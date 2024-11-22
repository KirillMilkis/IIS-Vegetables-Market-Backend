<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SelfHarvesting;
use App\Http\Resources\SelfHarvestingCollection;
use App\Http\Resources\SelfHarvestingResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Models\User; 
use App\Models\Product; 

class SelfHarvestingController extends Controller
{
    public function index(Request $request)
    {
        $selfHarvesting = SelfHarvesting::all();
        return new SelfHarvestingCollection($selfHarvesting);
        
    }

    public function getUserSelfHarvestings($userId)
    {
        // Найти пользователя по ID
        $user = User::find($userId);

        if (!$user) {
            return response()->json(['message' => 'User not found', 'code' => 404], 404);
        }

        // Получить привязанные записи SelfHarvesting
        $selfHarvestings = $user->selfHarvestingsVisits()->get();

        if ($selfHarvestings->isEmpty()) {
            return response()->json(['message' => 'No SelfHarvesting records found for this user', 'code' => 204], 204);
        }

        return response()->json([
            'message' => 'SelfHarvesting records retrieved successfully',
            'data' => $selfHarvestings,
            'code' => 200,
        ], 200);
    }

    public function getFarmerSelfHarvestings($userId)
    {
        // Найти пользователя по ID
        $user = User::find($userId);

        if (!$user) {
            return response()->json(['message' => 'User not found', 'code' => 404], 404);
        }

        // Получить привязанные записи SelfHarvesting
        $selfHarvestings = $user->selfHarvestingsPlanned()->get();

        if ($selfHarvestam6eharojeings->isEmpty()) {
            return response()->json(['message' => 'No SelfHarvesting records found for this user', 'code' => 204], 204);
        }

        return response()->json([
            'message' => 'SelfHarvesting records retrieved successfully',
            'data' => $selfHarvestings,
            'code' => 200,
        ], 200);
    }


    public function getProductSelfHarvestings($productId)
    {
        $product = Product::find($productId);

        if (!$product) {
            return response()->json(['message' => 'Product not found', 'code' => 404], 404);
        }

        // Получить привязанные записи SelfHarvesting
        $selfHarvestings = $product->self_harvestings()->get();

        if ($selfHarvestings->isEmpty()) {
            return response()->json(['message' => 'No SelfHarvesting records found for this product', 'code' => 204], 204);
        }

        return response()->json([
            'message' => 'SelfHarvesting records retrieved successfully',
            'data' => $selfHarvestings,
            'code' => 200,
        ], 200);
    }
    

    public function store(Request $request)
    {
        $input = collect($request->all())->mapWithKeys(function ($value, $key) {
            return [Str::snake($key) => $value];
        })->toArray();

        $product = Product::find($request->input('product_id'));

        // Проверяем, существует ли продукт
        if (!$product) {
            return response()->json(['message' => 'Product not found', 'code' => 404], 404);
        }

        // Получаем аутентифицированного пользователя
        $user = Auth::user();

        // Проверяем, что пользователь является владельцем продукта
        if ($product->farmer_id != $user['id']) {
            return response()->json(['message' => 'You can only create an event for your own product', 'code' => 403], 403);
        }

        
        if (SelfHarvesting::create($input)) {
            return response()->json(['message' => 'SelfHarvesting created',
                'code' => 201], 
                201);
        } else {
            return response()->json(['message' => 'SelfHarvesting not created',
                'code' => 500], 
                500);
        }
        
    }

    public function show($id)
    {
        $selfHarvesting = SelfHarvesting::find($id);

        if (!$selfHarvesting) {
            return response()->json(['message' => 'SelfHarvesting not found',
                'code' => 404],
                404);
        }

        return new SelfHarvestingResource($selfHarvesting);
    }

    public function update(Request $request)
    {
        $input = collect($request->all())->mapWithKeys(function ($value, $key) {
            return [Str::snake($key) => $value];
        })->toArray();
        $selfHarvesting = SelfHarvesting::find($input['id']);
        
        if (!$selfHarvesting) {
            return response()->json(['message' => 'SelfHarvesting not found',
                'code' => 404],
                404);
        }
        
        if ($selfHarvesting->update($input)) {
            return response()->json(['message' => 'SelfHarvesting updated',
                'code' => 200], 
                200);
        } else {
            return response()->json(['message' => 'SelfHarvesting not updated',
                'code' => 500], 
                500);
        }
    }

    public function destroy($id)
    {
        $selfHarvesting = SelfHarvesting::find($id);
        
        if (!$selfHarvesting) {
            return response()->json(['message' => 'SelfHarvesting not found',
                'code' => 404], 
                404);
        }
        
        if ($selfHarvesting->delete()) {
            return response()->json(['message' => 'SelfHarvesting deleted',
                'code' => 200], 
                200);
        } else {
            return response()->json(['message' => 'SelfHarvesting not deleted',
                'code' => 500], 
                500);
        }
    }

    public function validation_create($data){
        return Validator::make($data, [
            'name' => 'required|string|max:50',
            'description' => 'required|string|max:100',
            // 'price' => 'required|numeric',
            'dateTime' => 'required|timestamp',
            'location' => 'required|string|max:50',
            'farmer_id' => 'required|numeric|exists: users,id',
            'product_id' => 'required|numeric|exists: products,id',
        ]);
    }

    public function validation_update($data){
        return Validator::make($data, [
            'name' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:100',
            // 'price' => 'required|numeric',
            'dateTime' => 'nullable|timestamp',
            'location' => 'nullable|string|max:50',
            'farmer_id' => 'nullable|numeric|exists: users,id',
            'product_id' => 'nullable|numeric|exists: products,id',
        ]);
    }
}
