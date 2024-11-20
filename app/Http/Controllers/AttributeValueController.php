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


    public function indexPlaces(Request $request)
    {
        $attributeId = $request->input('attribute_id');

        if ($attributeId) {
            $attribute = Attribute::find($attributeId);


            if ($attribute->value_type !== 'PLACE') {
                return response()->json(['message' => 'Attribute is not of type PLACE', 'code' => 400], 400);
            }

            $attributeValues = AttributeValue::where('attribute_id', $attributeId)->get();
        } else {
            $attributeValues = AttributeValue::all();
        }


        if ($attributeValues->isEmpty()) {
            return response()->json(['message' => 'No attribute values found', 'code' => 204], 204);
        }

        return new AttributeValueCollection($attributeValues);
    }

    public function index(Request $request)
    {
        $productId = $request->input('product_id');
        
        if($productId){
            $attributeValues = AttributeValue::where('product_id', $productId)->get();
        } else{
            $attributeValues = AttributeValue::all();
        }

        if ($attributeValues->isEmpty()) {
            return response()->json(['message' => 'No attribute values found', 'code' => 204], 204);
        }
        
        return new AttributeValueCollection($attributeValues);
    }

    public function indexAttributeProduct(Request $request)
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

    public function update(Request $request){

        $attributeValue = AttributeValue::find($request->id);

        if (!$attributeValue) {
            return response()->json(['message' => 'Category not found',
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

}
