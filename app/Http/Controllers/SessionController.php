<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Session;
use App\Models\SystemSetting;
use App\Models\Teachers;
use App\Models\ManageClass;
use App\Models\Students;
use Carbon\Carbon;

class SessionController extends Controller
{
    public function createSession(Request $request)
    {
        $request->validate([
            'teacher_id' => 'required|integer',
            'latitude'   => 'required|numeric',
            'longitude'  => 'required|numeric',
        ]);

        // Step 1: Get campus lat/lng from system_settings table
        $campusLat = (float) SystemSetting::where('key', 'school_latitude')->value('value');
        $campusLng = (float) SystemSetting::where('key', 'school_longitude')->value('value');
         //device 
         $teacher = Teachers::find($request->teacher_id);

if (!$teacher) {
    return response()->json([
        'success' => false,
        'message' => 'Teacher not found'
    ], 404);
}
if ($teacher->device_id !== $request->device_id) {
    return response()->json([
        'success' => false,
        'message' => 'Unregistered device. Session not allowed.'
    ], 403);
}//me

        $manageClass = ManageClass::where('name', $teacher->username)->first();
        if (!$manageClass) {
            return response()->json([
                'success' => false,
                'message' => 'No class assigned to you by admin'
            ], 400);
        }

        $class_id = $manageClass->id;

    //   campus location
        if (!$campusLat || !$campusLng) {
            return response()->json([
                'success' => false,
                'message' => 'Campus location not configured in system settings'
            ], 500);
        }

        // Step 2: Calculate distance between teacher and campus
        $distance = $this->calculateDistance(
            (float) $request->latitude,
            (float) $request->longitude,
            $campusLat,
            $campusLng
        );

        // Step 3: Must be within 500 meters
        if ($distance > 500) {
            return response()->json([
                'success'  => false,
                'message'  => 'You are not inside the campus. You are ' . round($distance) . ' meters away.',
                'distance' => round($distance)
            ], 403);
        }

        // Step 4: Check if class already has active session
        $existing = Session::where('class_id', $class_id)
                           ->where('status', 'active')
                           ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'This class already has an active session'
            ], 400);
        }

        // Step 5: Create session
        $session = Session::create([
            'teacher_id' => $request->teacher_id,
            'class_id'   => $class_id,
            'start_time' => Carbon::now(),
            'latitude'   => $request->latitude,
            'longitude'  => $request->longitude,
            'status'     => 'active'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Session created successfully',
            'data'    => $session
        ], 201);
    }

    // Fixed Haversine formula - calculates distance in meters
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) *
            cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    public function login(Request $request)
    {
        return response()->json(['success' => true, 'message' => 'Session login working']);
    }

    public function getSessionStudents($id)
    {
        $session = Session::find($id);

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found'
            ], 404);
        }

        // Get the class string name associated with the session
        $manageClass = ManageClass::find($session->class_id);
        if (!$manageClass) {
            return response()->json([
                'success' => false,
                'message' => 'Class not found'
            ], 404);
        }

        $className = $manageClass->class_name ?? $manageClass->name;

        // Fetch students assigned to this teacher and this class
        $students = Students::where('role', 'student')
            ->where('status', 1)
            ->where('teacher_id', $session->teacher_id)
            ->where('class', $className)
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $students
        ]);
    }

    public function logout($id)
    {
        return response()->json(['success' => true, 'message' => 'Session logout working', 'id' => $id]);
    }

    public function activeSessions()
    {
        return response()->json([
            'success' => true,
            'data'    => Session::where('status', 'active')->get()
        ]);
    }
}