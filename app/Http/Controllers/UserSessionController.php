<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Session;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;

class SessionController extends Controller
{
    /**
     * USER LOGIN SESSION - Tumhara purana code
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

        if (!$user || !\Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid Credentials'], 401);
        }

        // deactivate old sessions
        Session::where('user_id', $user->id)->where('is_active', true)->update(['is_active' => false]);

        $session = Session::create([
            'user_id' => $user->id,
            'device_id' => $request->device_id,
            'login_time' => Carbon::now(),
            'login_latitude' => $request->latitude,
            'login_longitude' => $request->longitude,
            'is_active' => true
        ]);

        return response()->json([
            'message' => 'Login Successful',
            'session' => $session,
            'user' => $user
        ], 200);
    }

    /**
     * USER LOGOUT SESSION - Tumhara purana code
     */
    public function logout(Request $request, $id)
    {
        $session = Session::find($id);
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
     * GET ACTIVE SESSIONS - Tumhara purana code
     */
    public function activeSessions()
    {
        $sessions = Session::where('is_active', true)->with('user')->get();
        return response()->json($sessions);
    }

    /**
     * NAYA: STUDENT ATTENDANCE MARK - Ye naya add kiya hai
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
        if($session->is_active != true) {
            return response()->json([
                'success' => false,
                'message' => 'Session khatam ho chuki hai'
            ], 400);
        }

        // 2. Check duplicate attendance
        $exists = Attendance::where('session_id', $request->session_id)
                            ->where('student_id', $request->student_id)
                            ->first();
        if($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Attendance pehle hi mark ho chuki hai'
            ], 400);
        }

        // 3. Location check - teacher se 100 meter radius
        $distance = $this->calculateDistance(
            $session->login_latitude, 
            $session->login_longitude,
            $request->latitude, 
            $request->longitude
        );
        
        if($distance > 100) {
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

    /**
     * Distance calculate karne ka helper - Haversine formula
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2) {
        $earthRadius = 6371000; // meters
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        return $earthRadius * $c;
    }
}