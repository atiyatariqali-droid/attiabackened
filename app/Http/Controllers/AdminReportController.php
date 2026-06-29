<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminReportController extends Controller
{
    // GET /api/admin/reports/stats?class_id=&teacher_id=&days=7
    public function getStats(Request $request)
    {
        $classId   = $request->query('class_id');
        $teacherId = $request->query('teacher_id');
        $days      = (int) ($request->query('days', 7));

        // Total sessions
        $sessionQuery = DB::table('attendance_sessions')
            ->where('created_at', '>=', now()->subDays($days));
        if ($classId)   $sessionQuery->where('class_id', $classId);
        if ($teacherId) $sessionQuery->where('teacher_id', $teacherId);
        $totalSessions = $sessionQuery->count();

        // Total students in selected class
        $studentQuery = DB::table('users')->where('role', 'student');
        if ($classId) {
            // match by class_name from manage_classes
            $className = DB::table('manage_classes')->where('id', $classId)->value('class_name');
            if ($className) $studentQuery->where('class', $className);
        }
        if ($teacherId) $studentQuery->where('teacher_id', $teacherId);
        $totalStudents = $studentQuery->count();

        // Attendance %
        $sessionIds = (clone $sessionQuery)->pluck('id');
        $totalMarked  = DB::table('attendance')->whereIn('session_id', $sessionIds)->count();
        $presentCount = DB::table('attendance')->whereIn('session_id', $sessionIds)->where('status', 'present')->count();
        $attendancePct = $totalMarked > 0 ? round(($presentCount / $totalMarked) * 100, 1) : 0;

        // Previous period trend
        $prevIds = DB::table('attendance_sessions')
            ->whereBetween('created_at', [now()->subDays($days * 2), now()->subDays($days)])
            ->when($classId,   fn($q) => $q->where('class_id', $classId))
            ->when($teacherId, fn($q) => $q->where('teacher_id', $teacherId))
            ->pluck('id');
        $prevMarked  = DB::table('attendance')->whereIn('session_id', $prevIds)->count();
        $prevPresent = DB::table('attendance')->whereIn('session_id', $prevIds)->where('status', 'present')->count();
        $prevPct     = $prevMarked > 0 ? round(($prevPresent / $prevMarked) * 100, 1) : 0;
        $trend       = round($attendancePct - $prevPct, 1);

        return response()->json([
            'total_sessions' => $totalSessions,
            'total_students' => $totalStudents,
            'attendance_pct' => $attendancePct,
            'trend'          => $trend,
        ]);
    }

    // GET /api/admin/reports/chart?class_id=&teacher_id=&days=7
    public function getChartData(Request $request)
    {
        $classId   = $request->query('class_id');
        $teacherId = $request->query('teacher_id');
        $days      = (int) ($request->query('days', 7));
        $data      = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();

            $sessionIds = DB::table('attendance_sessions')
                ->whereDate('created_at', $date)
                ->when($classId,   fn($q) => $q->where('class_id', $classId))
                ->when($teacherId, fn($q) => $q->where('teacher_id', $teacherId))
                ->pluck('id');

            $total   = DB::table('attendance')->whereIn('session_id', $sessionIds)->count();
            $present = DB::table('attendance')->whereIn('session_id', $sessionIds)->where('status', 'present')->count();
            $pct     = $total > 0 ? round(($present / $total) * 100, 1) : 0;

            $data[] = [
                'date'  => $date,
                'label' => strtoupper(now()->subDays($i)->format('D')),
                'pct'   => $pct,
            ];
        }

        return response()->json(['chart' => $data]);
    }

    // GET /api/admin/reports/students?class_id=&teacher_id=&days=7
    public function getStudentsList(Request $request)
    {
        $classId   = $request->query('class_id');
        $teacherId = $request->query('teacher_id');
        $days      = (int) ($request->query('days', 7));

        // Build student query
        $studentQuery = DB::table('users as s')
            ->where('s.role', 'student')
            ->leftJoin('users as t', 't.id', '=', 's.teacher_id')
            ->select(
                's.id as student_id',
                's.username as student_name',
                's.roll_no',
                's.class as class_name',
                't.username as teacher_name'
            );

        if ($classId) {
            $className = DB::table('manage_classes')->where('id', $classId)->value('class_name');
            if ($className) $studentQuery->where('s.class', $className);
        }
        if ($teacherId) {
            $studentQuery->where('s.teacher_id', $teacherId);
        }

        $students = $studentQuery->orderBy('s.class')->orderBy('s.username')->get();

        // For each student, get attendance stats in the period
        $result = [];
        foreach ($students as $student) {
            // Get session ids in period for this student's class
            $classRecord = DB::table('manage_classes')->where('class_name', $student->class_name)->first();
            $classIdForStudent = $classRecord ? $classRecord->id : null;

            $sessionIds = DB::table('attendance_sessions')
                ->where('created_at', '>=', now()->subDays($days))
                ->when($classIdForStudent, fn($q) => $q->where('class_id', $classIdForStudent))
                ->pluck('id');

            $total   = DB::table('attendance')
                ->where('student_id', $student->student_id)
                ->whereIn('session_id', $sessionIds)
                ->count();

            $present = DB::table('attendance')
                ->where('student_id', $student->student_id)
                ->whereIn('session_id', $sessionIds)
                ->where('status', 'present')
                ->count();

            $absent = DB::table('attendance')
                ->where('student_id', $student->student_id)
                ->whereIn('session_id', $sessionIds)
                ->where('status', 'absent')
                ->count();

            $pct = $total > 0 ? round(($present / $total) * 100, 1) : 0;

            // Status logic
            if ($total === 0) {
                $status = 'no_data';
            } elseif ($pct < 50) {
                $status = 'critical';   // <50% attendance
            } elseif ($pct < 75) {
                $status = 'warning';    // 50-74%
            } else {
                $status = 'good';       // 75%+
            }

            $result[] = [
                'student_id'   => $student->student_id,
                'student_name' => $student->student_name ?? 'Unknown',
                'roll_no'      => $student->roll_no ?? '-',
                'class_name'   => $student->class_name ?? '-',
                'teacher_name' => $student->teacher_name ?? 'Not Assigned',
                'present'      => $present,
                'absent'       => $absent,
                'total'        => $total,
                'pct'          => $pct,
                'status'       => $status,
            ];
        }

        return response()->json(['students' => $result]);
    }

    // GET /api/admin/reports/classes
    public function getClasses()
    {
        $classes = DB::table('manage_classes')
            ->select('id', 'class_name')
            ->orderBy('class_name')
            ->get();
        return response()->json(['classes' => $classes]);
    }

    // GET /api/admin/reports/teachers
    public function getTeachers()
    {
        $teachers = DB::table('users')
            ->where('role', 'teacher')
            ->select('id', 'username as name')
            ->orderBy('username')
            ->get();
        return response()->json(['teachers' => $teachers]);
    }
}