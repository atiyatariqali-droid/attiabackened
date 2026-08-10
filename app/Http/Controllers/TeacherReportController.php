<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeacherReportController extends Controller
{
    // Resolve authenticated teacher id — 403 if not a teacher
    private function authTeacherId(Request $request)
    {
        $user = $request->user();
        if (!$user || $user->role !== 'teacher') {
            abort(403, 'Unauthorized Access');
        }
        return $user->id;
    }

    // Verify a student belongs to this teacher — 403 otherwise
    private function assertOwnsStudent($teacherId, $studentId)
    {
        $belongs = DB::table('users')
            ->where('id', $studentId)
            ->where('role', 'student')
            ->where('teacher_id', $teacherId)
            ->exists();

        if (!$belongs) {
            abort(403, 'Unauthorized Access - student not in your class');
        }
    }

    // GET /api/teacher/reports/stats
    public function getMyStats(Request $request)
    {
        $teacherId = $this->authTeacherId($request);
        $request->query->set('teacher_id', $teacherId); // force scope — overrides anything client sent

        $admin = new AdminReportController();
        return $admin->getStats($request);
    }

    // GET /api/teacher/reports/students  (supports class, student_id, student_ids, date range filters)
    public function getMyStudents(Request $request)
    {
        $teacherId = $this->authTeacherId($request);
        $request->query->set('teacher_id', $teacherId); // force scope

        $admin = new AdminReportController();
        return $admin->getStudentsList($request);
    }

    // GET /api/teacher/reports/student/{id}
    public function getStudentDetailReport(Request $request, $id)
    {
        $teacherId = $this->authTeacherId($request);
        $this->assertOwnsStudent($teacherId, $id);

        $admin = new AdminReportController();
        return $admin->getStudentDetailReport($request, $id);
    }
}