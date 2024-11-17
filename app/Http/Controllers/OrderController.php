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

    public function store(Request $request)
    {
        $input = collect($request -> all())->mapWithKeys(function ($value, $key) {
            return [Str::snake($key) => $value];
        })->toArray();
        
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
        $order = Order::find($request->id);

        if (!$order) {
            return response()->json(['message' => 'Order not found',
                'code' => 404], 
                404);
        }

        $input = collect($request->all())->mapWithKeys(function ($value, $key) {
            return [Str::snake($key) => $value];
        })->toArray();

        $validator = $this->validator_create($input);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(), 
                'code' => 400]);
        }

        if ($order->update($input)) {
            return response()->json(['message' => 'Order updated',
                'code' => 200], 
                200);
        } else {
            return response()->json(['message' => 'Order not updated',
                'code' => 500], 
                500);
        }
        
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
            'address' => 'required|string|max:100',
            'status' => 'required|string|max:50|in:UNCONFIRMED,CONFIRMED,SHIPPED,DELIVERED',
            'user_id' => 'required|integer|exists:users,id',

        ]);
    }

}
