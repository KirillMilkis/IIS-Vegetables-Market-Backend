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
        $orders = Order::where('user_id', $id)
        ->where('status', 'ORDERED') // Фильтруем только заказы со статусом 'ordered'
        ->get();


        // Проверяем, есть ли заказы
        if ($orders->isEmpty()) {
            return response()->json([
                'message' => 'No orders found for this user',
                'code' => 204
            ], 204);
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
            ->where('status', 'UNORDERED')
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
            'orders' => new OrderCollection(collect([$latestOrder])),
            'code' => 200
        ], 200);
    }

    public function getUnorderedOrderForAnotherController($id)
    {
        // Проверяем, существует ли пользователь с указанным ID
        $user = User::find($id);
        if (!$user) {
            return null; // Возвращаем null, если пользователь не найден
        }

        // Получаем заказы со статусом "UNCONFIRMED" для указанного пользователя
        $orders = Order::where('user_id', $id)
            ->where('status', 'UNORDERED')
            ->get();

        // Проверяем, есть ли такие заказы
        if ($orders->isEmpty()) {
            return null; // Возвращаем null, если нет заказов
        }

        // Если заказов больше одного, удаляем все кроме первого
        if ($orders->count() > 1) {
            $ordersToDelete = $orders->slice(1); // Все, кроме самого последнего
            foreach ($ordersToDelete as $order) {
                $order->delete(); // Удаляем заказ
            }
        }

        // Получаем последний оставшийся заказ
        $latestOrder = $orders->first();

        // Возвращаем только последний заказ
        return $latestOrder; // Возвращаем сам заказ, а не ответ в формате JSON
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
        if(!isset($input['address'])){
            return response()->json(['message' => 'Address is required'], 400);
        }
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

    public function updateOrderStatusToOrdered(Request $request, $id)
    {
        $order = Order::find($id);
        $userAuth = Auth::user();
        $input = $request->only(['address']);

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
        if (empty($order->address)) {
            if(empty($input['address'])){
                return response()->json([
                    'message' => 'Address is required when user order an order',
                    'code' => 400
                ], 400);
            }
            $order->address = $input['address'];
        }

        // Сохраняем изменения в заказе
        $order->save();

        // Обновляем статус всех связанных записей в order_product_quantity на 'ordered'

        $OPQcontroller = new OrderProductQuantityController();

        // Call the function
        $OPQcontroller->changeOrderProductQuantityStatusToUnconfirmed($order->id);

        return response()->json(['message' => 'Order status updated to ordered', 'code' => 200], 200);
    }

    // Функция для изменения статуса всех order_product_quantity на 'ordered'
    
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
            'description' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:100',
            'status' => 'required|string|max:50|in:UNORDERED,UNCONFIRMED,CONFIRMED,SHIPPED,DELIVERED',
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
