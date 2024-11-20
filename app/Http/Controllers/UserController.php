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
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {   
        $users = User::all();
        return new UserCollection($users);
        
    }

    /**
     * Display a listing of the users with special roles.
     */
    public function index_specified(string $role)
    {
    
        $users = User::where('role', $role)->get();

        return new UserCollection($users);
    } 

    public function getUsersWithProducts()
    {
        
        $users = User::has('products')
                 ->select('id', 'firstname', 'lastname', 'email')
                 ->get();
        return new UserCollection($users);
    }

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
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {


        $input = collect($request->all())->mapWithKeys(function ($value, $key) {
            return [Str::snake($key) => $value];
        })->toArray();

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
     * Display the specified resource.
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
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $user = User::find($request->id);
        $authUser = Auth::user(); 

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

        if (!$user) {
            return response()->json(['message' => 'User not found',
                'code' => 404], 
                404);
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
     * Remove the specified resource from storage.
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

    private function validator_create($data){
        return Validator::make($data, [
            'username' => 'required|max:20|unique:users',
            'firstname' => 'required|max:32',
            'lastname' => 'required|max:32',
            'address' => 'string|max:100',
            'password' => 'required|string|min:8|max:32',
            'email' => 'string|max:255|unique:users',
            'phone' => 'string|max:255',
            'role' => 'required|string|in:reg_user,moderator,admin',
        ]);
    }

    private function validator_update($data, $user){
        return Validator::make($data, [
            'username' => ['required', 'string', 'max:20', Rule::unique('users')->ignore($user->id),],
            'firstname' => 'required|string|max:32',
            'lastname' => 'required|string|max:32',
            'address' => 'required|string|max:100',
            'password' => 'nullable|string|min:8|max:32', // Для обновления пароля
            'email' => ['nullable','string','max:255','email',Rule::unique('users')->ignore($user->id),],
            'phone' => 'nullable|string|max:255',
            'role' => 'nullable|string|in:reg_user,moderator,admin',
        ]);
    }
}
