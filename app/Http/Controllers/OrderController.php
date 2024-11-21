<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Http\Resources\OrderCollection;
use App\Http\Resources\OrderResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;


class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::all();
        return new OrderCollection($orders);
        
    }

    public function getByUser($id)
    {
        // Проверяем, существует ли пользователь с указанным ID
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'message' => 'User not found',
                'code' => 404
            ], 404);
        }

        // Получаем заказы пользователя
        $orders = Order::where('user_id', $id)->get();

        // Проверяем, есть ли заказы
        if ($orders->isEmpty()) {
            return response()->json([
                'message' => 'No orders found for this user',
                'code' => 404
            ], 404);
        }

        // Возвращаем заказы в виде коллекции
        return response()->json([
            'message' => 'Orders retrieved successfully',
            'orders' => new OrderCollection($orders),
            'code' => 200
        ], 200);
    }

    public function getUnorderedOrder($id)
    {
        // Проверяем, существует ли пользователь с указанным ID
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'message' => 'User not found',
                'code' => 404
            ], 404);
        }

        // Получаем заказы со статусом "UNCONFIRMED" для указанного пользователя
        $orders = Order::where('user_id', $id)
            ->where('status', 'UNCONFIRMED')
            ->get();

        // Проверяем, есть ли такие заказы
        if ($orders->isEmpty()) {
            return response()->json([
                'message' => 'No unordered orders found for this user',
                'code' => 204
            ], 204);
        }


        if ($orders->count() > 1) {
            $ordersToDelete = $orders->slice(1); // Все, кроме самого последнего
            foreach ($ordersToDelete as $order) {
                $order->delete(); // Удаляем заказ
            }
        }
    
        // Получаем последний оставшийся заказ
        $latestOrder = $orders->first();

        // Возвращаем заказы в виде коллекции
        return response()->json([
            'message' => 'Unconfirmed orders retrieved successfully',
            'orders' => new OrderCollection($latestOrder),
            'code' => 200
        ], 200);
    }



    public function store(Request $request)
    {
        $userAuth = Auth::user();
        if($userAuth['role'] != 'reg_user'){
            return response()->json(['message' => 'You dont have access with your role'], 403);
        }

        $input = collect($request -> all())->mapWithKeys(function ($value, $key) {
            return [Str::snake($key) => $value];
        })->toArray();

        $input['user_id'] = $userAuth->id;
        $input['total_price'] = '0';
     
        if($input['status'] != 'UNORDERED'){
            return response()->json(['message' => 'Order while create must be unordered'], 403);
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


    public function update(Request $request)
    {
        $userAuth = Auth::user();
        $order = Order::find($request->route('id'));

        if (!$order) {
            return response()->json(['message' => 'Order not found', 'code' => 404], 404);
        }

        // Проверка, что запрос не меняет status
        if (isset($request->status)) {
            return response()->json(['message' => 'You cannot change the status directly'], 403);
        }

        // Обновление description и address
        $input = $request->only(['description', 'address']);

        // Проверка на обязательные поля
        if (isset($input['address']) && empty($input['address'])) {
            return response()->json(['message' => 'Address is required'], 400);
        }

        // Обновляем только description и address
        if (isset($input['description'])) {
            $order->description = $input['description'];
        }

        if (isset($input['address'])) {
            $order->address = $input['address'];
        }

        // Сохраняем изменения
        $order->save();

        return response()->json(['message' => 'Order updated successfully', 'code' => 200], 200);
    }

    public function updateOrderStatusToOrdered(Request $request)
    {
        $order = Order::find($id);
        $userAuth = Auth::user();

        if($order->user_id != $userAuth['id']){
            return response()->json(['message' => 'You dont have access to orders from another users'], 403);
        } 

        $order = Order::find($request->route('id'));

        if (!$order) {
            return response()->json(['message' => 'Order not found', 'code' => 404], 404);
        }

        // Меняем статус на 'ORDERED'
        $order->status = 'ORDERED';
        
        // Если адрес был передан, обновляем его
        if (!$address) {
            return response()->json([
                'message' => 'Address is required when user order an order',
                'code' => 400
            ], 400);
        }

        // Сохраняем изменения в заказе
        $order->save();

        // Обновляем статус всех связанных записей в order_product_quantity на 'ordered'
        $this->changeOrderProductQuantityStatusToOrdered($orderId);

        return response()->json(['message' => 'Order status updated to ordered', 'code' => 200], 200);
    }

    // Функция для изменения статуса всех order_product_quantity на 'ordered'
    public function changeOrderProductQuantityStatusToOrdered($orderId)
    {
        // Находим заказ по ID
        $order = Order::find($orderId);

        // Если заказ не найден
        if (!$order) {
            return response()->json(['message' => 'Order not found', 'code' => 404], 404);
        }

        // Обновляем статус всех связанных записей в order_product_quantity на 'ordered'
        $orderProductQuantities = $order->orderProductQuantities; // Предполагаем, что связь с order_product_quantity установлена

        foreach ($orderProductQuantities as $orderProductQuantity) {
            // Меняем статус на 'ordered'
            $orderProductQuantity->status = 'ordered';
            $orderProductQuantity->save(); // Сохраняем изменения
        }

        return response()->json(['message' => 'Order product quantities status updated to ordered', 'code' => 200], 200);
    }
    
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

    public function validator_create($data) {
        return Validator::make($data, [
            'total_price' => 'required|numeric|regex:/^\d{1,8}(\.\d{1,2})?$/',
            'description' => 'string|max:100',
            'address' => 'nullable|string|max:100',
            'status' => 'required|string|max:50|in:UNCONFIRMED,CONFIRMED,SHIPPED,DELIVERED',
            'user_id' => 'required|integer|exists:users,id',

        ]);
    }

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
