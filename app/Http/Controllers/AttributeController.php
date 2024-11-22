<?php

namespace App\Http\Controllers;

use App\Http\Resources\AttributeCollection;
use App\Http\Resources\AttributeResource;
use Illuminate\Http\Request;
use App\Models\Attribute;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\Category;
use App\Models\Product;

class AttributeController extends Controller
{

    public function index()
    {
        $attributes = Attribute::all();

        return new AttributeCollection($attributes);

    }

    public function getByCategoryOrProduct(Request $request)
    {
        $categoryId = $request->input('category_id');
        $productId = $request->input('product_id');

        $query = Attribute::query();

        if ($categoryId && $productId){

            return response()->json([
                'message' => 'You cannot specify both category_id and product_id at the same time.',
                'code' => 400
            ], 400);
        }
        

        if ($categoryId) {

            $query->whereHas('attribute_categories', function($q) use ($categoryId) {
                $q->where('category_id', $categoryId); 
            });
        } else if ($productId) {

            $query->whereHas('attribute_values', function($q) use ($productId) {
                $q->where('product_id', $productId); 
            });

        }

        $attributes = $query->get();

        if ($attributes->isEmpty()) {
            return response()->json(['message' => 'No attributes found', 'code' => 204], 204);
        }

        // if ($categoryId) {
        //     $attributes->each(function ($attribute) use ($categoryId) {
        //         // Find the associated pivot record (category_attribute) that matches the given categoryId
        //         $categoryAttribute = $attribute->attribute_categories->filter(function ($categoryAttribute) use ($categoryId) {
        //             return $categoryAttribute->category_id == $categoryId;
        //         })->first(); // Get the first match (if any)
                
        //         if ($categoryAttribute) {
        //             // Dynamically attach 'is_required' to the attribute object from the specific category
        //             $attribute->is_required = $categoryAttribute->is_required;
        //         }
        //     });
        // }

        $attributes->each(function ($attribute) use ($categoryId, $productId) {
            // Если указан category_id, берем его напрямую
            if ($categoryId) {
                $categoryAttribute = $attribute->attribute_categories->firstWhere('category_id', $categoryId);
            } 
            // Если указан product_id, берем категорию продукта
            else if ($productId) {
                $product = Product::find($productId);
                $productCategory = $product->category->first();
                $categoryAttribute = $attribute->attribute_categories->firstWhere('category_id', $productCategory->id);
            }
        
            // Если найдено, добавляем флаг is_required
            if ($categoryAttribute) {
                $attribute->is_required = $categoryAttribute->is_required;
            } else {
                $attribute->is_required = false; // Если связи нет, по умолчанию false
            }
        });


        return new AttributeCollection($attributes);
        
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

        if (Attribute::create($input)) {
            return response()->json(['message' => 'Attribute created',
                'code' => 201], 
                201);
        } else {
            return response()->json(['message' => 'Attribute not created',
                'code' => 500], 
                500);
        }
        
    }

    public function show($id)
    {
        $attribute = Attribute::find($id);

        if (!$attribute) {
            return response()->json(['message' => 'Attribute not found',
                'code' => 404],
                404);
        }

        return new AttributeResource($attribute);

    }

    public function update(Request $request, $id)
    {
        
        $attribute = Attribute::find($id);

        if (!$attribute) {
            return response()->json(['message' => 'Attribute not found',
                'code' => 404], 
                404);
        }

        $input = collect($request -> all())->mapWithKeys(function ($value, $key) {
            return [Str::snake($key) => $value];
        })->toArray();

        $validator = $this->validator_update($input);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(), 
                'code' => 400]);
        }

        if ($attribute->update($input)) {
            return response()->json(['message' => 'Attribute updated',
                'code' => 200], 
                200);
        } else {
            return response()->json(['message' => 'Attribute not updated',
                'code' => 500], 
                500);
        }


    }

    public function destroy($id)
    {
        
        $attribute = Attribute::find($id);

        if (!$attribute) {
            return response()->json(['message' => 'Attribute not found',
                'code' => 404], 
                404);
        }

        if ($attribute->delete()) {
            return response()->json(['message' => 'Attribute deleted',
                'code' => 200], 
                200);
        } else {
            return response()->json(['message' => 'Attribute not deleted',
                'code' => 500], 
                500);
        }

    }

    private function validator_create(array $data)
    {
        return Validator::make($data, [
            'name' => 'required|string|max:50',
            'value_type' => 'required|string|in:PRICE/KG, PRICE/PIECE, ORIGINAL PLACE, AVAILABLE, QUANTITY, EXPIRATION DATE, WEIGHT ',
        ]);
    }

    private function validator_update(array $data)
    {
        return Validator::make($data, [
            'name' => 'nullable|string|max:50',
            'value_type' => 'nullable|string|in:PRICE/KG, PRICE/PIECE, ORIGINAL PLACE, AVAILABLE, QUANTITY, EXPIRATION DATE, WEIGHT ',
        ]);
    }



}
