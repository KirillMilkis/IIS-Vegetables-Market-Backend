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



    // public function filter(Request $request)
    // {

    //     // dd($request->all());
    //     $sessionId = Session::getId();
    //     echo 'Session ID: ' . $sessionId;

    //     $filteredResults = Session::get('filtered_products');


    //     if (!$filteredResults) {
    //         echo 'No products found';
    //         return response()->json(['message' => 'No products found', 'code' => 205], 205);
    //     }

    //     $fullUrl = $request->fullUrl();

    //     echo 'Full URL: ' . $fullUrl;

    //     $filters = $request->input('filters', []);

    //     foreach ($filters as $attributeId => $data) {
    //         // Проверяем, есть ли диапазон (min и max)
    //         $attribute = Attribute::find($attributeId);
    //         echo 'Filtered results: ' . $attribute->value_type;

    //         if ($attribute && $attribute->value_type === 'PRICE/KG') {
    //             if (isset($data['min']) && isset($data['max'])) {
    //                 $min = isset($data['min']) ? $data['min'] : 0;
    //                 $max = isset($data['max']) ? $data['max'] : PHP_INT_MAX;
    //                 $filteredResults = $filteredResults->filter(function ($product) use ($attributeId, $min, $max) {
    //                     return $product->attribute_values->contains(function ($attributeValue) use ($attributeId, $min, $max) {
    //                         return $attributeValue->attribute_id == $attributeId && $attributeValue->value >= $min && $attributeValue->value <= $max;
    //                     });
    //                 });
    //             }
    //         }

    //         if ($attribute && $attribute->value_type === 'PRICE/PIECE') {
    //             if (isset($data['min']) && isset($data['max'])) {
    //                 $min = isset($data['min']) ? $data['min'] : 0;
    //                 $max = isset($data['max']) ? $data['max'] : PHP_INT_MAX;
    //                 $filteredResults = $filteredResults->filter(function ($product) use ($attributeId, $min, $max) {
    //                     return $product->attribute_values->contains(function ($attributeValue) use ($attributeId, $min, $max) {
    //                         return $attributeValue->attribute_id == $attributeId && $attributeValue->value >= $min && $attributeValue->value <= $max;
    //                     });
    //                 });
    //             }
    //         }

    //         if ($attribute && $attribute->value_type === 'PLACE') {
    //             if (isset($data['value']) && is_array($data['value'])) {
    //                 $values = $data['value'];
    //                 $filteredResults = $filteredResults->filter(function ($product) use ($attributeId, $values) {
    //                     return $product->attributeValues->contains(function ($attributeValue) use ($attributeId, $values) {
    //                         return $attributeValue->attribute_id == $attributeId && in_array($attributeValue->value, $values);
    //                     });
    //                 });
    //             }
    //         }

       
    // }



    //     if (!empty($search)) {
    //         $filteredResults = $filteredResults->filter(function ($product) use ($search) {
    //             return stripos($product->name, $search) !== false;
    //         });
    //     }


    //     if ($filteredResults->isEmpty()) {
    //         return response()->json(['message' => 'No products found', 'code' => 204], 204);
    //     }

    //     return ProductResource::collection($filteredResults);
    // }



    private function getDescendantCategoryIds($categoryId)
    {
        $categoryIds = [$categoryId];
        $childCategories = Category::where('parent_id', $categoryId)->get();

        foreach ($childCategories as $childCategory) {
            $categoryIds = array_merge($categoryIds, $this->getDescendantCategoryIds($childCategory->id));
        }

        return $categoryIds;
    }

    // public function filter(Request $request)
    // {

    //     $query = Product::query();

    //     if ($request->has('category_id')) {
    //         $categoryId = $request->query('category_id');
    //         $categoryIds = $this->getDescendantCategoryIds($categoryId);
    //         $query->whereIn('category_id', $categoryIds);
    //     }

    //     $products = $query->get();

    //     if ($products->isEmpty()) {
    //         return response()->json(['message' => 'No products found', 'code' => 404], 404);
    //     }
    //     return ProductResource::collection($products);
    // }

    // private function getDescendantCategoryIds($categoryId)
    // {
    //     $categoryIds = [$categoryId];
    //     $childCategories = Category::where('parent_id', $categoryId)->get();
    //     echo 'Child categories: ' . $childCategories;
    //     foreach ($childCategories as $childCategory) {
    //         $categoryIds = array_merge($categoryIds, $this->getDescendantCategoryIds($childCategory->id));
    //     }

    //     return $categoryIds;
    // }




    public function store(Request $request)
    {
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
        $product = Product::find($request->id);

        if (!$product) {
            return response()->json(['message' => 'Product not found',
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
        ]);
    }
}
