<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeacherReportExportController extends AdminReportExportController
{
    private function authTeacherId(Request $request)
    {
        $user = $request->user();
        if (!$user || $user->role !== 'teacher') {
            abort(403, 'Unauthorized Access');
        }
        return $user->id;
    }

    private function assertOwnsStudent($teacherId, $studentId)
    {
        $belongs = DB::table('users')
            ->join('class_groups', 'users.class_id', '=', 'class_groups.id')
            ->join('manage_classes', 'manage_classes.class_group_id', '=', 'class_groups.id')
            ->where('users.id', $studentId)
            ->where('users.role', 'student')
            ->where('manage_classes.teacher_id', $teacherId)
            ->exists();

        if (!$belongs) {
            abort(403, 'Unauthorized Access - student not in your class');
        }
    }

    //Export pdf for all classes
    public function exportClassPdf(Request $request)
    {
        $teacherId = $this->authTeacherId($request);
        $request->query->set('teacher_id', $teacherId);
        return parent::exportPdf($request);
    }

    //Export excel for all classes
    public function exportClassExcel(Request $request)
    {
        $teacherId = $this->authTeacherId($request);
        $request->query->set('teacher_id', $teacherId);
        return parent::exportExcel($request);
    }

    //Export pdf for a student
    public function exportStudentPdf(Request $request, $id)
    {
        $teacherId = $this->authTeacherId($request);
        $this->assertOwnsStudent($teacherId, $id);
        return parent::exportStudentPdf($request, $id);
    }

    //Export excel for a student
    public function exportStudentExcel(Request $request, $id)
    {
        $teacherId = $this->authTeacherId($request);
        $this->assertOwnsStudent($teacherId, $id);
        return parent::exportStudentExcel($request, $id);
    }
}