<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Attribute;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class ProductController extends Controller
{
    public function index(Request $request)
    {

        // $sessionId = Session::getId();

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

        // Session::put('filtered_products', $products);
      
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

                if ($attribute && $attribute->value_type === 'DATE') {
                    if (isset($data['value']) && is_array($data['value'])) {
                        $value = $data['value'];  // Значение, с которым будет сравнение
                        
                        $query->whereHas('attribute_values', function ($q) use ($attributeId, $value) {
                            // Преобразуем значение из базы данных и входное значение в формат YYYY-MM-DD для корректного сравнения
                            $q->where('attribute_id', $attributeId)
                            ->whereRaw('STR_TO_DATE(value, "%Y-%m-%d") >= ?', [$date]);  // Преобразование строки в дату и сравнение
                        });
                    }
                }

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
        $authUser = Auth::user();

        $input = collect($request->all())->mapWithKeys(function ($value, $key) {
            return [Str::snake($key) => $value];
        })->toArray();

        // $input['farmer_id'] = $farmerId;

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

        $attributes = $category->category_attributes()
        ->with('attribute')  // Eager load the Attribute model
        ->get()
        ->pluck('attribute');  // Get only the related Attribute models

        $attributeValues = $request->input('attribute_values');

        if (!$attributeValues || count($attributeValues) < $attributes->count()) {
            return response()->json([
                'message' => 'Not all required attribute values provided',
                'code' => 400
            ], 400);
        }
    
        // Проверяем, что для каждого обязательного атрибута есть значение
        foreach ($attributes as $categoryAttribute) {
            $attribute = $categoryAttribute->attribute; // Достаём модель Attribute
            $isRequired = $categoryAttribute->is_required; // Достаём флаг обязательности из category_attribute
        
            $attributeValue = collect($attributeValues)->firstWhere('attribute_id', $attribute->id);
            
            if (!$attributeValue){
                return response()->json([
                    'message' => "All attributes Value must be sent, but may have value null",
                    'code' => 400
                ], 400);
            }
            // Если атрибут обязательный и значение отсутствует или пустое, возвращаем ошибку
            if ($isRequired &&  empty($attributeValue['value'])) {
                return response()->json([
                    'message' => "Value for attribute {$attribute->name} is required",
                    'code' => 400
                ], 400);
            }

            
        }
    
        // Создаем продукт
        $product = Product::create($input);
    
        // Создаем записи в таблице AttributeValue
        app('App\Http\Controllers\AttributeValueController')
        ->createAttributeValuesFromProductController($product->id, $attributeValues);


        return response()->json([
            'message' => 'Product created successfully',
            'product' => $product,
            'code' => 201
        ], 201);
        
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
        $authUser = Auth::user();

        // Поиск продукта
        $product = Product::find($request->route('id'));

        if (!$product) {
            return response()->json([
                'message' => 'Product not found',
                'code' => 404,
            ], 404);
        }

        // Проверка прав доступа
        if ($product->farmer_id != $authUser->id) {
            return response()->json([
                'message' => "You don't have access to change other user's products",
                'code' => 403,
            ], 403);
        }

        // Проверка категории
        $category = Category::find($request->category_id);
        if (!$category) {
            return response()->json([
                'message' => 'Invalid category',
                'code' => 400,
            ], 400);
        }

        // Преобразование данных из snake_case
        $input = collect($request->all())->mapWithKeys(function ($value, $key) {
            return [Str::snake($key) => $value];
        })->toArray();

        // Валидация данных
        $validator = $this->validator_update($input);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'code' => 400,
            ], 400);
        }

        // Обновление основного продукта
        if (!$product->update($input)) {
            return response()->json([
                'message' => 'Product not updated',
                'code' => 500,
            ], 500);
        }

        // Обновление значений атрибутов
        $attributeValues = $request->input('attribute_values', []);

        $existingAttributes = $product->attribute_values->pluck('attribute_id')->toArray();

        // Проверяем, что все существующие атрибуты продукта имеют переданные значения
        $passedAttributeIds = collect($attributeValues)->pluck('attribute_id')->toArray();
        $missingAttributes = array_diff($existingAttributes, $passedAttributeIds);

        if (!empty($missingAttributes)) {
            return response()->json([
                'message' => 'Not all attribute values are provided for existing attributes',
                'missing_attributes' => $missingAttributes,
                'code' => 400
            ], 400);
        }

        foreach ($attributeValues as $attributeValue) {
            // Вызов метода AttributeValueController
            if($attributeValue)
            app('App\Http\Controllers\AttributeValueController')
            ->updateAttributeValuesFromProductController($product->id, $attributeValue);
        }

        return response()->json([
            'message' => 'Product updated',
            'code' => 200,
        ], 200);
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
            'farmer_id' => 'required|integer|exists:users,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'category_id' => 'required|integer|exists:categories,id',
        ]);
    }

    private function validator_update($data){
        return Validator::make($data, [
            'name' => 'nullable|max:32',
            'description' => 'nullable|max:255',
            'farmer_id' => 'nullable|integer|exists:users,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'category_id' => 'nullable|integer|exists:categories,id',
        ]);

    }
}
