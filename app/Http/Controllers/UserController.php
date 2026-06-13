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
            // 'longitude' => 'required|numeric',
            // 'latitude' => 'required|numeric',
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
        return[
    'success' => true,
    'message' => 'Login successful',
    "result" => [
        "username" => $user->username,   
        "email" => $user->email,
        "token" => $token,
        "role" => $user->role
    ]
    ];
           
         // Create Session
        session([
           'user_id' => $user->id,
           'username' => $user->username,
           'email' => $user->email,
           'role' => $user->role
       ]);
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

        
        $school = SystemConfi::first();

$distance = $this->distance(
    $request->latitude,
    $request->longitude,
    $school->latitude,
    $school->longitude
);

$allowed = 100; // meters

if ($distance > $allowed) {
    $schoolMapUrl = "https://www.google.com/maps?q={$schoolLat},{$schoolLng}";
    $teacherMapUrl = "https://www.google.com/maps?q={$teacherLat},{$teacherLng}";
    return response()->json([
        'success' => false,
        'message' => 'You are outside school range',
        'distance' => $distance
    ]);
}
    }
}
