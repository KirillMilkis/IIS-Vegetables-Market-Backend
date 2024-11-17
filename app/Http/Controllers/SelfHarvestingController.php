<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SelfHarvesting;
use App\Http\Resources\SelfHarvestingCollection;
use App\Http\Resources\SelfHarvestingResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SelfHarvestingController extends Controller
{
    public function index(Request $request)
    {
        $selfHarvesting = SelfHarvesting::all();
        return new SelfHarvestingCollection($selfHarvesting);
        
    }

    public function store(Request $request)
    {
        $input = collect($request->all())->mapWithKeys(function ($value, $key) {
            return [Str::snake($key) => $value];
        })->toArray();
        
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
            'farmer_id' => 'required|numeric|exists: user,id',
            'product_id' => 'required|numeric|exists: product,id',
        ]);
    }
}
