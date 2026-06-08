<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function login(Request $request)
    {
        
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        info("Login attempt for email: " . $request->email);

        $user = User::where("email", $request->email)->first();

        if (!$user) {
            return [
                "success" => false,
                "error" => "Invalid credentials"
            ];
        }

        $token = $user->createToken("auth_token")->plainTextToken;

        return [
            "success" => true,
            "msg" => "Login successful",
            "result" => [
                'id' => $user->id,  
                "username" => $user->username,   
                "email" => $user->email,
                "token" => $token,
                "role" => $user->role
            ]
        ];
    }
}
