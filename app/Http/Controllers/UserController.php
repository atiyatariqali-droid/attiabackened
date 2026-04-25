<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function login(Request $request)
    {
        // Basic validation (don’t skip this)
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        info("Login attempt for email: " . $request->email);

        $user = User::where("email", $request->email)->first();

        info($user->password);

        if (!$user || !Hash::check($request->password, $user->password)) {
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
                "name" => $user->name,   // NOT username unless you created that column
                "email" => $user->email,
                "token" => $token
            ]
        ];
    }
}
