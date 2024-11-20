<?php

namespace App\Http\Controllers;

use App\Http\Resources\AttributeCollection;
use App\Http\Resources\AttributeResource;
use Illuminate\Http\Request;
use App\Models\Attribute;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\Category;

class AttributeController extends Controller
{
    public function index(Request $request)
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

            $parentCategoryIds = $this->getParentsCategoryIds($categoryId);

            $query->whereHas('attribute_categories', function($q) use ($parentCategoryIds) {
                $q->whereIn('category_id', $parentCategoryIds); // Фильтруем по родительским категориям
            });
        } else if ($productId) {

            $query->whereHas('attribute_values', function($q) use ($productId) {
                $q->where('product_id', $productId); // предполагаем, что в attribute_values есть product_id
            });

        }

        $attributes = $query->get();

        if ($attributes->isEmpty()) {
            return response()->json(['message' => 'No attributes found', 'code' => 204], 204);
        }


        return new AttributeCollection($attributes);
        
    }



    public function getParentsCategoryIds($categoryId)
    {
        $categoryIds = [];
        $category = Category::find($categoryId);

        while ($category) {
            $categoryIds[] = $category->id;
            $category = $category->parent_id ? Category::find($category->parent_id) : null;
        }

        return $categoryIds;
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

        $validator = $this->validator_create($input);

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



}
