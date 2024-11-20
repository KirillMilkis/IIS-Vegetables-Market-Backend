<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Session;
use App\Models\Attribute;
use App\Models\User;

class ProductController extends Controller
{
    public function index(Request $request)
    {

        $sessionId = Session::getId();

        $categoryId = $request->input('category_id');
        $query = Product::query();

        if ($categoryId) {
            $category = Category::find($categoryId);

            if (!$category) {
                return response()->json(['message' => 'Category not found', 'code' => 404], 404);
            }

            $categoryIds = $this->getDescendantCategoryIds($categoryId);
            $query->whereIn('category_id', $categoryIds);
        } 

        $products = $query->get();

        if ($products->isEmpty()) {
            return response()->json(['message' => 'No products found', 'code' => 204], 204);
        }

        Session::put('filtered_products', $products);
      
        return ProductResource::collection($products);
    }

    public function getProductsByFarmer($farmerId)
    {
    
    $user = User::find($farmerId);

    if (!$user) {
       
        return response()->json(['message' => 'Farmer not found'], 404);
    }

    
    $products = $user->products; 

    
    return ProductResource::collection($products);
    }


    public function filter(Request $request)
    {
        $query = Product::query();

        $categoryId = $request->input('category_id');
        $filters = $request->input('filters', []); // Массив значений атрибутов
        $search = $request->input('name_like');

        if ($categoryId) {
            $categoryIds = $this->getDescendantCategoryIds($categoryId);
            $query->whereIn('category_id', $categoryIds);
        }

        if (!empty($filters) && is_array($filters)) {
            foreach ($filters as $attributeId => $data) {
                $attribute = Attribute::find($attributeId);

                if ($attribute && $attribute->value_type === 'PRICE/KG') {
                    if (isset($data['min']) || isset($data['max'])) {
                        $min = isset($data['min']) ? $data['min'] : 0;
                        $max = isset($data['max']) ? $data['max'] : PHP_INT_MAX;
                        $query->whereHas('attribute_values', function($q) use ($attributeId, $min, $max) {
                            $q->where('attribute_id', $attributeId)
                              ->whereBetween('value', [$min, $max]);
                        });
                    }
                }

                if ($attribute && $attribute->value_type === 'PRICE/PIECE') {
                    if (isset($data['min']) || isset($data['max'])) {
                        $min = isset($data['min']) ? $data['min'] : 0;
                        $max = isset($data['max']) ? $data['max'] : PHP_INT_MAX;
                        $query->whereHas('attribute_values', function($q) use ($attributeId, $min, $max) {
                            $q->where('attribute_id', $attributeId)
                              ->whereBetween('value', [$min, $max]);
                        });
                    }
                }

                if ($attribute && $attribute->value_type === 'PLACE') {
                    if (isset($data['value']) && is_array($data['value'])) {
                        $values = $data['value'];
                        $query->whereHas('attribute_values', function($q) use ($attributeId, $values) {
                            $q->where('attribute_id', $attributeId)
                              ->whereIn('value', $values);
                        });
                    }
                }
            }
        }

        if (!empty($search)) {
            $query->where('name', 'like', "%{$search}%");
        }

        $products = $query->get();

        if ($products->isEmpty()) {
            return response()->json(['message' => 'No products found', 'code' => 204], 204);
        }

        return ProductResource::collection($products);
    }


    private function getDescendantCategoryIds($categoryId)
    {
        $categoryIds = [$categoryId];
        $childCategories = Category::where('parent_id', $categoryId)->get();

        foreach ($childCategories as $childCategory) {
            $categoryIds = array_merge($categoryIds, $this->getDescendantCategoryIds($childCategory->id));
        }

        return $categoryIds;
    }


    public function store(Request $request)
    {
        $userInitiator = Auth::user();
        $farmerId = $userInitiator->id;

        $input = collect($request->all())->mapWithKeys(function ($value, $key) {
            return [Str::snake($key) => $value];
        })->toArray();

        $input['farmer_id'] = $farmerId;

        $validator = $this->validator_create($input);

        $category = Category::find($input['category_id']);
        if($category->parent_id == null){
            return response()->json(['message' => 'Product created',
                'code' => 400],
                400);
        }

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(), 
                'code' => 400]);
        }

        if (Product::create($input)) {
            return response()->json(['message' => 'Product created',
                'code' => 201],
                201);
        } else {
            return response()->json(['message' => 'Product not created',
                'code' => 500],
                500);
        }
        
    }

    public function show($id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['message' => 'Product not found',
                'code' => 404], 
                404);
        }
        return new ProductResource($product);
    }

    public function update(Request $request)
    {
        $userInitiator = Auth::user();

        $product = Product::find($request->id);

        if (!$product) {
            return response()->json(['message' => 'Product not found',
                'code' => 404], 
                404);
        }

        if($product->farmer_id != $userInitiator->id){
            return response()->json(['message' => "You dont have access to change other user's products",
            'code' => 403], 
            403);
        }

        $category = Category::find($input['category_id']);
        if($category->parent_id == null){
            return response()->json(['message' => 'Product created',
                'code' => 400],
                400);
        }

        $input = collect($request->all())->mapWithKeys(function ($value, $key) {
            return [Str::snake($key) => $value];
        })->toArray();

        $validator = $this->validator_update($input);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(), 
                'code' => 400]);
        }

        if ($product->update($input)) {
            return response()->json(['message' => 'Product updated',
                'code' => 200],
                200);
        } else {
            return response()->json(['message' => 'Product not updated',
                'code' => 500],
                500);
        }
    }

    public function destroy($id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['message' => 'Product not found',
                'code' => 404], 
                404);
        }

        if ($product->delete()) {
            return response()->json(['message' => 'Product deleted',
                'code' => 200],
                200);
        } else {
            return response()->json(['message' => 'Product not deleted',
                'code' => 500],
                500);
        }
    }

    private function validator_create($data){
        return Validator::make($data, [
            'name' => 'required|max:32',
            'description' => 'required|max:255',
            'farmer_id' => 'required|integer|exists:user,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'category_id' => 'required|integer|exists:category,id',
        ]);
    }

    private function validator_update($data){
        return Validator::make($data, [
            'name' => 'nullable|max:32',
            'description' => 'nullable|max:255',
            'farmer_id' => 'nullable|integer|exists:users,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'category_id' => 'nullable|integer|exists:category,id',
        ]);

    }
}
