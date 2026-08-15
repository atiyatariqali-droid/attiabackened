<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AttendanceReportExport;
use App\Exports\StudentReportExport;

class AdminReportExportController extends Controller
{
    // GET /api/admin/reports/export/pdf
    public function exportPdf(Request $request)
    {
        $data = $this->buildReportPayload($request);

        $pdf = Pdf::loadView('reports.attendance_report', $data);
        $pdf->setPaper('a4', 'portrait');

        $filename = 'attendance_report_' . now()->format('Ymd_His') . '.pdf';
        return $pdf->download($filename);
    }

    // GET /api/admin/reports/export/excel
    public function exportExcel(Request $request)
    {
        $data = $this->buildReportPayload($request);
        $filename = 'attendance_report_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new AttendanceReportExport($data), $filename);
    }

    // GET /api/admin/reports/student/{id}/export/pdf
    public function exportStudentPdf(Request $request, $id)
    {
        $data = $this->buildStudentReportPayload($id);
        if ($data === null) {
            abort(404, 'Student not found');
        }

        $pdf = Pdf::loadView('reports.student_attendance_report', $data);
        $pdf->setPaper('a4', 'portrait');

        $filename = 'student_report_' . $id . '_' . now()->format('Ymd_His') . '.pdf';
        return $pdf->download($filename);
    }

    // GET /api/admin/reports/student/{id}/export/excel
    public function exportStudentExcel(Request $request, $id)
    {
        $data = $this->buildStudentReportPayload($id);
        if ($data === null) {
            abort(404, 'Student not found');
        }

        $filename = 'student_report_' . $id . '_' . now()->format('Ymd_His') . '.xlsx';
        return Excel::download(new StudentReportExport($data), $filename);
    }

    /**
     * Mirrors AdminReportController::getStudentDetailReport so the
     * exported file matches exactly what's shown in the student report modal.
     */
    private function buildStudentReportPayload($id): ?array
    {
        $student = DB::table('users as s')
            ->where('s.id', $id)
            ->where('s.role', 'student')
            ->leftJoin('users as t', 't.id', '=', 's.teacher_id')
            ->select('s.id as student_id', 's.username as student_name', 's.roll_no', 's.class as class_name', 't.username as teacher_name')
            ->first();

        if (!$student) {
            return null;
        }

        $attendanceLogs = DB::table('attendance as a')
            ->where('a.student_id', $id)
            ->leftJoin('manage_classes as c', 'c.id', '=', 'a.class_id')
            ->select('a.id as attendance_id', 'a.attendance_date', 'a.status', 'c.class_name')
            ->orderBy('a.attendance_date', 'desc')
            ->get();

        $totalClasses = $attendanceLogs->count();
        $presentCount = $attendanceLogs->where('status', 'present')->count();
        $absentCount  = $attendanceLogs->where('status', 'absent')->count();
        $lateCount    = $attendanceLogs->where('status', 'late')->count();
        $attendedCount = $presentCount + $lateCount;
        $attendancePercentage = $totalClasses > 0 ? round(($attendedCount / $totalClasses) * 100, 1) : 0.0;

        $records = [];
        foreach ($attendanceLogs as $log) {
            $remarks = ucfirst($log->status);
            $audit = DB::table('attendance_audit_logs')
                ->where('attendance_id', $log->attendance_id)
                ->orderBy('created_at', 'desc')
                ->first();
            if ($audit) {
                $remarks = 'Edited by ' . $audit->admin_name;
            }

            $records[] = [
                'date'    => $log->attendance_date,
                'status'  => ucfirst($log->status),
                'subject' => $log->class_name ?? 'Class',
                'remarks' => $remarks,
            ];
        }

        return [
            'generated_at' => now()->format('d M Y, h:i A'),
            'student' => [
                'full_name'    => $student->student_name,
                'roll_number'  => $student->roll_no ?? '-',
                'class'        => $student->class_name ?? '-',
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
        ];
    }

    /**
     * Rebuilds the same dataset shown on the Reports & Audit screen
     * (mirrors AdminReportController::getStats / getStudentsList) so the
     * export always reflects the currently applied filters.
     */
    private function buildReportPayload(Request $request): array
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

        if ($days === null && !$date && !$startDate) {
            $days = 7;
        }

        // Resolve human-readable filter labels
        $className = 'All Classes';
        if ($classId) {
            $className = DB::table('manage_classes')->where('id', $classId)->value('class_name') ?? 'All Classes';
        }
        $teacherName = 'All Faculty';
        if ($teacherId) {
            $teacherName = DB::table('users')->where('id', $teacherId)->value('username') ?? 'All Faculty';
        }
        $periodLabel = 'All Time';
        if ($date) {
            $periodLabel = 'Date: ' . $date;
        } elseif ($startDate && $endDate) {
            $periodLabel = "Range: {$startDate} to {$endDate}";
        } elseif ($days) {
            $periodLabel = "Last {$days} Days";
        }

        // ---- Stats ----
        $sessionQuery = DB::table('attendance_sessions');
        if ($date) {
            $sessionQuery->whereDate('start_time', $date);
        } elseif ($startDate && $endDate) {
            $sessionQuery->whereBetween('start_time', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()]);
        } elseif ($days) {
            $sessionQuery->where('start_time', '>=', now()->subDays((int)$days));
        }
        if ($classId)   $sessionQuery->where('class_id', $classId);
        if ($teacherId) $sessionQuery->where('teacher_id', $teacherId);
        if ($sessionId) $sessionQuery->where('id', $sessionId);
        $totalSessions = $sessionQuery->count();

        $studentCountQuery = DB::table('users')->where('role', 'student');
        if ($classId && $className !== 'All Classes') $studentCountQuery->where('class', $className);
        if ($teacherId) $studentCountQuery->where('teacher_id', $teacherId);
        if ($studentId) $studentCountQuery->where('id', $studentId);
        if ($studentName) $studentCountQuery->where('username', 'like', "%{$studentName}%");
        $totalStudents = $studentCountQuery->count();

        $attendanceQuery = DB::table('attendance as a')
            ->join('users as s', 's.id', '=', 'a.student_id')
            ->leftJoin('attendance_sessions as sess', 'sess.id', '=', 'a.session_id');
        if ($date) {
            $attendanceQuery->whereDate('a.attendance_date', $date);
        } elseif ($startDate && $endDate) {
            $attendanceQuery->whereBetween('a.attendance_date', [$startDate, $endDate]);
        } elseif ($days) {
            $attendanceQuery->where('a.attendance_date', '>=', now()->subDays((int)$days)->toDateString());
        }
        if ($classId)   $attendanceQuery->where('a.class_id', $classId);
        if ($teacherId) $attendanceQuery->where('sess.teacher_id', $teacherId);
        if ($sessionId) $attendanceQuery->where('a.session_id', $sessionId);
        if ($studentId) $attendanceQuery->where('s.id', $studentId);
        if ($studentName) $attendanceQuery->where('s.username', 'like', "%{$studentName}%");
        if ($status)    $attendanceQuery->where('a.status', $status);

        $totalMarked   = (clone $attendanceQuery)->count();
        $presentCount  = (clone $attendanceQuery)->whereIn('a.status', ['present', 'late'])->count();
        $attendancePct = $totalMarked > 0 ? round(($presentCount / $totalMarked) * 100, 1) : 0;

        // ---- Students list ----
        $studentQuery = DB::table('users as s')
            ->where('s.role', 'student')
            ->leftJoin('users as t', 't.id', '=', 's.teacher_id')
            ->select('s.id as student_id', 's.username as student_name', 's.roll_no', 's.class as class_name', 't.username as teacher_name');

        if ($classId && $className !== 'All Classes') $studentQuery->where('s.class', $className);
        if ($teacherId) {
            $studentQuery->where(function($q) use ($teacherId) {
                $q->where('s.teacher_id', $teacherId)
                  ->orWhereExists(function ($ex) use ($teacherId) {
                      $ex->select(DB::raw(1))
                        ->from('attendance as att')
                        ->join('attendance_sessions as sses', 'sses.id', '=', 'att.session_id')
                        ->whereRaw('att.student_id = s.id')
                        ->where('sses.teacher_id', $teacherId);
                  });
            });
        }
        if ($studentId) $studentQuery->where('s.id', $studentId);
        if ($studentName) $studentQuery->where('s.username', 'like', "%{$studentName}%");

        $students = $studentQuery->orderBy('s.class')->orderBy('s.username')->get();

        $rows = [];
        foreach ($students as $student) {
            $query = DB::table('attendance as a')
                ->leftJoin('attendance_sessions as sess', 'sess.id', '=', 'a.session_id')
                ->where('a.student_id', $student->student_id);
            if ($date) {
                $query->whereDate('a.attendance_date', $date);
            } elseif ($startDate && $endDate) {
                $query->whereBetween('a.attendance_date', [$startDate, $endDate]);
            } elseif ($days) {
                $query->where('a.attendance_date', '>=', now()->subDays((int)$days)->toDateString());
            }
            if ($sessionId) $query->where('a.session_id', $sessionId);
            if ($teacherId) $query->where('sess.teacher_id', $teacherId);

            $total   = (clone $query)->count();
            $present = (clone $query)->where('a.status', 'present')->count();
            $absent  = (clone $query)->where('a.status', 'absent')->count();
            $late    = (clone $query)->where('a.status', 'late')->count();

            if ($status) {
                $hasStatusRecord = (clone $query)->where('a.status', $status)->exists();
                if (!$hasStatusRecord) continue;
            }

            $attended = $present + $late;
            $pct = $total > 0 ? round(($attended / $total) * 100, 1) : 0;
            $studentStatus = $total === 0 ? 'No Data' : ($pct < 50 ? 'Critical' : ($pct < 75 ? 'Warning' : 'Good'));

            $rows[] = [
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

        return [
            'generated_at' => now()->format('d M Y, h:i A'),
            'filters' => [
                'class'        => $className,
                'teacher'      => $teacherName,
                'period'       => $periodLabel,
                'status'       => $status ? ucfirst($status) : 'All',
                'student_name' => $studentName ?: '-',
                'session_id'   => $sessionId ?: '-',
            ],
            'stats' => [
                'total_sessions' => $totalSessions,
                'total_students' => $totalStudents,
                'attendance_pct' => $attendancePct,
            ],
            'students' => $rows,
        ];
    }
}