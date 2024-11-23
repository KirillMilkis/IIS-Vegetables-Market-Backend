<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use Illuminate\Support\Str;
use App\Models\Attribute;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\SelfHarvesting;

class ProductController extends Controller
{   

    /**
     * Display a listing of the products.
     * Category is can be specified to filter products by category.
     * The request in this case should contain all product from the specified category and its from category descendants.
     * 
     * @param Request $request
     * @return ProductResource
     */
    public function index(Request $request)
    {

        $categoryId = $request->input('category_id');
        $query = Product::query();

        if ($categoryId) {
            $category = Category::find($categoryId);

            if (!$category) {
                return response()->json(['message' => 'Category not found', 'code' => 404], 404);
            }

            // Use the method from CategoryController to get all descendant category ids.
            $categoryIds = app('App\Http\Controllers\CategoryController')
            ->getDescendantCategoryIds($categoryId);
            $query->whereIn('category_id', $categoryIds);
        } 

        $products = $query->get();

        if ($products->isEmpty()) {
            return response()->json(['message' => 'No products found', 'code' => 204], 204);
        }
      
        return ProductResource::collection($products);
    }

    /**
     * Display a listing of the products that farmer is selling.
     * 
     * @param int $farmerId
     * @return ProductResource
     */
    public function getProductsByFarmer($farmerId)
    {
    
        $user = User::find($farmerId);

        if (!$user) {
            return response()->json(['message' => 'Farmer not found'], 404);
        }
        
        $products = $user->products; 
        
        return ProductResource::collection($products);
    }

    /**
     * Display a listing of the products that are available for self-harvesting.
     * 
     * @param int $selfHarvestingId
     * @return ProductResource
     */
    public function getProductsBySelfHarvesting($selfHarvestingId)
    {
    
        $selfHarvesting = SelfHarvesting::find($selfHarvestingId);

        if (!$selfHarvesting) {
            return response()->json(['message' => 'Farmer not found'], 404);
        }

        $products = $selfHarvesting->product()->get(); 

        return ProductResource::collection($products);
    }


    /**
     * Filter products by category, attributes and name.
     * Attributes are passed as an array of objects with attribute_id and value that product should have.
     * 
     * @param Request $request
     * @return ProductResource
     */
    public function filter(Request $request)
    {
        $query = Product::query();

        $categoryId = $request->input('category_id'); // Filter by category.
        $filters = $request->input('filters', []);  // Filters by attributes.
        $search = $request->input('name_like'); // Search by name.

        // If category_id is specified, we get all products from the specified category and its descendants.
        if ($categoryId) {
            $categoryIds = app('App\Http\Controllers\CategoryController')
            ->getDescendantCategoryIds($categoryId);
            $query->whereIn('category_id', $categoryIds);
        }

        // If filters are specified, we filter products by attributes.
        if (!empty($filters) && is_array($filters)) {
            foreach ($filters as $attributeId => $data) {
                $attribute = Attribute::find($attributeId);

                // Filter by attribute that contatin price in kg.
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
                // Filter by attribute that contatin price per piece.
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
                // Filter by attribute that contatin place.
                if ($attribute && $attribute->value_type === 'PLACE') {
                    if (isset($data['value']) && is_array($data['value'])) {
                        $values = $data['value'];
                        $query->whereHas('attribute_values', function($q) use ($attributeId, $values) {
                            $q->where('attribute_id', $attributeId)
                              ->whereIn('value', $values);
                        });
                    }
                }
                // Filter by attribute that contatin expiration date.
                if ($attribute && $attribute->value_type === 'DATE') {
                    if (isset($data['value']) && is_array($data['value'])) {
                        $value = $data['value'];         
                        $query->whereHas('attribute_values', function ($q) use ($attributeId, $value) {
                            // Format date that is stored in the database and compare it with the value.
                            $q->where('attribute_id', $attributeId)
                            ->whereRaw('STR_TO_DATE(value, "%Y-%m-%d") >= ?', [$value]); 
                        });
                    }
                }
                // Filter by attribute that contatin quantity.
                if ($attribute && $attribute->value_type === 'QUANTITY') {
                    if (isset($data['value'])) {
                        $value = $data['value'];
                        $query->whereHas('attribute_values', function($q) use ($attributeId, $value) {
                            $q->where('attribute_id', $attributeId)
                            ->whereBetween('value', [$value, 100000000]);
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


    /**
     * Store a newly created product in storage.
     * 
     * @param Request $request
     * @return ProductResource
     */
    public function store(Request $request)
    {
        $authUser = Auth::user();

        $input = collect($request->all())->mapWithKeys(function ($value, $key) {
            return [Str::snake($key) => $value];
        })->toArray();


        $validator = $this->validator_create($input);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(), 
                'code' => 400], 400);
        }   

        $category = Category::find($input['category_id']);

        if (!$category) {
            return response()->json([
                'message' => 'Category not found',
                'code' => 404
            ], 404);
        }

        // Get all attributes of the category where the product is created. 
        $attributes = $category->category_attributes()
        ->get();
        
        $attributeValues = $request->input('attribute_values');
        // Check if it is enough attribute values specified for the category where the product is created.
        if (!$attributeValues || count($attributeValues) < $attributes->count()) {
            return response()->json([
                'message' => 'Not all required attribute values provided',
                'code' => 400
            ], 400);
        }
    
        // Check if all required attributes have values.
        foreach ($attributes as $categoryAttribute) {
            $attribute = $categoryAttribute->attribute; // Get attribute from category_attribute.
            $isRequired = $categoryAttribute->is_required; // Get is_required from category_attribute.
            
            // Get the value of the attribute from the request.
            $attributeValue = collect($attributeValues)->firstWhere('attribute_id', $attribute->id);
            // If attribute does not have a value from the request but it is required, return an error.
            if ($isRequired &&  empty($attributeValue['value'])) {
                return response()->json([
                    'message' => "Value for attribute {$attribute->name} is required",
                    'code' => 400
                ], 400);
            }

            
        }
    
        $product = Product::create($input);
    
        // Create a attribute values records for the product.
        app('App\Http\Controllers\AttributeValueController')
        ->createAttributeValuesFromProductController($product->id, $attributeValues);


        return response()->json([
            'message' => 'Product created successfully',
            'product' => $product,
            'code' => 201
        ], 201);
        
    }

    /**
     * Display the specified product.
     * 
     * @param int $id
     * @return ProductResource
     */
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

    /**
     * Update the specified product in storage.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request)
    {
        $authUser = Auth::user();

        $product = Product::find($request->route('id'));

        if (!$product) {
            return response()->json([
                'message' => 'Product not found',
                'code' => 404,
            ], 404);
        }

        // If the user is not the owner of the product, return an error.
        if ($product->farmer_id != $authUser->id) {
            return response()->json([
                'message' => "You don't have access to change other user's products",
                'code' => 403,
            ], 403);
        }

        $category = Category::find($request->category_id);
        if (!$category) {
            return response()->json([
                'message' => 'Invalid category',
                'code' => 400,
            ], 400);
        }

        $input = collect($request->all())->mapWithKeys(function ($value, $key) {
            return [Str::snake($key) => $value];
        })->toArray();

        $validator = $this->validator_update($input);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'code' => 400,
            ], 400);
        }


        if (!$product->update($input)) {
            return response()->json([
                'message' => 'Product not updated',
                'code' => 500,
            ], 500);
        }

        // Update the attribute values of the product.
        $attributeValues = $request->input('attribute_values', []);

        // Take all existing attributes of the product that product have with specified attribute values.
        $existingAttributes = $product->attribute_values->pluck('attribute_id')->toArray();

        // Check if all existing attributes have values.
        $passedAttributeIds = collect($attributeValues)->pluck('attribute_id')->toArray();
        $missingAttributes = array_diff($existingAttributes, $passedAttributeIds);

        // If not all existing attributes have values, return an error.
        if (!empty($missingAttributes)) {
            return response()->json([
                'message' => 'Not all attribute values are provided for existing attributes',
                'missing_attributes' => $missingAttributes,
                'code' => 400
            ], 400);
        }

        foreach ($attributeValues as $attributeValue) {
            // Update every attribute value of the product.
            if($attributeValue)
            app('App\Http\Controllers\AttributeValueController')
            ->updateAttributeValuesFromProductController($product->id, $attributeValue);
        }

        return response()->json([
            'message' => 'Product updated',
            'code' => 200,
        ], 200);
    }

    /**
     * Remove the specified product from storage.
     * 
     * @param int $id
     * @return JsonResponse
     */
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

    /**
     * Validate the input for creating a new product.
     * 
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    private function validator_create($data){
        return Validator::make($data, [
            'name' => 'required|max:32',
            'description' => 'required|max:255',
            'farmer_id' => 'required|integer|exists:users,id',
            'image_root' => 'nullable|max:255',
            'category_id' => 'required|integer|exists:categories,id',
        ]);
    }

    /**
     * Validate the input for updating a product.
     * 
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    private function validator_update($data){
        return Validator::make($data, [
            'name' => 'nullable|max:50',
            'description' => 'nullable|max:255',
            'farmer_id' => 'nullable|integer|exists:users,id',
            'image_root' => 'nullable|max:255',
            'category_id' => 'nullable|integer|exists:categories,id',
        ]);

    }
}
