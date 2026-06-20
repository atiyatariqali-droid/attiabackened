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
}
