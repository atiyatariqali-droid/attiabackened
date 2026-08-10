<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminReportController extends Controller
{
    // GET /api/admin/reports/stats?class_id=&teacher_id=&days=7
    public function getStats(Request $request)
    {
        $classId     = $request->query('class_id');
        $teacherId   = $request->query('teacher_id');
        $days        = $request->query('days');
        $status      = $request->query('status');
        $sessionId   = $request->query('session_id');
        $studentId   = $request->query('student_id');
        $studentName = $request->query('student_name');
        $date        = $request->query('date');
        $startDate   = $request->query('start_date');
        $endDate     = $request->query('end_date');

        // Total sessions query
        $sessionQuery = DB::table('attendance_sessions');
        
        if ($date) {
            $sessionQuery->whereDate('start_time', $date);
        } elseif ($startDate && $endDate) {
            $sessionQuery->whereBetween('start_time', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()]);
        } elseif ($days) {
            $sessionQuery->where('start_time', '>=', now()->subDays((int)$days));
        } else {
            $sessionQuery->where('start_time', '>=', now()->subDays(7));
        }

        if ($classId)   $sessionQuery->where('class_id', $classId);
        if ($teacherId) $sessionQuery->where('teacher_id', $teacherId);
        if ($sessionId) $sessionQuery->where('id', $sessionId);
        
        $totalSessions = $sessionQuery->count();

        // Total students query
        $studentQuery = DB::table('users')->where('role', 'student');
        if ($classId) {
            $className = DB::table('manage_classes')->where('id', $classId)->value('class_name');
            if ($className) $studentQuery->where('class', $className);
        }
        if ($teacherId) $studentQuery->where('teacher_id', $teacherId);
        if ($studentId) $studentQuery->where('id', $studentId);
        if ($studentName) $studentQuery->where('username', 'like', "%{$studentName}%");
        $totalStudents = $studentQuery->count();

        // Attendance Query (Querying attendance table directly)
        $attendanceQuery = DB::table('attendance as a')
            ->join('users as s', 's.id', '=', 'a.student_id');

        if ($date) {
            $attendanceQuery->whereDate('a.attendance_date', $date);
        } elseif ($startDate && $endDate) {
            $attendanceQuery->whereBetween('a.attendance_date', [$startDate, $endDate]);
        } elseif ($days) {
            $attendanceQuery->where('a.attendance_date', '>=', now()->subDays((int)$days)->toDateString());
        } else {
            $attendanceQuery->where('a.attendance_date', '>=', now()->subDays(7)->toDateString());
        }

        if ($classId)   $attendanceQuery->where('a.class_id', $classId);
        if ($teacherId) $attendanceQuery->where('s.teacher_id', $teacherId);
        if ($sessionId) $attendanceQuery->where('a.session_id', $sessionId);
        if ($studentId) $attendanceQuery->where('s.id', $studentId);
        if ($studentName) $attendanceQuery->where('s.username', 'like', "%{$studentName}%");
        if ($status)    $attendanceQuery->where('a.status', $status);

        $totalMarked  = (clone $attendanceQuery)->count();
        $presentCount = (clone $attendanceQuery)->whereIn('a.status', ['present', 'late'])->count();
        $attendancePct = $totalMarked > 0 ? round(($presentCount / $totalMarked) * 100, 1) : 0;

        // Previous period trend (default comparison)
        $compareDays = (int)($days ?? 7);
        $prevQuery = DB::table('attendance as a')
            ->join('users as s', 's.id', '=', 'a.student_id')
            ->whereBetween('a.attendance_date', [
                now()->subDays($compareDays * 2)->toDateString(),
                now()->subDays($compareDays)->toDateString()
            ]);

        if ($classId)   $prevQuery->where('a.class_id', $classId);
        if ($teacherId) $prevQuery->where('s.teacher_id', $teacherId);
        if ($sessionId) $prevQuery->where('a.session_id', $sessionId);
        if ($studentId) $prevQuery->where('s.id', $studentId);
        if ($studentName) $prevQuery->where('s.username', 'like', "%{$studentName}%");
        if ($status)    $prevQuery->where('a.status', $status);

        $prevMarked  = (clone $prevQuery)->count();
        $prevPresent = (clone $prevQuery)->whereIn('a.status', ['present', 'late'])->count();
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
        $classId     = $request->query('class_id');
        $teacherId   = $request->query('teacher_id');
        $days        = (int) ($request->query('days', 7));
        $status      = $request->query('status');
        $sessionId   = $request->query('session_id');
        $studentId   = $request->query('student_id');
        $studentName = $request->query('student_name');
        $date        = $request->query('date');
        $startDate   = $request->query('start_date');
        $endDate     = $request->query('end_date');
        $data        = [];

        $datesList = [];
        if ($date) {
            $datesList[] = $date;
        } elseif ($startDate && $endDate) {
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);
            $diff = min($start->diffInDays($end), 30);
            for ($i = 0; $i <= $diff; $i++) {
                $datesList[] = $start->copy()->addDays($i)->toDateString();
            }
        } else {
            for ($i = $days - 1; $i >= 0; $i--) {
                $datesList[] = now()->subDays($i)->toDateString();
            }
        }

        foreach ($datesList as $dStr) {
            $query = DB::table('attendance as a')
                ->join('users as s', 's.id', '=', 'a.student_id')
                ->whereDate('a.attendance_date', $dStr);

            if ($classId)   $query->where('a.class_id', $classId);
            if ($teacherId) $query->where('s.teacher_id', $teacherId);
            if ($sessionId) $query->where('a.session_id', $sessionId);
            if ($studentId) $query->where('s.id', $studentId);
            if ($studentName) $query->where('s.username', 'like', "%{$studentName}%");
            if ($status)    $query->where('a.status', $status);

            $total   = (clone $query)->count();
            $present = (clone $query)->whereIn('a.status', ['present', 'late'])->count();
            $pct     = $total > 0 ? round(($present / $total) * 100, 1) : 0;

            $data[] = [
                'date'  => $dStr,
                'label' => strtoupper(Carbon::parse($dStr)->format('D d')),
                'pct'   => $pct,
            ];
        }

        return response()->json(['chart' => $data]);
    }

    // GET /api/admin/reports/students?class_id=&teacher_id=&days=7
    public function getStudentsList(Request $request)
    {
        $classId     = $request->query('class_id');
        $teacherId   = $request->query('teacher_id');
        $days        = $request->query('days');
        $status      = $request->query('status');
        $sessionId   = $request->query('session_id');
        $studentId   = $request->query('student_id');
        $studentIds  = $request->query('student_ids'); //for multi-student selection
        $studentName = $request->query('student_name');
        $date        = $request->query('date');
        $startDate   = $request->query('start_date');
        $endDate     = $request->query('end_date');

        if ($days === null && !$date && !$startDate) {
            $days = 7;
        }

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
        if ($studentId) {
            $studentQuery->where('s.id', $studentId);
        }
        if ($studentName) {
            $studentQuery->where('s.username', 'like', "%{$studentName}%");
        }
        if ($studentIds) {
            $ids = is_array($studentIds) ? $studentIds : explode(',', $studentIds);
            $studentQuery->whereIn('s.id', $ids);
        }

        $students = $studentQuery->orderBy('s.class')->orderBy('s.username')->get();

        $result = [];
        foreach ($students as $student) {
            $query = DB::table('attendance as a')
                ->where('a.student_id', $student->student_id);

            // Apply date filters
            if ($date) {
                $query->whereDate('a.attendance_date', $date);
            } elseif ($startDate && $endDate) {
                $query->whereBetween('a.attendance_date', [$startDate, $endDate]);
            } elseif ($days) {
                $query->where('a.attendance_date', '>=', now()->subDays((int)$days)->toDateString());
            }

            if ($sessionId) {
                $query->where('a.session_id', $sessionId);
            }

            $total   = (clone $query)->count();
            $present = (clone $query)->where('status', 'present')->count();
            $absent  = (clone $query)->where('status', 'absent')->count();
            $late    = (clone $query)->where('status', 'late')->count();

            // Filter status if status is specified
            if ($status) {
                $hasStatusRecord = (clone $query)->where('status', $status)->exists();
                if (!$hasStatusRecord) {
                    continue;
                }
            }

            $attended = $present + $late;
            $pct = $total > 0 ? round(($attended / $total) * 100, 1) : 0;

            if ($total === 0) {
                $studentStatus = 'no_data';
            } elseif ($pct < 50) {
                $studentStatus = 'critical';
            } elseif ($pct < 75) {
                $studentStatus = 'warning';
            } else {
                $studentStatus = 'good';
            }

            $result[] = [
                'student_id'   => $student->student_id,
                'student_name' => $student->student_name ?? 'Unknown',
                'roll_no'      => $student->roll_no ?? '-',
                'class_name'   => $student->class_name ?? '-',
                'teacher_name' => $student->teacher_name ?? 'Not Assigned',
                'present'      => $present + $late,
                'absent'       => $absent,
                'total'        => $total,
                'pct'          => $pct,
                'status'       => $studentStatus,
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

    // GET /api/admin/reports/student/{id}
    // public function getStudentDetailReport(Request $request, $id)
    // {
    //     $student = DB::table('users as s')
    //         ->where('s.id', $id)
    //         ->where('s.role', 'student')
    //         ->leftJoin('users as t', 't.id', '=', 's.teacher_id')
    //         ->select(
    //             's.id as student_id',
    //             's.username as student_name',
    //             's.roll_no',
    //             's.class as class_name',
    //             't.username as teacher_name'
    //         )
    //         ->first();

    //     if (!$student) {
    //         return response()->json(['message' => 'Student not found'], 404);
    //     }

    //     // Fetch attendance logs for this student
    //     $attendanceLogs = DB::table('attendance as a')
    //         ->where('a.student_id', $id)
    //         ->leftJoin('manage_classes as c', 'c.id', '=', 'a.class_id')
    //         ->select(
    //             'a.id as attendance_id',
    //             'a.attendance_date',
    //             'a.status',
    //             'c.class_name'
    //         );
    //         // ->orderBy('a.attendance_date', 'desc')
    //         // ->get();
    //     // NEW: optional date range filter (used by teacher/student personal reports)
    //     $startDate = $request->query('start_date');
    //     $endDate   = $request->query('end_date');
    //     if ($startDate && $endDate) {
    //         $logsQuery->whereBetween('a.attendance_date', [$startDate, $endDate]);
    //     }

    //     $attendanceLogs = $logsQuery->orderBy('a.attendance_date', 'desc')->get();
    //     $totalClasses = $attendanceLogs->count();
    //     $presentCount = $attendanceLogs->where('status', 'present')->count();
    //     $absentCount  = $attendanceLogs->where('status', 'absent')->count();
    //     $lateCount    = $attendanceLogs->where('status', 'late')->count();

    //     // Calculate attendance percentage (present + late counts as attended)
    //     $attendedCount = $presentCount + $lateCount;
    //     $attendancePercentage = $totalClasses > 0 ? round(($attendedCount / $totalClasses) * 100, 1) : 0.0;

    //     $records = [];
    //     foreach ($attendanceLogs as $log) {
    //         $remarks = '-';
    //         if ($log->status === 'present') {
    //             $remarks = 'On Time';
    //         } elseif ($log->status === 'late') {
    //             $remarks = 'Late';
    //         } elseif ($log->status === 'absent') {
    //             $remarks = 'Absent';
    //         }

    //         // Check if there is an audit log for this attendance record
    //         $audit = DB::table('attendance_audit_logs')
    //             ->where('attendance_id', $log->attendance_id)
    //             ->orderBy('created_at', 'desc')
    //             ->first();

    //         $auditData = null;
    //         if ($audit) {
    //             $auditData = [
    //                 'admin_name'      => $audit->admin_name,
    //                 'original_status' => ucfirst($audit->original_status),
    //                 'updated_status'  => ucfirst($audit->updated_status),
    //                 'edited_at'       => Carbon::parse($audit->created_at)->toDateTimeString(),
    //             ];
    //             $remarks = 'Edited by ' . $audit->admin_name;
    //         }

    //         $records[] = [
    //             'id'        => $log->attendance_id,
    //             'date'      => $log->attendance_date,
    //             'status'    => ucfirst($log->status),
    //             'subject'   => $log->class_name ?? 'Class',
    //             'remarks'   => $remarks,
    //             'audit_log' => $auditData,
    //         ];
    //     }

    //     return response()->json([
    //         'student_details' => [
    //             'full_name'    => $student->student_name,
    //             'roll_number'  => $student->roll_no ?? '-',
    //             'class'        => $student->class_name ?? '-',
    //             'teacher_name' => $student->teacher_name ?? 'Not Assigned',
    //         ],
    //         'summary' => [
    //             'total_classes'         => $totalClasses,
    //             'present_count'         => $presentCount,
    //             'absent_count'          => $absentCount,
    //             'late_count'            => $lateCount,
    //             'attendance_percentage' => $attendancePercentage,
    //         ],
    //         'records' => $records,
    //     ]);
    // }
    // GET /api/admin/reports/student/{id}
public function getStudentDetailReport(Request $request, $id)
{
    // Get student
    $student = DB::table('users as s')
        ->where('s.id', $id)
        ->where('s.role', 'student')
        ->leftJoin('users as t', 't.id', '=', 's.teacher_id')
        ->select(
            's.id as student_id',
            's.username as student_name',
            's.roll_no',
            's.class as class_name',
            't.username as teacher_name'
        )
        ->first();

    if (!$student) {
        return response()->json([
            'message' => 'Student not found'
        ], 404);
    }

    // Build attendance query
    $logsQuery = DB::table('attendance as a')
        ->where('a.student_id', $id)
        ->leftJoin('manage_classes as c', 'c.id', '=', 'a.class_id')
        ->select(
            'a.id as attendance_id',
            'a.attendance_date',
            'a.status',
            'c.class_name'
        );

    // Optional date range filter
    $startDate = $request->query('start_date');
    $endDate   = $request->query('end_date');

    if ($startDate && $endDate) {
        $logsQuery->whereBetween('a.attendance_date', [
            $startDate,
            $endDate
        ]);
    } elseif ($startDate) {
        $logsQuery->whereDate('a.attendance_date', '>=', $startDate);
    } elseif ($endDate) {
        $logsQuery->whereDate('a.attendance_date', '<=', $endDate);
    }

    // Get attendance records
    $attendanceLogs = $logsQuery
        ->orderBy('a.attendance_date', 'desc')
        ->get();

    // Calculate summary
    $totalClasses = $attendanceLogs->count();

    $presentCount = $attendanceLogs
        ->where('status', 'present')
        ->count();

    $absentCount = $attendanceLogs
        ->where('status', 'absent')
        ->count();

    $lateCount = $attendanceLogs
        ->where('status', 'late')
        ->count();

    $attendedCount = $presentCount + $lateCount;

    $attendancePercentage = $totalClasses > 0
        ? round(($attendedCount / $totalClasses) * 100, 1)
        : 0.0;

    // Build records
    $records = [];

    foreach ($attendanceLogs as $log) {

        $remarks = '-';

        if ($log->status === 'present') {
            $remarks = 'On Time';
        } elseif ($log->status === 'late') {
            $remarks = 'Late';
        } elseif ($log->status === 'absent') {
            $remarks = 'Absent';
        }

        // Check audit log
        $audit = DB::table('attendance_audit_logs')
            ->where('attendance_id', $log->attendance_id)
            ->orderBy('created_at', 'desc')
            ->first();

        $auditData = null;

        if ($audit) {
            $auditData = [
                'admin_name'      => $audit->admin_name,
                'original_status' => ucfirst($audit->original_status),
                'updated_status'  => ucfirst($audit->updated_status),
                'edited_at'       => Carbon::parse(
                    $audit->created_at
                )->toDateTimeString(),
            ];

            $remarks = 'Edited by ' . $audit->admin_name;
        }

        $records[] = [
            'id'        => $log->attendance_id,
            'date'      => $log->attendance_date,
            'status'    => ucfirst($log->status),
            'subject'   => $log->class_name ?? 'Class',
            'remarks'   => $remarks,
            'audit_log' => $auditData,
        ];
    }

    return response()->json([
        'student_details' => [
            'full_name'   => $student->student_name,
            'roll_number' => $student->roll_no ?? '-',
            'class'       => $student->class_name ?? '-',
            'teacher_name' => $student->teacher_name ?? 'Not Assigned',
        ],

        'summary' => [
            'total_classes'         => $totalClasses,
            'present_count'         => $presentCount,
            'absent_count'          => $absentCount,
            'late_count'            => $lateCount,
            'attendance_percentage' => $attendancePercentage,
        ],

        'records' => $records,
    ]);
}

    // PUT /api/admin/reports/attendance/{id}
    public function updateAttendance(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:present,absent,late',
        ]);

        $attendance = DB::table('attendance')->where('id', $id)->first();
        if (!$attendance) {
            return response()->json(['success' => false, 'message' => 'Attendance record not found'], 404);
        }

        $originalStatus = $attendance->status;
        $updatedStatus  = $request->status;

        // Update the status
        DB::table('attendance')->where('id', $id)->update([
            'status'     => $updatedStatus,
            'updated_at' => now(),
        ]);

        // Get admin details
        $adminId   = auth()->id() ?? 1;
        $adminName = auth()->user()->username ?? 'Admin';

        // Write to audit log
        DB::table('attendance_audit_logs')->insert([
            'attendance_id'   => $id,
            'original_status' => $originalStatus,
            'updated_status'  => $updatedStatus,
            'admin_id'        => $adminId,
            'admin_name'      => $adminName,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Attendance status updated successfully and recorded in audit trail',
            'data'    => [
                'id'              => $id,
                'original_status' => $originalStatus,
                'updated_status'  => $updatedStatus,
                'admin_name'      => $adminName,
            ]
        ]);
    }

    // GET /api/admin/reports/teachers-summary
    public function getTeachersSummaryReport(Request $request)
    {
        $teachers = DB::table('users')
            ->where('role', 'teacher')
            ->select('id', 'username as name', 'email', 'phone')
            ->get();

        $result = [];
        foreach ($teachers as $teacher) {
            $totalSessions = DB::table('attendance_sessions')
                ->where('teacher_id', $teacher->id)
                ->count();

            $totalStudents = DB::table('users')
                ->where('role', 'student')
                ->where('teacher_id', $teacher->id)
                ->count();

            $attendanceQuery = DB::table('attendance as a')
                ->join('attendance_sessions as s', 's.id', '=', 'a.session_id')
                ->where('s.teacher_id', $teacher->id);

            $totalMarked = (clone $attendanceQuery)->count();
            $presentCount = (clone $attendanceQuery)->whereIn('a.status', ['present', 'late'])->count();
            $absentCount = (clone $attendanceQuery)->where('a.status', 'absent')->count();
            
            $attendancePct = $totalMarked > 0 ? round(($presentCount / $totalMarked) * 100, 1) : 0;

            $result[] = [
                'teacher_id'      => $teacher->id,
                'teacher_name'    => $teacher->name,
                'email'           => $teacher->email,
                'phone'           => $teacher->phone ?? '-',
                'total_sessions'  => $totalSessions,
                'total_students'  => $totalStudents,
                'present_count'   => $presentCount,
                'absent_count'    => $absentCount,
                'total_marked'    => $totalMarked,
                'attendance_pct'  => $attendancePct,
            ];
        }

        return response()->json(['teachers' => $result]);
    }

    // GET /api/admin/reports/classes-summary
    public function getClassesSummaryReport(Request $request)
    {
        $query = DB::table('manage_classes');
        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }
        
        $classes = $query->select('id', 'name as class_name', 'class_name as teacher_username', 'students_count', 'status')
            ->get();

        $result = [];
        foreach ($classes as $class) {
            $totalSessions = DB::table('attendance_sessions')
                ->where('class_id', $class->id)
                ->count();

            $totalStudents = DB::table('users')
                ->where('role', 'student')
                ->where('class', $class->class_name)
                ->count();

            $attendanceQuery = DB::table('attendance')
                ->where('class_id', $class->id);

            $totalMarked = (clone $attendanceQuery)->count();
            $presentCount = (clone $attendanceQuery)->whereIn('status', ['present', 'late'])->count();
            $absentCount = (clone $attendanceQuery)->where('status', 'absent')->count();

            $attendancePct = $totalMarked > 0 ? round(($presentCount / $totalMarked) * 100, 1) : 0;

            $result[] = [
                'class_id'        => $class->id,
                'class_name'      => $class->class_name,
                'teacher_name'    => $class->teacher_username ?? 'Not Assigned',
                'status'          => $class->status,
                'total_sessions'  => $totalSessions,
                'total_students'  => $totalStudents ?: $class->students_count,
                'present_count'   => $presentCount,
                'absent_count'    => $absentCount,
                'total_marked'    => $totalMarked,
                'attendance_pct'  => $attendancePct,
            ];
        }

        return response()->json(['classes' => $result]);
    }

    // GET /api/admin/reports/sessions-summary
    public function getSessionsSummaryReport(Request $request)
    {
        $sessionsQuery = DB::table('attendance_sessions as s')
            ->leftJoin('users as t', 't.id', '=', 's.teacher_id')
            ->leftJoin('manage_classes as c', 'c.id', '=', 's.class_id')
            ->select(
                's.id as session_id',
                's.created_at',
                's.status',
                't.username as teacher_name',
                'c.name as class_name'
            )
            ->orderBy('s.created_at', 'desc');

        if ($request->filled('teacher_id')) {
            $sessionsQuery->where('s.teacher_id', $request->teacher_id);
        }
        if ($request->filled('class_id')) {
            $sessionsQuery->where('s.class_id', $request->class_id);
        }

        $sessions = $sessionsQuery->get();

        $result = [];
        foreach ($sessions as $session) {
            $attendanceQuery = DB::table('attendance')
                ->where('session_id', $session->session_id);

            $totalMarked = (clone $attendanceQuery)->count();
            $presentCount = (clone $attendanceQuery)->where('status', 'present')->count();
            $lateCount = (clone $attendanceQuery)->where('status', 'late')->count();
            $absentCount = (clone $attendanceQuery)->where('status', 'absent')->count();

            $attendancePct = $totalMarked > 0 ? round((($presentCount + $lateCount) / $totalMarked) * 100, 1) : 0;

            $confirmationRequests = DB::table('confirmation_requests')
                ->where('session_id', $session->session_id)
                ->pluck('id');

            $verdict = 'No verification';
            if ($confirmationRequests->isNotEmpty()) {
                $responses = DB::table('confirmation_responses')
                    ->whereIn('request_id', $confirmationRequests)
                    ->get();

                $yesCount = $responses->where('response', 'yes')->count();
                $noCount = $responses->where('response', 'no')->count();
                $totalResponses = $responses->count();

                if ($totalResponses > 0) {
                    $verdict = $yesCount >= $noCount ? 'Teacher Present' : 'Teacher NOT Present';
                } else {
                    $verdict = 'Awaiting responses';
                }
            }

            $result[] = [
                'session_id'     => $session->session_id,
                'class_name'     => $session->class_name ?? 'Unknown Class',
                'teacher_name'   => $session->teacher_name ?? 'Unknown Teacher',
                'status'         => $session->status,
                'date_time'      => \Carbon\Carbon::parse($session->created_at)->format('Y-m-d h:i A'),
                'present_count'  => $presentCount,
                'late_count'     => $lateCount,
                'absent_count'   => $absentCount,
                'total_marked'   => $totalMarked,
                'attendance_pct' => $attendancePct,
                'verdict'        => $verdict,
            ];
        }

        return response()->json(['sessions' => $result]);
    }
}