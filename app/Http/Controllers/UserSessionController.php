<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserSession;
use App\Models\SystemConfi;
use Illuminate\Support\Facades\Hash;

class UserSessionController extends Controller
{
    
     // Calculate distance using Haversine formula
     
    private function calculateDistance($teacherLat, $teacherLng, $schoolLat, $schoolLng)
    {
        $config = SystemConfi::first();
         if (!$config || !$config->latitude || !$config->longitude) {
        return null;
    }
    $schoolLat = $config->latitude;
    $schoolLng = $config->longitude;

        $earthRadius = 6371; // KM
      
    $dLat = deg2rad($schoolLat - $teacherLat);
    $dLng = deg2rad($schoolLng - $teacherLng);

    $a = sin($dLat / 2) * sin($dLat / 2) +
        cos(deg2rad($teacherLat)) *
        cos(deg2rad($schoolLat)) *
        sin($dLng / 2) * sin($dLng / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
     return round($earthRadius * $c, 2);   
    }

    
    // User Login Session
     
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_id' => 'required',
            'latitude' => 'required',
            'longitude' => 'required'
        ]);
        $user = User::where('email', $request->email)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid Credentials'
            ], 401);
        }
 
        // Get school coordinates
        $config = SystemConfi::first();
        $schoolLat = $config->latitude;
        $schoolLng = $config->longitude;

        // Check allowed range
        $distance = $this->calculateDistance(
            $request->latitude,
            $request->longitude,
            $schoolLat,
            $schoolLng
        );
        $allowedDistance = 100;

        //Allow or reject range
            if ($distance > $allowedDistance) {
                return response()->json([
                    'message' => 'Login Failed: Out of Range',
                    'distance' => round($distance, 2)
                ], 403);
            }

        // TODO: Add distance validation logic here
    return response()->json([
    'message' => 'Login Successful',
    'distance_km' => round($distance, 2),

    'school_location' => [
        'latitude' => $schoolLat,
        'longitude' => $schoolLng,
        'google_map_url' => $schoolMapUrl
    ],

    'teacher_location' => [
        'latitude' => $request->latitude,
        'longitude' => $request->longitude,
        'google_map_url' => $teacherMapUrl
    ]
]);      
       
    }

    // Logout Session
    public function logout($id)
    {
        $session = UserSession::find($id);
        if (!$session) {
            return response()->json([
                'message' => 'Session Not Found'
            ], 404);
        }
        $session->update([
            'logout_time' => now(),
            'is_active' => false
        ]);
        return response()->json([
            'message' => 'Logout Successful'
        ]);
    }

    //Get Active Sessions 
    public function activeSessions()
    {
        $sessions = UserSession::where('is_active', true)->get();
        return response()->json($sessions);
    }
}
