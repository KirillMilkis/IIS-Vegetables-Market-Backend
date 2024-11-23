<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OrderProductQuantity;
use App\Http\Resources\OrderProductQuantityCollection;
use App\Http\Resources\OrderProductQuantityResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\OrderController;
use App\Models\Product;
use Illuminate\Support\Facades\Log;
use App\Models\Order;

class OrderProductQuantityController extends Controller
{
    /**
     * Display a listing of the orderProductQuantity(Quantity of product in the order).
     *
     * @return OrderProductQuantityCollection
     */
    public function index(Request $request)
    {
        $order_product_quantities = OrderProductQuantity::all();
        return new OrderProductQuantityCollection($order_product_quantities);
    }


    /**
     * Get the OrderProductQuantity by order_id
     *
     * @param int $order_id
     * @return OrderProductQuantityCollection
     */
    public function getByOrderId($order_id)
    {
        $orderProductQuantities = OrderProductQuantity::where('order_id', $order_id)
        ->with('product')
        ->get();

        if ($orderProductQuantities->isEmpty()) {
            return response()->json([
                'message' => 'No products found for this order',
                'code' => 404
            ], 404);
        }

        // Add the product name to each OrderProductQuantity.
        // This is a virtual attribute and does not exist in the database.
        // It is added to the model instance for the response for the frontend convienence, to display product name with 
        // the order product quantity.
        $orderProductQuantities->map(function ($item) {
            $item->product_name = $item->product->name; // Add the product name
            return $item;
        });

            return new OrderProductQuantityCollection($orderProductQuantities);
    }


    /**
     * Get the OrderProductQuantity by farmer id
     *
     * @param int $product_id
     * @return OrderProductQuantityCollection
     */
    public function getByFarmerId($farmerId)
    {
        // if the farmer id is not provided, get the authenticated user's id and use it like a farmer id.
        $authUser = Auth::user();
        if(empty($farmerId)){
            $farmerId = $authUser['id'];
        }

        $userProductIds = Product::where('farmer_id', $farmerId)->pluck('id');

        // Fetch OrderProductQuantities linked to the farmers product. 
        // Fetch only those quantity of products that were ordered.
        // In future farmer can see all the products that were ordered and approve that he shipped it. 
        $orderProductQuantities = OrderProductQuantity::whereIn('product_id', $userProductIds)
        ->whereHas('order', function($query) {
            $query->where('status', '!=', 'UNORDERED'); 
        })
        ->with('product') // Load the product information.
        ->get();

        if ($orderProductQuantities->isEmpty()) {
            return response()->json([
                'message' => 'No products found for this user',
                'code' => 204
            ], 204);
        }

        // Add the product name, address and date_time to each OrderProductQuantity.
        // Thats for the frontend convienence, to display product name, address and date_time with the order product quantity.
        // Farmer should know all this information from this request.
        $orderProductQuantities->map(function ($item) {
            $item->product_name = $item->product->name; // Add the product name
            $item->address = $item->order->address;
            $item->date_time = $item->order->order_date;
            return $item;
        });

 
        return new OrderProductQuantityCollection($orderProductQuantities);
    }

    /**
     * Get the OrderProductQuantity by product id
     *
     * @param int $product_id
     * @return OrderProductQuantityCollection
     */
    public function store(Request $request)
    {

        $userAuth = Auth::user();
        $input = collect($request->all())->mapWithKeys(function ($value, $key) {
            return [Str::snake($key) => $value];
        })->toArray();
    
        $validator = $this->validator_create($input);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'code' => 400
            ]);
        }
    
        // Find the order that is not yet confirmed for the user
        // This order that is gonna be found is used like a cart.
        // Add to this card the product that user wants to order.
        $orderController = new OrderController(); // создаем экземпляр класса
        $order = $orderController->getUnorderedOrderForAnotherController($userAuth['id']);
        if (!$order) {
            return response()->json([
                'message' => 'Order not found or already confirmed',
                'code' => 404
            ], 404);
        }
    
        // Get the product that user wants to order.
        $product = Product::find($input['product_id']);
        if (!$product) {
            return response()->json([
                'message' => 'Product not found',
                'code' => 404
            ], 404);
        }
    
        // Get the attribute values of the product.
        $attributeValues = $product->attribute_values;
        // Calculate the price for the quantity of product based on the price that was stored in the attribute value with type PRICE/KG or PRICE/PIECE.
        $price = $this->calculateProductPrice($input['product_id'], $input['quantity']);
    
        // Check if the price is valid.
        if ($price <= 0) {
            return response()->json([
                'message' => 'Invalid price or quantity',
                'code' => 400
            ]);
        }

        // Find the attribute 'Quantity' for this product that indicate how many pieces or kgs left in the stock.
        $quantityAttribute = $attributeValues->first(function ($attributeValue) {
            return $attributeValue->attribute && $attributeValue->attribute->name == 'Quantity';
        });
        
        if (!$quantityAttribute) {
            return response()->json([
                'message' => 'Does not have quantity',
                'code' => 400
            ], 400);
        }
    
        // Check if it is enough quantity in the stock to order.
        if ($quantityAttribute->value < $input['quantity']) {
            return response()->json([
                'message' => 'Insufficient quantity available',
                'code' => 400
            ], 400);
        }
    
        // Decrease the quantity in the stock.
        $quantityAttribute->value -= $input['quantity'];
        $quantityAttribute->save();
    
        // Set all the necessary fields for the new OrderProductQuantity record.
        $input['price'] = $price; // Price for the quantity.
        $input['quantity'] = $input['quantity']; // Quantity of product that user wants to order..
        $input['order_id'] = $order->id; // Relate this order product quantity to the order(cart).
    
        // Create the OrderProductQuantity record.
        $orderProductQuantity = OrderProductQuantity::create($input);
    
        if ($orderProductQuantity) {
            //  Update the total price in the order based on the calculated price of this quantity of product.
            $this->updateTotalPriceInOrder($order);
    
            return response()->json([
                'message' => 'OrderProductQuantity created successfully',
                'code' => 201
            ], 201);
        } else {
            return response()->json([
                'message' => 'OrderProductQuantity not created',
                'code' => 500
            ], 500);
        }
    }
    
    /**
     * Display the specified orderProductQuantity.
     *
     * @param int $order_id
     * @param int $product_id
     * @return OrderProductQuantityResource
     */
    public function show($order_id, $product_id)
    {
        $id = [$order_id, $product_id];
        $orderProductQuantity = OrderProductQuantity::find($id);

        if(!$orderProductQuantity) {
            return response()->json(['message' => 'OrderProductQuantity not found',
                'code' => 404],
                404);
        }

        return new OrderProductQuantityResource($orderProductQuantity);
    }
    
    /**
     * Update the specified orderProductQuantity in storage.
     * This method is used to update the quantity of product in the order that is used like a cart(Has status 'UNORDERED').
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
    
        $orderProductQuantity = OrderProductQuantity::find($id);

        if (!$orderProductQuantity) {
            return response()->json([
                'message' => 'OrderProductQuantity not found',
                'code' => 404
            ], 404);
        }

       
        $input = collect($request->all())->mapWithKeys(function ($value, $key) {
            return [Str::snake($key) => $value];
        })->toArray();

        // Quantity is required for this update and it should be positive value.
        if (!isset($input['quantity']) || $input['quantity'] <= 0) {
            return response()->json([
                'message' => 'Invalid quantity',
                'code' => 400
            ], 400);
        }

        // Save the previous quantity and new quantity.
        $previousQuantity = $orderProductQuantity->quantity;
        $newQuantity = $input['quantity'];

        // Find the attribute values of the product that is linked to this order product quantity.
        $product = Product::find($orderProductQuantity->product_id);
        $attributeValues = $product->attribute_values;

        // Find the attribute 'Quantity' for this product that indicate how many pieces or kgs left in the stock.
        $quantityAttribute = $attributeValues->first(function ($attributeValue) {
            return $attributeValue->attribute && $attributeValue->attribute->name == 'Quantity';
        });

        if (!$quantityAttribute) {
            return response()->json([
                'message' => 'Product does not have a quantity attribute',
                'code' => 400
            ], 400);
        }

        // Case when new quantity is less than previous quantity
        if ($newQuantity < $previousQuantity) {
            // Calculate the difference and increase the quantity in the stock.
            $difference = $previousQuantity - $newQuantity;
            $quantityAttribute->value += $difference;
            $quantityAttribute->save(); 
        }

        // Case when new quantity is greater than previous quantity
        if ($newQuantity > $previousQuantity) {
            // Check if it is enough quantity in the stock to order.
            $difference = $newQuantity - $previousQuantity;
            if ($quantityAttribute->value < $difference) {
                return response()->json([
                    'message' => 'Not enough quantity in stock',
                    'code' => 400
                ], 400);
            }
            // Decrease the quantity in the stock.
            $quantityAttribute->value -= $difference;
            $quantityAttribute->save(); 
        }

        // Update the quantity in the order product quantity.
        $orderProductQuantity->quantity = $newQuantity;


        // Get the price forr the new quantity of product.
        $price = $this->calculateProductPrice($orderProductQuantity->product_id, $input['quantity']);

        // Update the filed price.
        $orderProductQuantity->price = $price;

        // Save the changes.
        if ($orderProductQuantity->save()) {
            // Calculate the total price in the order based on the new quantity of product.
            $order = Order::find($orderProductQuantity->order_id);

            $this->updateTotalPriceInOrder($order);

            return response()->json([
                'message' => 'OrderProductQuantity quantity updated',
                'code' => 200
            ], 200);
        } else {
            return response()->json([
                'message' => 'OrderProductQuantity not updated',
                'code' => 500
            ], 500);
        }
    }

    /**
     * Calculate the price for the quantity of product based on the price that was stored in the attribute value with type PRICE/KG or PRICE/PIECE.
     *
     * @param int $productId
     * @param int $quantity
     * @return float
     */
    public function calculateProductPrice($productId, $quantity)
    {
        $product = Product::find($productId);
        if (!$product) {
            return 0; 
        }

        // Get all attribute values of the product.
        $attributeValues = $product->attribute_values;
        $price = 0;


        // Search for the attribute 'Price/kg' or 'Price/piece' and calculate the price based on the quantity.
        foreach ($attributeValues as $attributeValue) {

            if ($attributeValue->attribute->name == 'Price/kg' || $attributeValue->attribute->name == 'Price/piece') {
    
                $price = $attributeValue->value * $quantity;
            }
        }

        return $price;
    }

    /**
     * Update the total price in the order based on the quantity of product.
     *
     * @param Order $order
     * @return void
     */
    public function updateTotalPriceInOrder($order)
    {
        // Get all order product quantities linked to the order.
        $orderProductQuantities = $order->order_product_quantities;
    
        // Calculate the total price based from all the order product quantities in this order.
        $totalPrice = $orderProductQuantities->sum('price');
    
        // Update the total price in the order.
        $order->total_price = $totalPrice;
        $order->save();
    }

    /**
     * Update the status of the order product quantity.
     * This is the method for the farmer to confirm that he approved and shipped the product.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus($id)
    {
        // Only registrated user (farmer) can update the status.
        $authUser = Auth::user();
        if($authUser['role'] != 'reg_user') {
            return response()->json([
                'message' => 'Only farmer can update the status',
                'code' => 403
            ], 403);
        }

        $orderProductQuantity = OrderProductQuantity::find($id);

        if (!$orderProductQuantity) {
            return response()->json([
                'message' => 'OrderProductQuantity not found',
                'code' => 404,
            ], 404);
        }

        // Define valid status transitions
        $validTransitions = [
            'UNCONFIRMED' => 'CONFIRMED',
            'CONFIRMED' => 'SHIPPED',
        ];

        $currentStatus = $orderProductQuantity->status;

        // Check if the current status has a valid transaction. 
        if (!isset($validTransitions[$currentStatus])) {
            return response()->json([
                'message' => 'No valid transition for the current status',
                'current_status' => $currentStatus,
                'code' => 400,
            ], 400);
        }

        // Update the status to the next state
        $newStatus = $validTransitions[$currentStatus];
        $orderProductQuantity->status = $newStatus;

        if ($orderProductQuantity->save()) {
            return response()->json([
                'message' => 'Status updated successfully',
                'new_status' => $newStatus,
                'code' => 200,
            ], 200);
        } else {
            return response()->json([
                'message' => 'Failed to update status',
                'code' => 500,
            ], 500);
        }
    }

    /**
     * Remove the specified orderProductQuantity from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $orderProductQuantity = OrderProductQuantity::find($id);

        if(!$orderProductQuantity) {
            return response()->json(['message' => 'OrderProductQuantity not found',
                'code' => 404],
                404);
        }

        $order = $orderProductQuantity->order; 
        
        // Order must be in UNORDERED status to delete the product quantity. 
        // If the order is already ordered, it is not possible to delete the product quantity.
        if ($order->status !== 'UNORDERED') {
            return response()->json([
                'message' => 'Order status must be UNORDERED to delete the product quantity',
                'code' => 400
            ], 400);
        }

        // Get the product and all product attributes.
        $product = Product::find($orderProductQuantity->product_id);
        $attributeValues = $product->attribute_values;
    
        // Find the attribute quantity for this product.
        $quantityAttribute = $attributeValues->first(function ($attributeValue) {
            return $attributeValue->attribute && $attributeValue->attribute->name == 'Quantity';
        });
    
        if (!$quantityAttribute) {
            return response()->json([
                'message' => 'Product does not have a quantity attribute',
                'code' => 400
            ], 400);
        }
    
        // Return the amount that user wanted to order to the stock.
        $quantityAttribute->value += $orderProductQuantity->quantity;
        $quantityAttribute->save(); // Сохраняем изменения в stock

        if($orderProductQuantity->delete()) {
            // In order(cart) recalculate the total price based that the product quantity was deleted.
            $this->updateTotalPriceInOrder($order);

            if ($order->order_product_quantities->isEmpty()) {
                $order->delete();
                return response()->json([
                    'message' => 'OrderProductQuantity deleted and order deleted (last item)',
                    'code' => 200
                ], 200);
            }

            return response()->json(['message' => 'OrderProductQuantity deleted',
                'code' => 200],
                200);
        } else {
            return response()->json(['message' => 'OrderProductQuantity not deleted',
                'code' => 500],
                500);
        }
    }

    /**
     * Change the status of all order product quantities to 'UNCONFIRMED' for the order.
     *
     * @param int $orderId
     * @return void
     */
    public function changeOrderProductQuantityStatusToUnconfirmed($orderId)
    {
    
        $order = Order::find($orderId);

        
        if (!$order) {
            return response()->json(['message' => 'Order not found', 'code' => 404], 404);
        }

        // Get all order product quantities linked to the order.
        $orderProductQuantities = $order->order_product_quantities; 

        // Change the status of all order product quantities to 'UNCONFIRMED'.
        foreach ($orderProductQuantities as $orderProductQuantity) {
           
            $orderProductQuantity->status = 'UNCONFIRMED';
            $orderProductQuantity->save(); 
        }

        return;
    }

    /**
     * Validate the input for creating a new orderProductQuantity.
     *
     * @param array $input
     * @return \Illuminate\Contracts\Validation\Validator
     */
    private function validator_create($input)
    {
        return Validator::make($input, [
            'order_id' => 'integer',
            'product_id' => 'required|integer',
            'quantity' => 'required|integer',
        ]);
    }



}
