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

        // Location check: must be within classroom radius (default 100m)
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

        // Step 2: Determine target users for verification
        $total            = count($markedIds);
        $notifiedStudents = [];
        $targetUserIds    = [];
        $message          = 'Please confirm: is your teacher present in the classroom?';

        $isBsClass = $this->isBsClass($classId);

        if ($total >= 10 && $isBsClass) {
            $pool = $markedIds;
            shuffle($pool);
            $selected3 = array_slice($pool, 0, 3);
            $targetUserIds = $selected3;
        } else {
            $targetUserIds = \DB::table('users')
                ->where('role', 'admin')
                ->pluck('id')
                ->toArray();
            
            if (!$isBsClass) {
                $message = "Non-BS Class session started. Please verify: is teacher {$session->teacher->username} present in class?";
            } else {
                $message = "Class session has less than 10 students present. Please verify: is teacher {$session->teacher->username} present in class?";
            }
        }

        // Step 3: Create confirmation requests and notifications
        $parentMode = \DB::table('system_settings')->where('key', 'parent_verification_mode')->value('value');
        $studentExpiryMinutes = ($parentMode === 'true' || $parentMode === '1' || $parentMode === 1) ? 1440 : 5;
        $adminExpiryMinutes = 10;

        foreach ($targetUserIds as $chosenId) {
            // Close any old pending requests for this user (cleanup)
            \DB::table('confirmation_requests')
                ->where('student_id', $chosenId)
                ->where('status', 'pending')
                ->update(['status' => 'closed']);

            $isStudent = ($total >= 10);
            $expiryMinutes = $isStudent ? $studentExpiryMinutes : $adminExpiryMinutes;

            // Per-user confirmation request
            \DB::table('confirmation_requests')->insert([
                'session_id' => $session->id,
                'student_id' => $chosenId,
                'status'     => 'pending',
                'expires_at' => Carbon::now()->addMinutes($expiryMinutes),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            // Notification
            \DB::table('notifications')->insert([
                'student_id'  => $chosenId,
                'session_id'  => $session->id,
                'message'     => $message,
                'type'        => 'teacher_verification',
                'is_read'     => 0,
                'created_at'  => Carbon::now(),
                'updated_at'  => Carbon::now(),
            ]);

            $notifiedStudents[] = $chosenId;
        }

        return response()->json([
            'success'              => true,
            'message'              => 'Attendance marked successfully',
            'total_marked'         => $total,
            'notifications_sent'   => count($notifiedStudents),
            'notified_student_ids' => $notifiedStudents,
            'note'                 => $total >= 10
                ? '3 random students selected for teacher verification'
                : 'Less than 10 present students — verification requests sent to admins',
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

        // Location check: must be within classroom radius (default 100m)
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
        $presentOrLateIds = [];

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

            if ($status === 'present' || $status === 'late') {
                $presentOrLateIds[] = $studentId;
            }
            $markedIds[] = $studentId;
        }

        // Verification logic
        $totalPresentOrLate = count($presentOrLateIds);
        $notifiedStudents = [];
        $targetUserIds = [];
        $message = 'Please confirm: is your teacher present in the classroom?';

        $isBsClass = $this->isBsClass($classId);

        if ($totalPresentOrLate >= 10 && $isBsClass) {
            $pool = $presentOrLateIds;
            shuffle($pool);
            $selected3 = array_slice($pool, 0, 3);
            $targetUserIds = $selected3;
        } else {
            $targetUserIds = \DB::table('users')
                ->where('role', 'admin')
                ->pluck('id')
                ->toArray();
            
            if (!$isBsClass) {
                $message = "Non-BS Class session started. Please verify: is teacher {$session->teacher->username} present in class?";
            } else {
                $message = "Class session has less than 10 students present. Please verify: is teacher {$session->teacher->username} present in class?";
            }
        }

        $parentMode = \DB::table('system_settings')->where('key', 'parent_verification_mode')->value('value');
        $studentExpiryMinutes = ($parentMode === 'true' || $parentMode === '1' || $parentMode === 1) ? 1440 : 5;
        $adminExpiryMinutes = 10;

        foreach ($targetUserIds as $chosenId) {
            // Close any old pending requests
            \DB::table('confirmation_requests')
                ->where('student_id', $chosenId)
                ->where('status', 'pending')
                ->update(['status' => 'closed']);

            $isStudent = ($totalPresentOrLate >= 10);
            $expiryMinutes = $isStudent ? $studentExpiryMinutes : $adminExpiryMinutes;

            // Create new request
            \DB::table('confirmation_requests')->insert([
                'session_id' => $session->id,
                'student_id' => $chosenId,
                'status'     => 'pending',
                'expires_at' => Carbon::now()->addMinutes($expiryMinutes),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            // Insert notification
            \DB::table('notifications')->insert([
                'student_id'  => $chosenId,
                'session_id'  => $session->id,
                'message'     => $message,
                'type'        => 'teacher_verification',
                'is_read'     => 0,
                'created_at'  => Carbon::now(),
                'updated_at'  => Carbon::now(),
            ]);

            $notifiedStudents[] = $chosenId;
        }

        return response()->json([
            'success'              => true,
            'message'              => 'Attendance submitted successfully',
            'total_submitted'      => count($markedIds),
            'total_present'        => $totalPresentOrLate,
            'notifications_sent'   => count($notifiedStudents),
            'notified_student_ids' => $notifiedStudents,
            'note'                 => $totalPresentOrLate >= 10
                ? '3 random students selected for teacher verification'
                : 'Less than 10 present/late students — verification requests sent to admins',
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
        $user = $request->user();

        $query = Attendance::with(['student', 'session.teacher'])
            ->orderBy('attendance_date', 'desc');

        if ($user->hasRole('teacher')) {
            $query->whereHas('session', function ($q) use ($user) {
                $q->where('teacher_id', $user->id);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date')) {
            $query->where('attendance_date', $request->date);
        }

        $records = $query->get()->map(function ($att) {
            return [
                'id'              => $att->id,
                'student_name'    => $att->student->name ?? 'Unknown',
                'status'          => $att->status,
                'attendance_date' => $att->attendance_date,
                'session_id'      => $att->session_id,
                'teacher_name'    => $att->session->teacher->name ?? 'Unknown',
            ];
        });

        return response()->json([
            'success' => true,
            'count'   => $records->count(),
            'data'    => $records,
        ]);
    }
}