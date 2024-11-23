<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Http\Resources\OrderCollection;
use App\Http\Resources\OrderResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Support\Facades\Auth;



class OrderController extends Controller
{
    /**
     * Display a listing of the orders.
     *
     * @return OrderCollection
     */
    public function index(Request $request)
    {
        $orders = Order::all();
        return new OrderCollection($orders);
        
    }

    /**
     * Get orders by user ID
     *
     * @param int $id
     * @return OrderCollection
     */
    public function getByUser($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'message' => 'User not found',
                'code' => 404
            ], 404);
        }

        // In this method, we only want to return orders with status 'ORDERED', because 'UNORDERED' orders are not real orders
        $orders = Order::where('user_id', $id)
        ->where('status', 'ORDERED') 
        ->get();


        if ($orders->isEmpty()) {
            return response()->json([
                'message' => 'No orders found for this user',
                'code' => 204
            ], 204);
        }

        return response()->json([
            'message' => 'Orders retrieved successfully',
            'orders' => new OrderCollection($orders),
            'code' => 200
        ], 200);
    }

    /**
     * Get unordered orders by user ID
     *
     * @param int $id
     * @return OrderCollection
     */
    public function getUnorderedOrder($id)
    {
  
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'message' => 'User not found',
                'code' => 404
            ], 404);
        }

        // In this method, we only want to return orders with status 'UNORDERED'.
        // Order with this status works as a cart, so we only want to return the last one.
        $orders = Order::where('user_id', $id)
            ->where('status', 'UNORDERED')
            ->get();

        if ($orders->isEmpty()) {
            return response()->json([
                'message' => 'No unordered orders found for this user',
                'code' => 204
            ], 204);
        }

        // There should be only one UNORDERED order at a time.
        // Check if there are more than one and delete all but not the first one.
        // User will have only one cart at a time.
        if ($orders->count() > 1) {
            $ordersToDelete = $orders->slice(1); 
            foreach ($ordersToDelete as $order) {
                $order->delete(); 
            }
        }
    
        // Recieve the cart of the user, that is the last order that method did not delete.
        $latestOrder = $orders->first();

        return response()->json([
            'message' => 'Unconfirmed orders retrieved successfully',
            'orders' => new OrderCollection(collect([$latestOrder])),
            'code' => 200
        ], 200);
    }

    /**
     * Get unordered order for another controller
     * That is useful in the OrderProductQuantityController
     * 
     * @param int $id
     * @return Order
     */
    public function getUnorderedOrderForAnotherController($id)
    {
        $user = User::find($id);
        if (!$user) {
            return null; 
        }

         // In this method, we only want to return orders with status 'UNORDERED'.
        // Order with this status works as a cart, so we only want to return the last one.
        $orders = Order::where('user_id', $id)
            ->where('status', 'UNORDERED')
            ->get();

        if ($orders->isEmpty()) {
            return null; 
        }

        // There should be only one UNORDERED order at a time.
        // Check if there are more than one and delete all but not the first one.
        // User will have only one cart at a time.
        if ($orders->count() > 1) {
            $ordersToDelete = $orders->slice(1); 
            foreach ($ordersToDelete as $order) {
                $order->delete(); 
            }
        }

         // Recieve the cart of the user, that is the last order that method did not delete.
        $latestOrder = $orders->first();


        return $latestOrder; 
    }


    /**
     * Store a newly created order in storage.
     * It will be unordered by default, because it will work like a card
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $userAuth = Auth::user();
        // Only registered users can create orders (cart)
        if($userAuth['role'] != 'reg_user'){
            return response()->json(['message' => 'You dont have access with your role'], 403);
        }

        $input = collect($request -> all())->mapWithKeys(function ($value, $key) {
            return [Str::snake($key) => $value];
        })->toArray();

        // Add user_id to the input, that is the user who created the order(cart).
        // Total price is 0 by default, because it does not have items to but yet.
        $input['user_id'] = $userAuth->id;
        $input['total_price'] = '0';
        
        // If status is not set, set it to 'UNORDERED' or if it is set, check if it is 'UNORDERED'.
        if(isset($input['status'])){
            if( $input['status'] != 'UNORDERED') {
                return response()->json(['message' => 'Order while create must be unordered'], 403);
            }
        }
        if(!isset($input['status'])){
            $input['status'] = 'UNORDERED';
        }
        
        $validator = $this->validator_create($input);
        
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(), 
                'code' => 400]);
        }

        if (Order::create($input)) {
            return response()->json(['message' => 'Order created',
                'code' => 201], 
                201);
        } else {
            return response()->json(['message' => 'Order not created',
                'code' => 500], 
                500);
        }
        
    }

    /**
     * Display the specified order.
     *
     * @param int $id
     * @return OrderResource
     */
    public function show($id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found',
                'code' => 404],
                404);
        }

        return new OrderResource($order);

    }

    /**
     * Update the specified order in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        $userAuth = Auth::user();
        $order = Order::find($request->route('id'));

        if (!$order) {
            return response()->json(['message' => 'Order not found', 'code' => 404], 404);
        }

        // Check if status is not changing directly, because it is not allowed.
        if (isset($request->status)) {
            return response()->json(['message' => 'You cannot change the status directly'], 403);
        }

        // Update method is only for updating description and address.
        $input = $request->only(['description', 'address']);

        // Adress is required to update.
        if(!isset($input['address'])){
            return response()->json(['message' => 'Address is required'], 400);
        }
        if (isset($input['address']) && empty($input['address'])) {
            return response()->json(['message' => 'Address is required'], 400);
        }

        // Chaning the description and address.
        if (isset($input['description'])) {
            $order->description = $input['description'];
        }

        if (isset($input['address'])) {
            $order->address = $input['address'];
        }

        $order->save();

        return response()->json(['message' => 'Order updated successfully', 'code' => 200], 200);
    }

    /**
     * Update the specified order status to 'ORDERED'.
     * It is used when user orders the cart.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateOrderStatusToOrdered(Request $request, $id)
    {
        $order = Order::find($id);
        $userAuth = Auth::user();
        $input = $request->only(['address']);

        // Only user that created the order(cart) can order it.
        if($order->user_id != $userAuth['id']){
            return response()->json(['message' => 'You dont have access to orders from another users'], 403);
        } 

        $order = Order::find($request->route('id'));

        if (!$order) {
            return response()->json(['message' => 'Order not found', 'code' => 404], 404);
        }

        /// Changing the status to 'ORDERED'
        $order->status = 'ORDERED';
        
        // If address is not set yet, it should be set now when user orders the cart.
        if (empty($order->address)) {
            if(empty($input['address'])){
                return response()->json([
                    'message' => 'Address is required when user order an order',
                    'code' => 400
                ], 400);
            }
            $order->address = $input['address'];
        }

        // Setting the order date to current date.
        // Order date is taking now while processing this method.
        $currentTimestamp = now();
        $order->order_date = $currentTimestamp; 
        $order->save();

        // After changing the status to 'ORDERED', we need to change the status of all order_product_quantity to 'UNCONFIRMED'.
        // Because now the order is ordered and Farmers need to confirm the order_product_quantity(value of their products that user ordered).

        $OPQcontroller = new OrderProductQuantityController();

        // Call the function to do it in OrderProductQuantityController.
        $OPQcontroller->changeOrderProductQuantityStatusToUnconfirmed($order->id);

        return response()->json(['message' => 'Order status updated to ordered', 'code' => 200], 200);
    }
     
    /**
     * Remove the specified order from storage.
     * In our implementation it is not used, because order will be deleted when users are emptying their cart
     * In other cases, when order has status ORDERED, it should be forever in your database for customers convenience.    
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $order = Order::find($id);
        
        if (!$order) {
            return response()->json(['message' => 'Order not found',
                'code' => 404], 
                404);
        }
        
        if ($order->delete()) {
            return response()->json(['message' => 'Order deleted',
                'code' => 200], 
                200);
        } else {
            return response()->json(['message' => 'Order not deleted',
                'code' => 500], 
                500);
        }
    }

    /**
     * Validate the input for creating a new order.
     * 
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public function validator_create($data) {
        return Validator::make($data, [
            'total_price' => 'required|numeric|regex:/^\d{1,8}(\.\d{1,2})?$/',
            'description' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:100',
            'status' => 'required|string|max:50|in:UNORDERED,UNCONFIRMED,CONFIRMED,SHIPPED,DELIVERED',
            'user_id' => 'required|integer|exists:users,id',

        ]);
    }

    /**
     * Validate the input for updating an order.
     * 
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public function validator_update($data) {
        return Validator::make($data, [
            'total_price' => 'nullable|numeric|regex:/^\d{1,8}(\.\d{1,2})?$/',
            'description' => 'string|max:100',
            'address' => 'nullable|string|max:100',
            'status' => 'nullable|string|max:50|in:UNORDERED,UNCONFIRMED,CONFIRMED,SHIPPED,DELIVERED',
            'user_id' => 'nullable|integer|exists:users,id',

        ]);
    }

}
