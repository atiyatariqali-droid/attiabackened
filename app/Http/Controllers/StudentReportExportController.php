<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StudentReportExportController extends AdminReportExportController
{
    private function authStudentId(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            abort(403, 'Unauthorized Access');
        }
        return $user->id;
    }

    //Report export pdf for student
    public function exportMyPdf(Request $request)
    {
        $studentId = $this->authStudentId($request);
        return parent::exportStudentPdf($request, $studentId);
    }

    //Report export excel for student
    public function exportMyExcel(Request $request)
    {
        $studentId = $this->authStudentId($request);
        return parent::exportStudentExcel($request, $studentId);
    }
}