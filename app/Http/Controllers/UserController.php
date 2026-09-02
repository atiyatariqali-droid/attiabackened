<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

use Illuminate\Support\Facades\Hash;
use App\Models\SystemSetting;


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

    //  teacher account must be approved by admin before login is allowed.
    if ($user->role === 'teacher' && (int) $user->status !== 1) {
        return response()->json([
            'success' => false,
            'message' => 'Your account is pending admin approval. Please wait until an admin approves your registration.'
        ], 403);
    }

    if ($user->role === 'student') {
        if (!$request->filled('latitude') || !$request->filled('longitude')) {
             return response()->json([
                 'success' => false,
                 'message' => 'Location is required for students to login. Please enable GPS permissions.'
             ], 403);
        }

        $campusLat = (float) SystemSetting::where('key', 'school_latitude')->value('value');
        $campusLng = (float) SystemSetting::where('key', 'school_longitude')->value('value');

        if (!$campusLat || !$campusLng) {
            return response()->json([
                'success' => false,
                'message' => 'Campus location not configured in system settings'
            ], 500);
        }

        $distance = $this->calculateDistance(
            (float) $request->latitude,
            (float) $request->longitude,
            $campusLat,
            $campusLng
        );

        if ($distance > 150) {
            return response()->json([
                'success' => false,
                'message' => 'You must be on campus to login. You are ' . round($distance) . ' meters away.'
            ], 403);
        }
    }


    // 4. Device ID check (dynamically bind device ID on first login if null)
    if ($user->role !== 'student') {
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

    private function calculateDistance($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6371; // KM
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) *
            cos(deg2rad($lat2)) *
            sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distanceKm = $earthRadius * $c;
        return round($distanceKm * 1000, 2); // convert km to meters
    }
}