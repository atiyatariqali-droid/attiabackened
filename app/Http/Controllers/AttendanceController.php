<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Session;
use App\Models\ManageClass;
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

        $session = Session::find($request->session_id);
        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found. Ask your teacher to start the session.'
            ], 404);
        }

        if ($session->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Session is not active'
            ], 400);
        }

        $radius = 100;
        $distance = $this->calculateDistance(
            (float) $session->latitude,
            (float) $session->longitude,
            (float) $request->latitude,
            (float) $request->longitude
        );

        if ($distance > $radius) {
            return response()->json([
                'success' => false,
                'message' => 'You are outside the classroom range. Distance: ' . round($distance) . ' meters.'
            ], 400);
        }

        $classId = $session->class_id;
        $attendanceDate = Carbon::parse($session->start_time)->toDateString();

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

    // Calculate how many Present students should receive verification notifications (20% rule)
    private function calculateNotificationCount($presentCount)
    {
        if ($presentCount <= 0) {
            return 0;
        }
        return max(1, (int) round($presentCount * 0.20));
    }

    // Restrict teacher-verification notifications to BS classes only
    private function isBsClass($classId)
    {
        $class = ManageClass::find($classId);
        if (!$class || empty($class->class_name)) {
            return true;
        }
        $name = strtoupper(trim($class->class_name));
        if ((str_contains($name, 'INTER') || str_contains($name, 'FSC') || str_contains($name, 'FA')) && !str_contains($name, 'BS')) {
            return false;
        }
        return true;
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
        ], [
            'student_ids.min' => 'Minimum 1 student is required to submit attendance.',
        ]);

        $session = Session::find($request->session_id);
        if (!$session) {
            return response()->json(['success' => false, 'message' => 'Session not found'], 404);
        }

        $attendanceDate = Carbon::parse($session->start_time)->toDateString();
        $classId        = $session->class_id;

        // Step 1: Mark all students present
        $markedIds = [];
        foreach ($request->student_ids as $sid) {
            Attendance::updateOrCreate(
                [
                    'student_id'      => (int) $sid,
                    'class_id'        => $classId,
                    'attendance_date' => $attendanceDate,
                ],
                [
                    'session_id' => $session->id,
                    'status'     => 'present',
                ]
            );
            $markedIds[] = (int) $sid;
        }

        // Step 2: restrict verification to BS classes only
        $isBsClass = $this->isBsClass($classId);

        // Step 3: 20% of Present students rule
        $total             = count($markedIds); // all marked here are 'present'
        $notificationCount = $isBsClass ? $this->calculateNotificationCount($total) : 0;
        $notifiedStudents  = [];

        if ($isBsClass && $notificationCount > 0) {
            $pool = $markedIds;
            shuffle($pool);
            $selected = array_slice($pool, 0, min($notificationCount, count($pool)));

            foreach ($selected as $chosenId) {
                // Close any old pending requests for this student (cleanup)
                \DB::table('confirmation_requests')
                    ->where('student_id', $chosenId)
                    ->where('status', 'pending')
                    ->update(['status' => 'closed']);

                // Per-student confirmation request
                \DB::table('confirmation_requests')->insert([
                    'session_id' => $session->id,
                    'student_id' => $chosenId,
                    'status'     => 'pending',
                    'expires_at' => Carbon::now()->addHour(1),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);

                // Notification
                \DB::table('notifications')->insert([
                    'student_id'  => $chosenId,
                    'session_id'  => $session->id,
                    'message'     => 'Please confirm: is your teacher present in the classroom?',
                    'type'        => 'teacher_verification',
                    'is_read'     => 0,
                    'created_at'  => Carbon::now(),
                    'updated_at'  => Carbon::now(),
                ]);

                $notifiedStudents[] = $chosenId;
            }
        }

        return response()->json([
            'success'              => true,
            'message'              => 'Attendance marked successfully',
            'total_marked'         => $total,
            'notifications_sent'   => count($notifiedStudents),
            'notified_student_ids' => $notifiedStudents,
            'note'                 => !$isBsClass
                ? 'Verification not applicable — not a BS class'
                : ($total > 0
                    ? $notificationCount . ' of ' . $total . ' present students selected for verification (20% rule)'
                    : 'No present students — no verification sent'),
        ], 201);
    }

    // SUBMIT ATTENDANCE BULK SAVE
    public function submitAttendance(Request $request)
    {
        $request->validate([
            'session_id' => 'required|integer|exists:attendance_sessions,id',
            'latitude'   => 'required|numeric',
            'longitude'  => 'required|numeric',
            'attendance' => 'required|array',
            'attendance.*.student_id' => 'required|integer',
            'attendance.*.status'     => 'required|string|in:present,absent,late',
            'attendance.*.reason'     => 'nullable|string',
        ]);

        $session = Session::find($request->session_id);
        if (!$session) {
            return response()->json(['success' => false, 'message' => 'Session not found'], 404);
        }

        if ($session->status !== 'active') {
            return response()->json(['success' => false, 'message' => 'Session is not active'], 400);
        }

        $radius = 100;
        $distance = $this->calculateDistance(
            (float) $session->latitude,
            (float) $session->longitude,
            (float) $request->latitude,
            (float) $request->longitude
        );

        if ($distance > $radius) {
            return response()->json([
                'success' => false,
                'message' => 'You are outside the classroom range. Distance: ' . round($distance) . ' meters.'
            ], 400);
        }

        $attendanceDate = Carbon::parse($session->start_time)->toDateString();
        $classId = $session->class_id;

        $markedIds = [];
        $presentOnlyIds = [];

        foreach ($request->attendance as $att) {
            $studentId = $att['student_id'];
            $status = $att['status'];

            Attendance::updateOrCreate(
                [
                    'student_id'      => $studentId,
                    'class_id'        => $classId,
                    'attendance_date' => $attendanceDate,
                ],
                [
                    'session_id' => $session->id,
                    'status'     => $status,
                ]
            );

            if ($status === 'present') {
                $presentOnlyIds[] = $studentId;
            }
            $markedIds[] = $studentId;
        }

        $isBsClass = $this->isBsClass($classId);

        // Verification logic — 20% of PRESENT students only (late/absent excluded)
        $totalPresent      = count($presentOnlyIds);
        $notificationCount = $isBsClass ? $this->calculateNotificationCount($totalPresent) : 0;
        $notifiedStudents  = [];

        if ($isBsClass && $totalPresent > 0 && $notificationCount > 0) {
            $pool = $presentOnlyIds;
            shuffle($pool);
            $selected = array_slice($pool, 0, min($notificationCount, count($pool)));

            foreach ($selected as $chosenId) {
                \DB::table('confirmation_requests')
                    ->where('student_id', $chosenId)
                    ->where('status', 'pending')
                    ->update(['status' => 'closed']);

                \DB::table('confirmation_requests')->insert([
                    'session_id' => $session->id,
                    'student_id' => $chosenId,
                    'status'     => 'pending',
                    'expires_at' => Carbon::now()->addHour(1),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);

                \DB::table('notifications')->insert([
                    'student_id'  => $chosenId,
                    'session_id'  => $session->id,
                    'message'     => 'Please confirm: is your teacher present in the classroom?',
                    'type'        => 'teacher_verification',
                    'is_read'     => 0,
                    'created_at'  => Carbon::now(),
                    'updated_at'  => Carbon::now(),
                ]);

                $notifiedStudents[] = $chosenId;
            }
        }

        return response()->json([
            'success'              => true,
            'message'              => 'Attendance submitted successfully',
            'total_submitted'      => count($markedIds),
            'total_present'        => $totalPresent,
            'notifications_sent'   => count($notifiedStudents),
            'notified_student_ids' => $notifiedStudents,
            'note'                 => !$isBsClass
                ? 'Verification not applicable — not a BS class'
                : ($totalPresent > 0
                    ? $notificationCount . ' of ' . $totalPresent . ' present students selected for verification (20% rule)'
                    : 'No present students — no verification sent'),
        ], 201);
    }

    public function getNotifications($studentId)
    {
        $notifications = \DB::table('notifications')
            ->where('student_id', $studentId)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        $unreadCount = \DB::table('notifications')
            ->where('student_id', $studentId)
            ->where('is_read', 0)
            ->count();

        return response()->json([
            'success'      => true,
            'unread_count' => $unreadCount,
            'count'        => $notifications->count(),
            'data'         => $notifications,
        ]);
    }

    public function markNotificationsRead($studentId)
    {
        \DB::table('notifications')
            ->where('student_id', $studentId)
            ->where('is_read', 0)
            ->update(['is_read' => 1]);

        return response()->json(['success' => true]);
    }

    // STUDENT DASHBOARD SUMMARY (today's status + this month's counts)
    public function getStudentDashboardStatus($studentId)
    {
        $today = Carbon::today()->toDateString();

        $todayRecord = Attendance::where('student_id', $studentId)
            ->where('attendance_date', $today)
            ->first();

        $todayStatus = $todayRecord ? $todayRecord->status : 'not_marked';

        $monthStart = Carbon::now()->startOfMonth()->toDateString();
        $monthEnd   = Carbon::now()->endOfMonth()->toDateString();

        $monthRecords = Attendance::where('student_id', $studentId)
            ->whereBetween('attendance_date', [$monthStart, $monthEnd])
            ->get();

        $present = $monthRecords->where('status', 'present')->count();
        $late    = $monthRecords->where('status', 'late')->count();
        $absent  = $monthRecords->where('status', 'absent')->count();

        $totalMarked = $monthRecords->count();
        $overallPercentage = $totalMarked > 0
            ? round((($present + $late) / $totalMarked) * 100, 1)
            : 0;

        return response()->json([
            'success' => true,
            'today_status' => $todayStatus,
            'overall_percentage' => $overallPercentage,
            'this_month' => [
                'present' => $present,
                'late'    => $late,
                'absent'  => $absent,
            ],
        ]);
    }
}