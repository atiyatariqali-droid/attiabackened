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

        $manageClass = ManageClass::where('name', $teacher->username)->first();
        if (!$manageClass) {
            return response()->json([
                'success' => false,
                'message' => 'No class assigned to you by admin'
            ], 400);
        }

        $class_id = $manageClass->id;

      //campus location
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

        // Fetch students assigned to this class
        $students = Students::where('role', 'student')
            ->where('status', 1)
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


    // GET A TEACHER'S SESSIONS
    public function getTeacherSessions($teacher_id)
    {
        $sessions = Session::where('teacher_id', $teacher_id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Optionally, attach class names to the sessions
        foreach ($sessions as $session) {
            $class = ManageClass::find($session->class_id);
            $session->class_name = $class ? ($class->class_name ?? $class->name) : 'Unknown';
        }

        return response()->json([
            'success' => true,
            'data'    => $sessions
        ]);
    }

    // END SESSION (Mark as inactive)
    public function endSession($id)
    {
        $session = Session::find($id);
        if (!$session) {
            return response()->json(['success' => false, 'message' => 'Session not found'], 404);
        }

        $session->status = 'inactive';
        $session->save();

        return response()->json([
            'success' => true,
            'message' => 'Session ended successfully',
            'data'    => $session
        ]);
    }

    // UPDATE SESSION STATUS
    
    public function updateSessionStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:active,inactive']);

        $session = Session::find($id);
        if (!$session) {
            return response()->json(['success' => false, 'message' => 'Session not found'], 404);
        }

        $session->status = $request->status;
        $session->save();

        return response()->json([
            'success' => true,
            'message' => 'Session status updated',
            'data'    => $session
        ]);
    }

    // DELETE SESSION

    public function deleteSession($id)
    {
        $session = Session::find($id);
        if (!$session) {
            return response()->json(['success' => false, 'message' => 'Session not found'], 404);
        }

        $session->delete();

        return response()->json([
            'success' => true,
            'message' => 'Session deleted successfully'
        ]);
    }
    public function sessionReport(Request $request)
{
    $query = Session::with('teacher')->orderBy('created_at', 'desc');

    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }

    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function ($q) use ($search) {
            $q->where('id', 'like', "%{$search}%")
              ->orWhereHas('teacher', function ($tq) use ($search) {
                  $tq->where('name', 'like', "%{$search}%");
              });
        });
    }

    $sessions = $query->get()->map(function ($session) {
        return [
            'session_id'       => $session->id,
            'user_name'        => $session->teacher->username ?? 'Unknown',
            'current_location' => round($session->latitude, 6) . ', ' . round($session->longitude, 6),
            'status'           => $session->status,
            'created_at'       => optional($session->created_at)->format('Y-m-d H:i:s'),
        ];
    });

    return response()->json([
        'success' => true,
        'count'   => $sessions->count(),
        'data'    => $sessions,
    ]);
}
public function reportDashboard(Request $request)
{
    $today = Carbon::today();

    // Stats
    $totalSessions = Session::count();

    $activeSessToday = Session::where('status', 'active')
        ->whereDate('created_at', $today)
        ->count();

    $totalPresent = \App\Models\Attendance::where('status', 'present')->count();

    // Flagged = sessions ended in under 2 minutes (suspicious short sessions)
    $flagged = Session::whereNotNull('end_time')
        ->whereRaw('TIMESTAMPDIFF(SECOND, start_time, end_time) < 120')
        ->count();

    // Weekly attendance % (last 7 days)
    $weeklyData = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = Carbon::today()->subDays($i);
        $present = \App\Models\Attendance::where('status', 'present')
            ->whereDate('attendance_date', $date)->count();
        $total = \App\Models\Attendance::whereDate('attendance_date', $date)->count();
        $weeklyData[] = [
            'day'        => $date->format('D'),
            'percentage' => $total > 0 ? round(($present / $total) * 100, 1) : 0,
        ];
    }

    // Recent sessions (last 5)
    $recentSessions = Session::with('teacher')
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get()
        ->map(fn($s) => [
            'type'       => 'session',
            'id'         => $s->id,
            'title'      => $s->teacher->username ?? 'Unknown Teacher',
            'subtitle'   => 'Class ID: ' . $s->class_id,
            'time'       => optional($s->created_at)->format('h:i A'),
            'status'     => $s->status,
            'created_at' => optional($s->created_at)->toISOString(),
        ]);

    // Recent attendance (last 5)
    $recentAttendance = \App\Models\Attendance::with('student')
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get()
        ->map(fn($a) => [
            'type'       => 'attendance',
            'id'         => $a->id,
            'title'      => $a->student->username ?? 'Unknown Student',
            'subtitle'   => 'Date: ' . $a->attendance_date,
            'time'       => optional($a->created_at)->format('h:i A'),
            'status'     => $a->status,
            'created_at' => optional($a->created_at)->toISOString(),
        ]);

    // Merge and sort by created_at desc
    $logs = $recentSessions->concat($recentAttendance)
        ->sortByDesc('created_at')
        ->values();

    return response()->json([
        'success' => true,
        'stats'   => [
            'total_sessions'   => $totalSessions,
            'active_today'     => $activeSessToday,
            'total_present'    => $totalPresent,
            'flagged'          => $flagged,
        ],
        'weekly_data' => $weeklyData,
        'recent_logs' => $logs,
    ]);
}
//attendance report 
public function attendanceReport(Request $request)
{
    $query = \App\Models\Attendance::with(['student', 'session.teacher'])
        ->orderBy('created_at', 'desc');

    // Filter by session
    if ($request->filled('session_id')) {
        $query->where('session_id', $request->session_id);
    }

    // Filter by status (present/absent/late)
    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }

    // Filter by date
    if ($request->filled('date')) {
        $query->whereDate('attendance_date', $request->date);
    }

    // Teacher sees only their own sessions' attendance
    if ($request->filled('teacher_id')) {
        $query->whereHas('session', function ($q) use ($request) {
            $q->where('teacher_id', $request->teacher_id);
        });
    }

    $records = $query->get()->map(fn($a) => [
        'id'              => $a->id,
        'student_name'    => $a->student->username ?? 'Unknown',
        'roll_no'         => $a->student->roll_no ?? '-',
        'class'           => $a->student->class ?? '-',
        'status'          => $a->status,
        'attendance_date' => $a->attendance_date,
        'session_id'      => $a->session_id,
        'teacher_name'    => $a->session?->teacher?->username ?? '-',
        'marked_at'       => optional($a->created_at)->format('Y-m-d h:i A'),
    ]);

    return response()->json([
        'success' => true,
        'count'   => $records->count(),
        'data'    => $records,
    ]);
}
//index method
public function index()
{
    $sessions = Session::with(['teacher'])
        ->latest('created_at')
        ->get()
        ->map(function ($session) {
            return [
                'id'           => $session->id,
                'teacher_name' => $session->teacher->username ?? 'Unknown',
                'class_id'     => $session->class_id,
                'status'       => $session->status,
                'latitude'     => $session->latitude,
                'longitude'    => $session->longitude,
                'start_time'   => optional($session->start_time)->format('h:i A'),
                'end_time'     => optional($session->end_time)->format('h:i A'),
                'date'         => optional($session->created_at)->format('d M Y'),
                'created_at'   => $session->created_at,
            ];
        });

    return response()->json([
        'success' => true,
        'count'   => $sessions->count(),
        'data'    => $sessions,
    ]);
}
//TOGGLA method
public function toggleStatus(Request $request, $id)
{
    $session = Session::find($id);

    if (!$session) {
        return response()->json([
            'success' => false,
            'message' => 'Session not found'
        ], 404);
    }

    // active <-> completed toggle
    $session->status = $session->status === 'active' ? 'inactive' : 'active';
    $session->save();

    return response()->json([
        'success' => true,
        'id'      => $session->id,
        'status'  => $session->status,
    ]);
}
}