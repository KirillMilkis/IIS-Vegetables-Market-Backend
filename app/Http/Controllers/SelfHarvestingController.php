<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SelfHarvesting;
use App\Http\Resources\SelfHarvestingCollection;
use App\Http\Resources\SelfHarvestingResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Models\User; 
use App\Models\Product; 

class SelfHarvestingController extends Controller
{   
    /**
     * Display a listing of the self harvestings.
     *
     * @return SelfHarvestingCollection
     */
    public function index(Request $request)
    {
        $selfHarvesting = SelfHarvesting::all();
        return new SelfHarvestingCollection($selfHarvesting);
        
    }

    /**
     * Get self harvestings that are related to a user.
     * Only self harvestings tah users wanna visit.
     *
     * @param int $userId
     * @return SelfHarvestingCollection
     */
    public function getUserSelfHarvestings($userId)
    {
        
        $user = User::find($userId);

        if (!$user) {
            return response()->json(['message' => 'User not found', 'code' => 404], 404);
        }

        // Get related SelfHarvesting records that user wanna visit.
        $selfHarvestings = $user->self_harvestings_visits()->get();

        if ($selfHarvestings->isEmpty()) {
            return response()->json(['message' => 'No SelfHarvesting records found for this user', 'code' => 204], 204);
        }

        $selfHarvestings->map(function ($item) {
            $item->product_name = $item->product->name;
            return $item;
        });

        return new SelfHarvestingCollection($selfHarvestings);

    }

    /**
     * Get self harvestings that are related to a farmer.
     * Only self harvestings that farmer has planned for his products.
     *
     * @param int $userId
     * @return SelfHarvestingCollection
     */
    public function getFarmerSelfHarvestings($userId)
    {
        $user = User::find($userId);

        if (!$user) {
            return response()->json(['message' => 'User not found', 'code' => 404], 404);
        }

        // Get only SelfHarvesting records that farmer has planned for his products.
        $selfHarvestings = $user->self_harvestings_planned()->get();

        if ($selfHarvestings->isEmpty()) {
            return response()->json(['message' => 'No SelfHarvesting records found for this user', 'code' => 204], 204);
        }

        $selfHarvestings->map(function ($item) {
            $item->product_name = $item->product->name;
            return $item;
        });

        return new SelfHarvestingCollection($selfHarvestings);

    }

    /**
     * Get self harvestings that are related to a product.
     * Product may have many self harvestings.
     *
     * @param int $productId
     * @return SelfHarvestingCollection
     */
    public function getProductSelfHarvestings($productId)
    {
        $product = Product::find($productId);

        if (!$product) {
            return response()->json(['message' => 'Product not found', 'code' => 404], 404);
        }

        $selfHarvestings = $product->self_harvestings()->get();

        if ($selfHarvestings->isEmpty()) {
            return response()->json(['message' => 'No SelfHarvesting records found for this product', 'code' => 204], 204);
        }

        $selfHarvestings->map(function ($item) {
            $item->product_name = $item->product->name;
            return $item;
        });

        return new SelfHarvestingCollection($selfHarvestings);
    }
    
    /**
     * Store a newly created self harvesting in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $input = collect($request->all())->mapWithKeys(function ($value, $key) {
            return [Str::snake($key) => $value];
        })->toArray();

        $product = Product::find($request->input('product_id'));

        if (!$product) {
            return response()->json(['message' => 'Product not found', 'code' => 404], 404);
        }

        $user = Auth::user();
        // Check if the user is the owner of the product. Only owner can create an event for his product.
        if ($product->farmer_id != $user['id']) {
            return response()->json(['message' => 'You can only create an event for your own product', 'code' => 403], 403);
        }

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

    /**
     * Display the specified self harvesting.
     *
     * @param int $id
     * @return SelfHarvestingResource
     */
    public function show($id)
    {
        $selfHarvesting = SelfHarvesting::find($id);

        if (!$selfHarvesting) {
            return response()->json(['message' => 'SelfHarvesting not found',
                'code' => 404],
                404);
        }

        $selfHarvesting->product_name = $selfHarvesting->product->name ?? null;

        return new SelfHarvestingResource($selfHarvesting);
    }

    /**
     * Update the specified self harvesting in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * Remove the specified self harvesting from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * Validate the request data for creating a new self harvesting.
     *
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public function validation_create($data){
        return Validator::make($data, [
            'name' => 'required|string|max:50',
            'description' => 'required|string|max:255',
            'dateTime' => 'required|timestamp',
            'location' => 'required|string|max:50',
            'farmer_id' => 'required|numeric|exists: users,id',
            'product_id' => 'required|numeric|exists: products,id',
        ]);
    }

    /**
     * Validate the request data for updating a self harvesting.
     *
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public function validation_update($data){
        return Validator::make($data, [
            'name' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:255',
            'dateTime' => 'nullable|timestamp',
            'location' => 'nullable|string|max:50',
            'farmer_id' => 'nullable|numeric|exists: users,id',
            'product_id' => 'nullable|numeric|exists: products,id',
        ]);
    }
}
