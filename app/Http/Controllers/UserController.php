<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Product;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\UserResource;
use App\Http\Resources\UserCollection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Models\SelfHarvesting;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     *
     * @return UserCollection
     */
    public function index()
    {   
        $users = User::all();
        return new UserCollection($users);
        
    }

    /**
     * Get farmers who have products.
     *
     * @return UserCollection
     */
    public function getFarmers()
    {
        
        $users = User::has('products')
                 ->select('id', 'firstname', 'lastname', 'email')
                 ->get();
        return new UserCollection($users);
    }

    /**
     * Get the farmer who has a specific product.
     *
     * @param Request $request
     * @return UserResource
     */
    public function getFarmerByProductId(Request $request)
    {

        $productId = $request->input('product_id');
    
        if (!$productId) {
            return response()->json([
                'message' => 'You must specify product_id','code' => 400], 400);
        }


        $product = Product::find($productId);

    
        if (!$product) {
            return response()->json([
                'message' => 'Product not found', 'code' => 404], 404);
        }

        $userId = $product->farmer_id;
        $farmer = User::find($userId);

    
        if (!$farmer) {
            return response()->json([
                'message' => 'Farmer not found for this product','code' => 404], 404);
        }

        
        return new UserResource($farmer);
    }

    /**
     * Store a newly created user in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {

        $input = collect($request->all())->mapWithKeys(function ($value, $key) {
            return [Str::snake($key) => $value];
        })->toArray();

        // Admin cannot be created via API, only via seeder.
        if ($input['role'] != 'moderator' && $input['role'] != 'reg_user'){
            return response()-> json([
                'message' => 'Cannot create admin',
                'errors' => [], 
                'code' => 400], 400);
        }

        $validator = $this->validator_create($input);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(), 
                'code' => 400], 400);
        }

        // Hash the password
        $input['password'] = bcrypt($input['password']);

        if (User::create($input)) {
            return response()->json(['message' => 'User created',
                'code' => 201], 
                201);
        } else {
            return response()->json(['message' => 'User not created',
                'code' => 200], 
                200);
        }
    }

    /**
     *  Display the specified user.
     *
     * @param string $id
     * @return UserResource
     */
    public function show(string $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found',
                'code' => 404], 
                404);
        }

        return new UserResource($user);
    }

    /**
     * Update the specified user in storage.
     *
     * @param Request $request
     * @return UserResource
     */
    public function update(Request $request)
    {
        $user = User::find($request->id);

        if (!$user) {
            return response()->json(['message' => 'User not found',
                'code' => 404], 
                404);
        }

        $authUser = Auth::user(); 

        // User can modify only his own account. Admin can modify any account.
        if ($authUser->role === 'reg_user' || $authUser->role === 'moderator') {
            if ($authUser->id !== $user->id) {
                return response()->json(['message' => 'You can only modify your own account', 'code' => 403], 403);
            }
        } 

        $input = collect($request->all())->mapWithKeys(function ($value, $key) {
            return [Str::snake($key) => $value];
        })->toArray();

        unset($input['id']);

        $validator =  $this->validator_update($input, $user);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed','errors' => $validator->errors(),'code' => 400], 400);
        }

        if (isset($input['password'])) {
            $input['password'] = bcrypt($input['password']);
        }

        if ($user->update($input)) {
            return new UserResource($user);
        } else {
            return response()->json(['message' => 'User not updated',
                'code' => 414], 
                414);
        }


    }

    /**
     * Attach a self harvesting to the user.
     * Only for purpose that user wanna visit the self harvesting.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function attachSelfHarvesting(Request $request)
    {
 
        $authUser = Auth::user(); 

    
        $selfHarvestingId = $request->input('self_harvesting_id');


        if (empty($selfHarvestingId)) {
            return response()->json(['message' => 'Invalid or missing self_harvesting_id', 'code' => 400], 400);
        }


        $selfHarvestingExists = SelfHarvesting::where('id', $selfHarvestingId)->exists();

        if (!$selfHarvestingExists) {
            return response()->json(['message' => 'SelfHarvesting not found', 'code' => 404], 404);
        }
  
        $authUser->self_harvestings_visits()->syncWithoutDetaching([$selfHarvestingId]);

        return response()->json([
            'message' => 'SelfHarvesting(s) attached successfully',
            'code' => 200,
        ], 200);
    }

    /**
     * Detach a self harvesting from the user.
     * Only for purpose that user wanna cancel the visit to the self harvesting.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function detachSelfHarvesting(Request $request)
    {
        
        $authUser = Auth::user(); 

        
        $selfHarvestingId = $request->input('self_harvesting_id');

        
        if (empty($selfHarvestingId)) {
            return response()->json(['message' => 'Invalid or missing self_harvesting_id', 'code' => 400], 400);
        }

        
        $selfHarvestingExists = SelfHarvesting::where('id', $selfHarvestingId)->exists();

        if (!$selfHarvestingExists) {
            return response()->json(['message' => 'SelfHarvesting not found', 'code' => 404], 404);
        }

        
        $authUser->self_harvestings_visits()->detach($selfHarvestingId);

        return response()->json([
            'message' => 'SelfHarvesting(s) detached successfully',
            'code' => 200,
        ], 200);
    }

    /**
     * Remove the specified user from storage.
     *
     * @param string $id
     * @return JsonResponse
     */
    public function delete(string $id)
    {

        $userInitiator = Auth::user();

        if ($userInitiator->role != 'admin') {
            return response()->json([
                'message' => 'You do not have permission to delete this post',
                'code' => 403 
            ]);
        }

        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found',
                'code' => 404], 
                404);
        }
        if ($user->role == 'admin') {
            return response()->json(['message' => 'Cannot delete admin',
                'code' => 403], 
                403);
        }

        if ($user->delete()) {
            return response()->json(['message' => 'User deleted',
                'code' => 200], 
                200);
        } else {
            return response()->json(['message' => 'User not deleted',
                'code' => 500], 
                500);
        }
    }

    /**
     * Validate the input for creating a user.
     *
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    private function validator_create($data){
        return Validator::make($data, [
            'username' => 'required|max:20|unique:users',
            'firstname' => 'required|max:50',
            'lastname' => 'required|max:50',
            'address' => 'string|max:100',
            'password' => 'required|string|min:8|max:32',
            'email' => 'string|max:50|unique:users',
            'phone' => 'string|max:50',
            'role' => 'required|string|in:reg_user,moderator,admin',
        ]);
    }
    
    /**
     * Validate the input for updating a user.
     *
     * @param array $data
     * @param User $user
     * @return \Illuminate\Contracts\Validation\Validator
     */
    private function validator_update($data, $user){
        return Validator::make($data, [
            'username' => ['required', 'string', 'max:20', Rule::unique('users')->ignore($user->id),],
            'firstname' => 'required|string|max:32',
            'lastname' => 'required|string|max:32',
            'address' => 'nullable|string|max:100',
            'password' => 'nullable|string|min:8|max:32', // Для обновления пароля
            'email' => ['nullable','string','max:255','email',Rule::unique('users')->ignore($user->id),],
            'phone' => 'nullable|string|max:255',
            'role' => 'nullable|string|in:reg_user,moderator,admin',
        ]);
    }
}
