<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TeacherDashboardController extends Controller
{
    //
    public function stats(Request $request, $teacherId)
    {
        // Classes this teacher teaches.
        $classIds = DB::table('manage_classes')
            ->where('teacher_id', $teacherId)
            ->pluck('class_group_id');

        //My total students
        $myTotalStudents = User::where('role', 'student')
            ->where('status', 1)
            ->whereIn('class_id', $classIds)
            ->count();

        // 2. Active sessions today
        $activeSessionsToday = DB::table('attendance_sessions')
            ->where('teacher_id', $teacherId)
            ->where('status', 'active')
            ->whereDate('start_time', now()->toDateString())
            ->count();

        // 3. Today's attendance percentage
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

        // 4. Pending confirmation requests 
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