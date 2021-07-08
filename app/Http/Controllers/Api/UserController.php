<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\User;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class UserController extends Controller
{
    //Function for User registration
    public function register(Request $request)
    {
        $validator = \Validator::make($request->all(),[ 
            'firstname' => 'required|string|min:2|max:255',
            'lastname' => 'required|string|min:2|max:255',
            'mobile' => 'required|numeric|digits:10|unique:users',
            'email' => 'required|email|unique:users',
            'age' => 'required|numeric',
            'gender' => 'required|in:m,f,o',
            'city' => 'required|string',
            'password' => 'required|min:5',
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 422);      
        }
  
        $user = User::create([
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'mobile' => $request->mobile,
            'email' => $request->email,
            'age' => $request->age,
            'gender' => $request->gender,
            'city' => $request->city,
            'password' => bcrypt($request->password)
        ]);
  
        $token = JWTAuth::fromUser($user);
        return response()->json(compact('user','token'),201);
    }
  
    //Function for User login
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');
        try {
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'invalid_credentials'], 400);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'could_not_create_token'], 500);
        }

        return response()->json(compact('token'),200);
    }
 
    //Function for getting User information
    public function getAuthenticatedUser()
    {
        try {
            if (! $user = JWTAuth::parseToken()->authenticate()) {
                return response()->json(['user_not_found'], 404);
            }
        } catch (Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['token_expired'], $e->getStatusCode());
        } catch (Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['token_invalid'], $e->getStatusCode());
        } catch (Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['token_absent'], $e->getStatusCode());
        }

        return response()->json(compact('user'));
    }
}
