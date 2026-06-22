<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Session;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function markAttendance(Request $request)
    {
        $request->validate([
            'session_id' => 'required|integer',
            'student_id' => 'required|integer',
            'latitude'   => 'required|numeric',
            'longitude'  => 'required|numeric',
            'status'     => 'required|string|in:present,absent,late',
        ]);

        // Find the active class session (Session model points to attendance_sessions table)
        $session = Session::find($request->session_id);
        if (!$session) {
            // For developer testing / standalone mark attendance screen (which uses hardcoded session_id = 1)
            // if no session exists, we dynamically seed one at student's coordinates.
            $session = new Session();
            $session->id = $request->session_id;
            $session->teacher_id = 2; // default seeded teacher
            $session->class_id = 1;   // default seeded class
            $session->start_time = now();
            $session->latitude = $request->latitude;
            $session->longitude = $request->longitude;
            $session->status = 'active';
            $session->save();
        }

        if ($session->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Session is not active'
            ], 400);
        }

        // Location check: must be within 100 meters of the teacher's session start location
        $distance = $this->calculateDistance(
            (float) $session->latitude,
            (float) $session->longitude,
            (float) $request->latitude,
            (float) $request->longitude
        );

        if ($distance > 100) {
            return response()->json([
                'success' => false,
                'message' => 'You are outside the classroom range. Distance: ' . round($distance) . ' meters.'
            ], 400);
        }

        // Get class_id and date from the session
        $classId = $session->class_id;
        $attendanceDate = Carbon::parse($session->start_time)->toDateString();

        // Check if attendance is already marked for this student in this class on this date
        $exists = Attendance::where('student_id', $request->student_id)
                            ->where('class_id', $classId)
                            ->where('attendance_date', $attendanceDate)
                            ->first();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Attendance is already marked for today'
            ], 400);
        }

        // Save attendance record to the attendance table
        $attendance = Attendance::create([
            'student_id'      => $request->student_id,
            'class_id'        => $classId,
            'attendance_date' => $attendanceDate,
            'status'          => $request->status,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Attendance Marked Successfully',
            'data'    => $attendance
        ], 201);
    }

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
 

    // SAVE SESSION STUDENTS (bulk mark present)
    public function saveSessionStudents(Request $request)
    {
        $request->validate([
            'session_id'    => 'required|integer|exists:attendance_sessions,id',
            'student_ids'   => 'required|array|min:1',
            'student_ids.*' => 'integer',
        ]);

        $session = Session::find($request->session_id);

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found'
            ], 404);
        }

        $attendanceDate = Carbon::parse($session->start_time)->toDateString();
        $classId = $session->class_id;

        $records = [];
        foreach ($request->student_ids as $studentId) {
            $records[] = Attendance::updateOrCreate(
                [
                    'student_id'      => $studentId,
                    'class_id'        => $classId,
                    'attendance_date' => $attendanceDate,
                ],
                [
                    'session_id' => $session->id,
                    'status'     => 'present',
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Students marked present successfully',
            'data'    => $records
        ], 201);
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

public function getActiveSession($teacherId)
{
    $session = Session::where('teacher_id', $teacherId)
                      ->where('status', 'active')
                      ->latest('created_at')
                      ->first();

    return response()->json([
        'success' => true,
        'active'  => $session !== null,
        'data'    => $session
    ]);
}

}
