<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Session;
use App\Models\Attendance;
use App\Models\User;
use App\Models\UserSession;
use App\Models\SystemConfi;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UserSessionController extends Controller
{
    // Calculate distance in meters using Haversine formula
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

    /**
     * USER LOGIN SESSION
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_id' => 'required',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid Credentials'], 401);
        }
 
        // Get school coordinates
        $config = SystemConfi::first();
        if (!$config || !$config->latitude || !$config->longitude) {
            return response()->json(['message' => 'School configuration coordinates not set.'], 400);
        }
        $schoolLat = $config->latitude;
        $schoolLng = $config->longitude;

        // Check allowed range
        $distance = $this->calculateDistance(
            $request->latitude,
            $request->longitude,
            $schoolLat,
            $schoolLng
        );
        $allowedDistance = 100; // 100 meters

        // Allow or reject range
        if ($distance > $allowedDistance) {
            return response()->json([
                'message' => 'Login Failed: Out of Range',
                'distance' => round($distance, 2)
            ], 403);
        }

        // Deactivate old sessions
        UserSession::where('user_id', $user->id)->where('is_active', true)->update(['is_active' => false]);

        // Create new session
        $session = UserSession::create([
            'user_id' => $user->id,
            'device_id' => $request->device_id,
            'ip_address' => $request->ip() ?? '127.0.0.1',
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'login_time' => Carbon::now(),
            'is_active' => true
        ]);

        $schoolMapUrl = "https://www.google.com/maps?q={$schoolLat},{$schoolLng}";
        $teacherMapUrl = "https://www.google.com/maps?q={$request->latitude},{$request->longitude}";

        return response()->json([
            'message' => 'Login Successful',
            'distance_meters' => round($distance, 2),
            'session' => $session,
            'user' => $user,
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
        ], 200);
    }

    /**
     * USER LOGOUT SESSION
     */
    public function logout(Request $request, $id)
    {
        $session = UserSession::find($id);
        if (!$session) {
            return response()->json(['message' => 'Session Not Found'], 404);
        }
        $session->update([
            'logout_time' => Carbon::now(),
            'is_active' => false
        ]);

        return response()->json(['message' => 'Logout Successful']);
    }

    /**
     * GET ACTIVE SESSIONS
     */
    public function activeSessions()
    {
        $sessions = UserSession::where('is_active', true)->with('user')->get();
        return response()->json($sessions);
    }

    /**
     * STUDENT ATTENDANCE MARK
     */
    public function markAttendance(Request $request) {
        $request->validate([
            'session_id' => 'required|integer|exists:sessions,id',
            'student_id' => 'required|integer|exists:users,id',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        // 1. Check session active hai
        $session = Session::find($request->session_id);
        if ($session->status != 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Session khatam ho chuki hai'
            ], 400);
        }

        // 2. Check duplicate attendance
        $exists = Attendance::where('session_id', $request->session_id)
                            ->where('student_id', $request->student_id)
                            ->first();
        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Attendance pehle hi mark ho chuki hai'
            ], 400);
        }

        // 3. Location check - teacher se 100 meter radius
        $distance = $this->calculateDistance(
            $session->latitude, 
            $session->longitude,
            $request->latitude, 
            $request->longitude
        );
        
        if ($distance > 100) {
            return response()->json([
                'success' => false,
                'message' => 'Aap class room se bahar hain. Distance: '.round($distance).' meter'
            ], 400);
        }

        // 4. Attendance save
        $attendance = Attendance::create([
            'session_id' => $request->session_id,
            'student_id' => $request->student_id,
            'marked_at' => Carbon::now(),
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'status' => 'present'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Attendance mark ho gayi',
            'data' => $attendance
        ], 201);
    }
}