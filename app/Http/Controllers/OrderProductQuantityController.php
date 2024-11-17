<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OrderProductQuantity;
use App\Http\Resources\OrderProductQuantityCollection;
use App\Http\Resources\OrderProductQuantityResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;


class OrderProductQuantityController extends Controller
{
    public function index(Request $request)
    {
        $order_product_quantities = OrderProductQuantity::all();
        return new OrderProductQuantityCollection($order_product_quantities);
    }

    public function store(Request $request)
    {
        $input = collect($request->all())->mapWithKeys(function ($value, $key) {
            return [Str::snake($key) => $value];
        })->toArray();

        $validator = $this->validator_create($input);

        if($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'code' => 400]);
        }

        if(OrderProductQuantity::create($input)) {
            return response()->json(['message' => 'OrderProductQuantity created',
                'code' => 201],
                201);
        } else {
            return response()->json(['message' => 'OrderProductQuantity not created',
                'code' => 500],
                500);
        }

    }

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

    public function update(Request $request)
    {
        $order_product_quantity = OrderProductQuantity::find($request->id);

        if(!$order_product_quantity) {
            return response()->json(['message' => 'OrderProductQuantity not found',
                'code' => 404],
                404);
        }

        $input = collect($request->all())->mapWithKeys(function ($value, $key) {
            return [Str::snake($key) => $value];
        })->toArray();

        $validator = $this->validator_create($input);

        if($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'code' => 400]);
        }

        if($order_product_quantity->update($input)) {
            return response()->json(['message' => 'OrderProductQuantity updated',
                'code' => 200],
                200);
        } else {
            return response()->json(['message' => 'OrderProductQuantity not updated',
                'code' => 500],
                500);
        }
    }

    public function destroy($order_id, $product_id)
    {
        $id = [$order_id, $product_id];
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
            'order_id' => 'required|integer',
            'product_id' => 'required|integer',
            'quantity' => 'required|integer',
            'quantity_type' => 'required|in:KG,PIECE',
            'price' => 'required|numeric|regex:/^\d{1,8}(\.\d{1,2})?$/'
        ]);
    }


}
