<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Mysugar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Carbon\Carbon;

class AuthUser extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login','register','refresh','logout']]);
    }

    public function register(Request $request){
        $request->validate([
            'fname' => 'required|string|max:255',
            'lname' => 'required|string|max:255',
            'idcard' => 'required|string|max:13|unique:users',
            'password' => 'required|string|min:8',
            'dob' => 'nullable|date|before_or_equal:' . Carbon::now()->format('Y-m-d'),
            'phone' => 'nullable|string|max:10',
            'address' => 'nullable|string|max:500',
            'status' => 'required|boolean',

        ]);
    
        $user = User::create([
            'fname' => $request->fname,
            'lname' => $request->lname,
            'idcard' => $request->idcard,
            'password' => Hash::make($request->password),
            'dob' => $request->dob,
            'phone' => $request->phone,
            'address' => $request->address,
            'status' => $request->status,
        ]);

         // Create a Mysugar record for the newly registered user
        Mysugar::create([
            'user_id' => $user->id,
            'sugarValue' => null, // Set default value or based on your logic
            'symptom' => null, // Set default value or based on your logic
            'note' => null, // Set default value or based on your logic
        ]);

        $token = Auth::guard('api')->login($user);
        return response()->json([
            'status' => 'success',
            'message' => 'User created successfully',
            'user' => $user,
            'authorisation' => [
                'token' => $token,
                'type' => 'bearer',
            ]
        ]);
    }
    
public function login(Request $request)
{
    $request->validate([
        'idcard' => 'required|string',
        'password' => 'required|string',
    ]);

    $credentials = $request->only('idcard', 'password');

    $user = User::where('idcard', $credentials['idcard'])->first();

    if (!$user) {
        return response()->json([
            'status' => 'error',
            'message' => 'User not found',
        ], 404);
    }

    if (!Hash::check($credentials['password'], $user->password)) {
        return response()->json([
            'status' => 'error',
            'message' => 'Incorrect password!',
        ], 401);
    }

    $token = Auth::guard('api')->attempt($credentials);
    if (!$token) {
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized',
        ], 401);
    }

    return response()->json([
        'status' => 'success',
        'user' => $user,
        'authorisation' => [
            'token' => $token,
            'type' => 'bearer',
        ]
    ]);
}

    public function logout()
    {
        Auth::guard('api')->logout();
        return response()->json([
            'status' => 'success',
            'message' => 'Successfully logged out',
        ]);
    }


    public function refresh()
    {
        return response()->json([
            'status' => 'success',
            'user' => Auth::guard('api')->user(),
            'authorisation' => [
                'token' => Auth::guard('api')->refresh(),
                'type' => 'bearer',
            ]
        ]);
    }


    /* ------------------------------------------------------ */
    public function getProfile(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'status' => 'success',
            'user' => $user,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $user->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Profile updated successfully',
            'user' => $user,
        ]);
    }

}