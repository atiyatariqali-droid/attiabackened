<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TeacherDashboardController extends Controller
{
    // GET /api/teacher/{teacher_id}/dashboard-stats
    // Powers the teacher dashboard's welcome/stats card.
    //
    // Confirmed against actual schema:
    //   manage_classes:      id, teacher_id, status, ...
    //   users:                id, role, status, class_id, ... (no teacher_id — link is via class_id)
    //   attendance_sessions: id, teacher_id, class_id, start_time, end_time, status ('active'/'inactive')
    //                        (created_at is always NULL here, so date filters use start_time)
    //   attendance:          id, student_id, class_id, attendance_date, status ('present'/...), session_id
    //   confirmation_requests: id, session_id, student_id, status ('pending'/'closed'), expires_at
    //   confirmation_responses is currently unused/empty — pending state lives on
    //   confirmation_requests.status instead.
    public function stats(Request $request, $teacherId)
    {
        // Classes this teacher teaches.
        $classIds = DB::table('manage_classes')
            ->where('teacher_id', $teacherId)
            ->pluck('class_group_id');

        // 1. My total students — students enrolled in any of this teacher's classes.
        $myTotalStudents = User::where('role', 'student')
            ->where('status', 1)
            ->whereIn('class_id', $classIds)
            ->count();

        // 2. Active sessions today (by start_time, since created_at is unused here).
        $activeSessionsToday = DB::table('attendance_sessions')
            ->where('teacher_id', $teacherId)
            ->where('status', 'active')
            ->whereDate('start_time', now()->toDateString())
            ->count();

        // 3. Today's attendance % — present out of all attendance marked today
        //    across this teacher's sessions.
        $totalMarked = DB::table('attendance')
            ->join('attendance_sessions', 'attendance.session_id', '=', 'attendance_sessions.id')
            ->where('attendance_sessions.teacher_id', $teacherId)
            ->whereDate('attendance.attendance_date', now()->toDateString())
            ->count();

        $presentMarked = DB::table('attendance')
            ->join('attendance_sessions', 'attendance.session_id', '=', 'attendance_sessions.id')
            ->where('attendance_sessions.teacher_id', $teacherId)
            ->whereDate('attendance.attendance_date', now()->toDateString())
            ->where('attendance.status', 'present')
            ->count();

        $todayAttendancePercent = $totalMarked > 0
            ? round(($presentMarked / $totalMarked) * 100)
            : 0;

        // 4. Pending confirmation requests across this teacher's sessions.
        $pendingConfirmations = DB::table('confirmation_requests')
            ->join('attendance_sessions', 'confirmation_requests.session_id', '=', 'attendance_sessions.id')
            ->where('attendance_sessions.teacher_id', $teacherId)
            ->where('confirmation_requests.status', 'pending')
            ->count();

        return response()->json([
            'my_total_students'      => $myTotalStudents,
            'active_sessions_today'  => $activeSessionsToday,
            'today_attendance_pct'   => $todayAttendancePercent,
            'pending_confirmations'  => $pendingConfirmations,
        ]);
    }
}