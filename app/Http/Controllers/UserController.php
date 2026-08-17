<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function login(Request $request)
    {
        // validation don’t skip this
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_id' => 'required',
        ]);
   //user find ky liye
        $user = User::where("email", $request->email)->first();

        if (!$user) {
            return response()->json([
                "success" => false,
                "error" => "Invalid credentials"
            ], 401);
        }

        // 3. Password check
    if (!Hash::check($request->password, $user->password)) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid credentials'
        ], 401);
    }

    // 4. Device ID check (dynamically bind device ID on first login if null)
    if ($user->device_id) {
        if ($user->device_id !== $request->device_id) {
            return response()->json([
                'success' => false,
                'message' => 'This device is not authorized'
            ], 403);
        }
    } else {
        $user->device_id = $request->device_id;
        $user->save();
    }

    //create token
        $token = $user->createToken("auth_token")->plainTextToken;
       //success response
        return response()->json([
    'success' => true,
    'message' => 'Login successful',
    "result" => [
        "id" => $user->id,
        "username" => $user->username,   
        "email" => $user->email,
        "token" => $token,
        "role" => $user->role
    ]
    ]);
    }
}