<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Session; 
use App\Models\SystemConfi;
use Carbon\Carbon;

class SessionController extends Controller
{
    public function login(Request $request)
    {
        return response()->json([
            "success" => true,
            "message" => "Session login working"
        ]);
    }

    public function logout($id)
    {
        return response()->json([
            "success" => true,
            "message" => "Session logout working",
            "id" => $id
        ]);
    }

    public function activeSessions()
    {
        return response()->json([
            "success" => true,
            "data" => []
        ]);
    }

    // Helper to calculate distance in meters using Haversine formula
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // meters
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }

    public function createSession(Request $request) 
    {
        $request->validate([
            'teacher_id' => 'required|integer',
            'class_id' => 'required|integer',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        // Get school coordinates
        $config = SystemConfi::first();
        if (!$config || !$config->latitude || !$config->longitude) {
            return response()->json([
                'success' => false,
                'message' => 'School location configuration is not set.'
            ], 400);
        }

        $schoolLat = $config->latitude;
        $schoolLng = $config->longitude;

        // Calculate distance
        $distance = $this->calculateDistance(
            $request->latitude,
            $request->longitude,
            $schoolLat,
            $schoolLng
        );

        $allowedDistance = 100; // 100 meters
        if ($distance > $allowedDistance) {
            return response()->json([
                'success' => false,
                'message' => 'Out of school range! You are ' . round($distance) . ' meters away from school.'
            ], 400);
        }

        // Check: 1 class ki 1 hi active session
        $existing = Session::where('class_id', $request->class_id)
                            ->where('status', 'active')
                            ->first();
        
        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Is class ki session pehle se active hai'
            ], 400);
        }

        $session = Session::create([
            'teacher_id' => $request->teacher_id,
            'class_id' => $request->class_id,
            'start_time' => Carbon::now(),
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'status' => 'active'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Session create ho gayi',
            'data' => $session
        ], 201);
    }
}
