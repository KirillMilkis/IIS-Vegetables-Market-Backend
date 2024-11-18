<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\UserResource;
use App\Http\Resources\UserCollection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Session;

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

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {


        $input = collect($request->all())->mapWithKeys(function ($value, $key) {
            return [Str::snake($key) => $value];
        })->toArray();

        $validator = $this->validator_create($input);

        if ($validator->fails()) {
            return response()-> json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(), 
                'code' => 400]);
        }

        $input['password'] = bcrypt($input['password']);

        if (User::create($input)) {
            return response()->json(['message' => 'User created',
                'code' => 201], 
                201);
        } else {
            return response()->json(['message' => 'User not created',
                'code' => 500], 
                500);
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

        if (!$user) {
            return response()->json(['message' => 'User not found',
                'code' => 404], 
                404);
        }

        $input = collect($request->all())->mapWithKeys(function ($value, $key) {
            return [Str::snake($key) => $value];
        })->toArray();

        $validator = $this->validator_create($input);

        if ($validator->fails()) {
            return response()-> json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(), 
                'code' => 400]);
        }

        if (isset($input['password'])) {
            $input['password'] = bcrypt($input['password']);
        }

        if ($user->update($input)) {
            return response()->json(['message' => 'User updated',
                'code' => 200], 
                200);
        } else {
            return response()->json(['message' => 'User not updated',
                'code' => 414], 
                414);
        }


    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found',
                'code' => 404], 
                404);
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
            'address' => 'max:100',
            'password' => 'required|string|min:8|max:32',
            'email' => 'string|max:255|unique:users',
            'role' => 'required|string|in:ADMIN,MODER,REG_CUSTOMER_FARMER,REG_CUSTOMER,UNREG_CUSTOMER',
        ]);
    }
}
