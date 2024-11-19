<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth; 
use App\Models\User; 
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required|string|alpha_num|max:255',
            'password' => 'required',
        ]);
    
        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Incorrect login or password', 'errors' => []], 401);
        }
    
        $user = Auth::user();
        $token = $user->createToken('API Token')->plainTextToken;
    
        return response()->json([
            'token' => $token,
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'firstName' => $user->firstname,
            'lastName' => $user->lastname,
            'address' => $user->address,
            'role' => $user->role,
        ]);
    }


    public function register(Request $request)
    {
        
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255|unique:users',
            'username' => 'required|max:20|unique:users',
            'firstname' => 'required|max:32',
            'lastname' => 'required|max:32',
            'password' => 'required|string|min:8|max:32',
        ]);

        
        if ($validator->fails()) {
            return response()->json(['message' => 'Incorrect registration data', 'errors' => $validator->errors()], 422);
        }

       
        $user = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),  
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'role' => 'reg_user'
        ]);

      
        $token = $user->createToken('YourAppName')->plainTextToken;  

      
        return response()->json([
            'token' => $token,
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'firstName' => $user->firstname,
            'lastName' => $user->lastname,
            'address' => $user->address,
            'role' => $user->role,
        ], 201);
    }

    public function logout()
    {
        
        Auth::user()->tokens->each(function ($token) {
            $token->delete();
        });

        return response()->json(['message' => 'Successfully logged out']);
    }


}
