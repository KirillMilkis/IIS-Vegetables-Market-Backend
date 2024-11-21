<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\AttributeValue; 
use App\Http\Resources\AttributeValueCollection;
use App\Http\Resources\AttributeValueResource;
use Illuminate\Support\Facades\Validator;
use App\Models\Attribute;



class AttributeValueController extends Controller
{


    public function index()
    {
        
        $attributeValues = AttributeValue::all();

        if ($attributeValues->isEmpty()) {
            return response()->json(['message' => 'No attribute values found', 'code' => 204], 204);
        }
        
        return new AttributeValueCollection($attributeValues);
    }


    public function getByAttribute(Request $request)
    {
        $attributeId = $request->input('attribute_id');

        if (!$attributeId) {
            return response()->json(['message' => 'Should be specified attributeId', 'code' => 400], 400);
        }

        $attribute = Attribute::find($attributeId);


        // if ($attribute->value_type !== 'PLACE' || !$attribute) {
        //     return response()->json(['message' => 'Attribute is not of type PLACE', 'code' => 400], 400);
        // }

        $attributeValues = AttributeValue::where('attribute_id', $attributeId)->get();

        if ($attributeValues->isEmpty()) {
            return response()->json(['message' => 'No attribute values found', 'code' => 204], 204);
        }

        return new AttributeValueCollection($attributeValues);
    }

    public function getByProduct(Request $request)
    {
        $productId = $request->input('product_id');
        
        if(!$productId){
            return response()->json(['message' => 'Product not found', 'code' => 400], 400);
        } 
        $attributeValues = AttributeValue::where('product_id', $productId)->get();

        if ($attributeValues->isEmpty()) {
            return response()->json(['message' => 'No attribute values found', 'code' => 204], 204);
        }
        
        return new AttributeValueCollection($attributeValues);
    }


    public function getByAttributeProduct(Request $request)
    {
        $attributeId = $request->input('attribute_id');
        $productId = $request->input('product_id');

        if(!$attributeId || !$productId){
            return response()->json(['message' => 'Should specify product_id and attribute_id', 'code' => 400], 400);
        }

        $attributeValues = AttributeValue::where('product_id', $productId)
                                     ->where('attribute_id', $attributeId)
                                     ->get();

        if ($attributeValues->isEmpty()) {
            return response()->json(['message' => 'No attribute values found', 'code' => 204], 204);
        }

        
        return new AttributeValueCollection($attributeValues);
    }



    public function store(Request $request)
    {
        $input = collect($request -> all())->mapWithKeys(function ($value, $key) {
            return [Str::snake($key) => $value];
        })->toArray();

        $validator = $this->validator_create($input);

        if ($validator->fails()) {
            return response()-> json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(), 
                'code' => 400]);
        }

        if (AttributeValue::create($input)) {
            return response()->json(['message' => 'Attribute value created',
                'code' => 201], 
                201);
        } else {
            return response()->json(['message' => 'Attribute value not created',
                'code' => 500], 
                500);
        }
    }

    public function show($id)
    {
        $attributeValue = AttributeValue::find($id);

        if (!$attributeValue) {
            return response()->json(['message' => 'Attribute value not found',
                'code' => 404],
                404);
        }

        return new AttributeValueResource($attributeValue);
    }

    public function update(Request $request, $id){

        $attributeValue = AttributeValue::find($id);

        if (!$attributeValue) {
            return response()->json(['message' => 'Category not found',
                'code' => 404],
                404);
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

        if ($attributeValue->update($input)) {
            return response()->json(['message' => 'Category updated',
                'code' => 200],
                200);
        } else {
            return response()->json(['message' => 'Category not updated',
                'code' => 500],
                500);
        }


    }

    public function updateAttributeValuesFromProductController($productId, $attributeValueData)
    {
        // Проверка на существование записи AttributeValue
        $attributeValue = AttributeValue::where('product_id', $productId)
            ->where('attribute_id', $attributeValueData['attribute_id'])
            ->first();

        if ($attributeValue) {
            // Обновляем существующее значение
            $attributeValue->update([
                'value' => $attributeValueData['value'],
            ]);
        } else {
            // Создаем новую запись, если её нет
            AttributeValue::create([
                'product_id' => $productId,
                'attribute_id' => $attributeValueData['attribute_id'],
                'value' => $attributeValueData['value'],
            ]);
        }
    }

    public function createAttributeValuesFromProductController($productId, array $attributeValues)
    {
        foreach ($attributeValues as $value) {
            // Проверяем, что данные корректны
            if (isset($value['attribute_id'], $value['value'])) {
                AttributeValue::create([
                    'product_id' => $productId,
                    'attribute_id' => $value['attribute_id'],
                    'value' => $value['value'],
                ]);
            } else {
                // Вы можете добавить обработку ошибок или пропустить некорректные данные
                \Log::warning('Invalid attribute value data', ['data' => $value]);
            }
        }
    }


    public function destroy($id)
    {
        $attributeValue = AttributeValue::find($id);

        if (!$attributeValue) {
            return response()->json(['message' => 'Attribute value not found',
                'code' => 404],
                404);
        }

        if ($attributeValue->delete()) {
            return response()->json(['message' => 'Attribute value deleted',
                'code' => 200],
                200);
        } else {
            return response()->json(['message' => 'Attribute value not deleted',
                'code' => 500],
                500);
        }
    }

    private function validator_create($input)
    {
        return Validator::make($input, [
            'attribute_id' => 'required|integer|exists:attributes,id',
            'product_id' => 'required|integer|exists:products,id',
            'value' => 'required|string|max:50',
        ]);
    }

    private function validator_update($input)
    {
        return Validator::make($input, [
            'attribute_id' => 'nullable|integer|exists:attributes,id',
            'product_id' => 'nullable|integer|exists:products,id',
            'value' => 'nullable|string|max:50',
        ]);
    }

}
