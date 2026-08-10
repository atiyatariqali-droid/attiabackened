<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeacherReportExportController extends AdminReportExportController
{
    private function authTeacherId(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->hasRole('teacher')) {
            abort(403, 'Unauthorized Access');
        }
        return $user->id;
    }

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

    // GET /api/teacher/reports/export/pdf
    // Handles: full class report AND multiple-selected-students report
    // (pass ?student_ids=1,2,3 in query to restrict to selected students)
    public function exportClassPdf(Request $request)
    {
        $teacherId = $this->authTeacherId($request);
        $request->query->set('teacher_id', $teacherId); // force scope
        return parent::exportPdf($request); // reuses AdminReportExportController logic fully
    }

    // GET /api/teacher/reports/export/excel
    public function exportClassExcel(Request $request)
    {
        $teacherId = $this->authTeacherId($request);
        $request->query->set('teacher_id', $teacherId);
        return parent::exportExcel($request);
    }

    // GET /api/teacher/reports/student/{id}/export/pdf
    public function exportStudentPdf(Request $request, $id)
    {
        $teacherId = $this->authTeacherId($request);
        $this->assertOwnsStudent($teacherId, $id);
        return parent::exportStudentPdf($request, $id);
    }

    // GET /api/teacher/reports/student/{id}/export/excel
    public function exportStudentExcel(Request $request, $id)
    {
        $teacherId = $this->authTeacherId($request);
        $this->assertOwnsStudent($teacherId, $id);
        return parent::exportStudentExcel($request, $id);
    }
}