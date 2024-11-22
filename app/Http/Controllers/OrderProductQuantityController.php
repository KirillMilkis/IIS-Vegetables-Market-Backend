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
use App\Models\User;
use App\Models\Product;
use Illuminate\Support\Facades\Log;
use App\Models\Order;

class OrderProductQuantityController extends Controller
{
    public function index(Request $request)
    {
        $order_product_quantities = OrderProductQuantity::all();
        return new OrderProductQuantityCollection($order_product_quantities);
    }


    public function getByOrderId($order_id)
    {
        $orderProductQuantities = OrderProductQuantity::where('order_id', $order_id)
        ->with('product') // Подгружаем связанную модель Product
        ->get();

        // Если записи не найдены, возвращаем ошибку
        if ($orderProductQuantities->isEmpty()) {
            return response()->json([
                'message' => 'No products found for this order',
                'code' => 404
            ], 404);
        }

    // Если записи найдены, возвращаем их
    return response()->json([
        'message' => 'Products found',
        'data' => $orderProductQuantities->map(function ($item) {
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product->name, // Название продукта
                'quantity' => $item->quantity,
                'price' => $item->price,
                'order_id' => $item->order_id,
            ];
        }),
        'code' => 200
    ], 200);
    }


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
    
        // Получаем заказ с указанным id
        $orderController = new OrderController(); // создаем экземпляр класса
        $order = $orderController->getUnorderedOrderForAnotherController($userAuth['id']);
        if (!$order) {
            return response()->json([
                'message' => 'Order not found or already confirmed',
                'code' => 404
            ], 404);
        }
    
        // Получаем информацию о продукте
        $product = Product::find($input['product_id']);
        if (!$product) {
            return response()->json([
                'message' => 'Product not found',
                'code' => 404
            ], 404);
        }
    
        // Получаем атрибуты продукта через промежуточную таблицу
        $attributeValues = $product->attributeValues;
        $price = $this->calculateProductPrice($input['product_id'], $input['quantity']);
    
        // Проверяем, какой атрибут указывает на цену за килограмм или за штуку
    
        if ($price <= 0) {
            return response()->json([
                'message' => 'Invalid price or quantity',
                'code' => 400
            ]);
        }
    
        // Обновляем данные в OrderProductQuantity
        $input['price'] = $price; // Цена для данного количества
        
        $input['quantity'] = $input['quantity']; // Количество продукта
        $input['order_id'] = $order->id; // Связываем с заказом
    
        // Создаем запись в OrderProductQuantity
        $orderProductQuantity = OrderProductQuantity::create($input);
    
        if ($orderProductQuantity) {
            // Обновляем total_price в заказе
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
    
    // Функция для обновления общей цены заказа
   
    

    public function show($order_id, $product_id)
    {
        $id = [$order_id, $product_id];
        $order_product_quantity = OrderProductQuantity::find($id);

        if(!$order_product_quantity) {
            return response()->json(['message' => 'OrderProductQuantity not found',
                'code' => 404],
                404);
        }

        return new OrderProductQuantityResource($order_product_quantity);
    }

    public function update(Request $request, $id)
    {
       // Находим OrderProductQuantity по id
        $orderProductQuantity = OrderProductQuantity::find($id);


        if (!$orderProductQuantity) {
            return response()->json([
                'message' => 'OrderProductQuantity not found',
                'code' => 404
            ], 404);
        }

        // Маппируем входные данные
        $input = collect($request->all())->mapWithKeys(function ($value, $key) {
            return [Str::snake($key) => $value];
        })->toArray();

        // Проверяем, что передано только поле quantity
        if (!isset($input['quantity']) || $input['quantity'] <= 0) {
            return response()->json([
                'message' => 'Invalid quantity',
                'code' => 400
            ], 400);
        }

        // Обновляем только quantity в OrderProductQuantity
        $orderProductQuantity->quantity = $input['quantity'];

        // Получаем цену для нового количества
        $price = $this->calculateProductPrice($orderProductQuantity->product_id, $input['quantity']);

        // Обновляем цену в OrderProductQuantity
        $orderProductQuantity->price = $price;

        // Сохраняем обновленную информацию
        if ($orderProductQuantity->save()) {
            // Пересчитываем total_price в заказе
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

    public function calculateProductPrice($productId, $quantity)
    {
        $product = Product::find($productId);
        if (!$product) {
            return 0; // Продукт не найден
        }

        // Получаем все атрибуты продукта
        $attributeValues = $product->attribute_values;
        $price = 0;


        // Ищем атрибут 'price' и рассчитываем цену в зависимости от quantity
        foreach ($attributeValues as $attributeValue) {
            Log::info("Processing attribute value: ", ['attribute_value' => $attributeValue->toArray()]);
            if ($attributeValue->attribute->name == 'Price/kg' || $attributeValue->attribute->name == 'Price/piece') {
    
                // В зависимости от типа цены
                if ($attributeValue->attribute->value_type == 'PRICE/PIECE') {
                    // Цена за штуку, используем значение из attribute_value как цену за штуку
                    $price = $attributeValue->value * $quantity;
                } else if ($attributeValue->attribute->value_type == 'PRICE/KG') {
                    // Цена за килограмм, используем значение из attribute_value как цену за килограмм

                    $price = $attributeValue->value * $quantity;
                }
            }
        }

        return $price;
    }

    public function updateTotalPriceInOrder($order)
    {
        // Получаем все OrderProductQuantity для этого заказа
        $orderProductQuantities = $order->order_product_quantities;
    
        // Рассчитываем общую стоимость
        $totalPrice = $orderProductQuantities->sum('price');
    
        // Обновляем total_price в заказе
        $order->total_price = $totalPrice;
        $order->save();
    }

    public function destroy($id)
    {
        $order_product_quantity = OrderProductQuantity::find($id);

        if(!$order_product_quantity) {
            return response()->json(['message' => 'OrderProductQuantity not found',
                'code' => 404],
                404);
        }

        if($order_product_quantity->delete()) {
            return response()->json(['message' => 'OrderProductQuantity deleted',
                'code' => 200],
                200);
        } else {
            return response()->json(['message' => 'OrderProductQuantity not deleted',
                'code' => 500],
                500);
        }
    }


    private function validator_create($input)
    {
        return Validator::make($input, [
            'order_id' => 'integer',
            'product_id' => 'required|integer',
            'quantity' => 'required|integer',
        ]);
    }
    private function validator_update($input)
    {
        return Validator::make($input, [
            'order_id' => 'integer',
            'product_id' => 'required|integer',
            'quantity' => 'required|integer',
        ]);
    }



}
