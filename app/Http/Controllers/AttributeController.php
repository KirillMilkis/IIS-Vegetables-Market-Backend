<?php

namespace App\Http\Controllers;

use App\Http\Resources\AttributeCollection;
use App\Http\Resources\AttributeResource;
use Illuminate\Http\Request;
use App\Models\Attribute;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\Product;

class AttributeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return AttributeCollection
     */
    public function index()
    {
        $attributes = Attribute::all();

        return new AttributeCollection($attributes);

    }

    /**
     * Get attributes that are related to a categoryId or a productId. But not both.
     * 
     *
     * @param Request $request
     * @return AttributeCollection
     */
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
        
        // If a category_id is specified, we get the attributes that are related to that category.
        // If a product_id is specified, we get the attributes that are related to that product.
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
        
        // For convenience, we add the is_required flag to each attribute. 
        // This flag is located in the pivot table category_attribute.
        $attributes->each(function ($attribute) use ($categoryId, $productId) {
            if ($categoryId) {
                $categoryAttribute = $attribute->attribute_categories->firstWhere('category_id', $categoryId);
            } 
        
            else if ($productId) {
                $product = Product::find($productId);
                $productCategory = $product->category->first();
                $categoryAttribute = $attribute->attribute_categories->firstWhere('category_id', $productCategory->id);
            }
        
            if ($categoryAttribute) {
                $attribute->is_required = $categoryAttribute->is_required;
            } else {
                $attribute->is_required = false; 
            }
        });


        return new AttributeCollection($attributes);
        
    }


    /**
     * Store a newly created attribute in storage.
     * But our implementation in frontend does not allow to create new attribute.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

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

    /**
     * Display the specified attribute.
     *
     * @param int $id
     * @return AttributeResource
     */
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

    /**
     * Update the specified attribute in storage.
     * But our implementation in frontend does not allow to update attribute.
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * Remove the specified attribute from storage.
     * But our implementation in frontend does not allow to delete attribute.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */

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

    /**
     * Validate the request data for creating a new attribute.
     *
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    private function validator_create(array $data)
    {
        return Validator::make($data, [
            'name' => 'required|string|max:50',
            'value_type' => 'required|string|in:PRICE/KG, PRICE/PIECE, ORIGINAL PLACE, AVAILABLE, QUANTITY, EXPIRATION DATE, WEIGHT ',
        ]);
    }


    /**
     * Validate the request data for updating an attribute.
     *
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    private function validator_update(array $data)
    {
        return Validator::make($data, [
            'name' => 'nullable|string|max:50',
            'value_type' => 'nullable|string|in:PRICE/KG, PRICE/PIECE, ORIGINAL PLACE, AVAILABLE, QUANTITY, EXPIRATION DATE, WEIGHT ',
        ]);
    }



}
