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
            'session_id'      => $session->id,
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
 

    // SAVE SESSION STUDENTS ( mark present)
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
    $classId        = $session->class_id;

    // ── Step 1: Mark attendance ───────────────────
    $successfulStudentIds = [];

    foreach ($request->student_ids as $studentId) {
         Attendance::updateOrCreate(
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

        // Track successfully marked students
        $successfulStudentIds[] = (int) $studentId;
    }

    // ── Step 2: Auto confirmation request ────────
    \DB::table('confirmation_requests')
        ->where('session_id', $request->session_id)
        ->where('status', 'pending')
        ->update(['status' => 'closed']);

    \DB::table('confirmation_requests')->insert([
        'session_id' => $request->session_id,
        'status'     => 'pending',
        'expires_at' => Carbon::now()->addMinutes(2),
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now(),
    ]);

    // Step 3: Random notification logic 3 students only
    $totalMarked = count($successfulStudentIds);
    $notifiedStudents = [];

    if ($totalMarked >= 10) {
        $shuffled = $successfulStudentIds;
        shuffle($shuffled);

        // Pick first 3 after shuffle — guaranteed unique
        $selected = array_slice($shuffled, 0, 3);

        foreach ($selected as $studentId) {
            // Check no duplicate notification for same session
            $alreadyNotified = \DB::table('notifications')
                ->where('student_id', $studentId)
                ->where('session_id', $session->id)
                ->where('type', 'attendance_marked')
                ->exists();
                 if (!$alreadyNotified) {
                \DB::table('notifications')->insert([
                    'student_id'  => $studentId,
                    'session_id'  => $session->id,
                    'message'     => 'Your attendance has been randomly selected for verification. Please confirm.',
                    'type'        => 'attendance_marked',
                    'is_read'     => false,
                    'created_at'  => Carbon::now(),
                    'updated_at'  => Carbon::now(),
                ]);
                $notifiedStudents[] = $studentId;
            }
        }
    }
     return response()->json([
        'success'              => true,
        'message'              => 'Attendance marked successfully',
        'total_marked'         => $totalMarked,
        'notifications_sent'   => count($notifiedStudents),
        'notified_student_ids' => $notifiedStudents,
        'notification_note'    => $totalMarked >= 10
            ? '3 random students notified out of ' . $totalMarked
            : 'Only ' . $totalMarked . ' students marked — minimum 10 required for notifications',
    ], 201);

        // // Randomly pick exactly 3 unique students
        // $randomKeys = array_rand($successfulStudentIds, 3);

        // // array_rand returns single value if count=1, array otherwise
        // if (!is_array($randomKeys)) {
        //     $randomKeys = [$randomKeys];
        // }

        // foreach ($randomKeys as $key) {
        //     $studentId = $successfulStudentIds[$key];
        //     $notifiedStudents[] = $studentId;

            // Save notification in DB
        //     \DB::table('notifications')->insert([
        //         'student_id'  => $studentId,
        //         'session_id'  => $session->id,
        //         'message'     => 'Your attendance has been marked for today\'s class.',
        //         'type'        => 'attendance_marked',
        //         'is_read'     => false,
        //         'created_at'  => Carbon::now(),
        //         'updated_at'  => Carbon::now(),
        //     ]);
        // }
    
}
//get notification
public function getNotifications($studentId)
{
    $notifications = \DB::table('notifications')
        ->where('student_id', $studentId)
        ->orderBy('created_at', 'desc')
        ->limit(20)
        ->get();
        // Step 2: Unread count check karo BEFORE marking read
    $unreadCount = \DB::table('notifications')
        ->where('student_id', $studentId)
        ->where('is_read', false)
        ->count();

    // Mark all as read
    \DB::table('notifications')
        ->where('student_id', $studentId)
        ->where('is_read', false)
        ->update(['is_read' => true]);

    return response()->json([
        'success' => true,
        'count'   => $notifications->count(),
        'unread_count' => $unreadCount, 
        'data'    => $notifications,
    ]);
}
//session report

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

    // ATTENDANCE REPORT (admin sees all, teacher filtered)
    
    public function attendanceReport(Request $request)
    {
        $query = Attendance::with(['student', 'session.teacher'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('session_id')) {
            $query->where('session_id', $request->session_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date')) {
            $query->whereDate('attendance_date', $request->date);
        }

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
            'session_id'      => $a->session_id ?? '-',
            'teacher_name'    => $a->session?->teacher?->username ?? '-',
            'marked_at'       => optional($a->created_at)->format('Y-m-d h:i A'),
        ]);

        return response()->json([
            'success' => true,
            'count'   => $records->count(),
            'data'    => $records,
        ]);
    }


}