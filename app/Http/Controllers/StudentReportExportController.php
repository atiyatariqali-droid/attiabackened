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

    // GET /api/student/reports/export/pdf
    public function exportMyPdf(Request $request)
    {
        $studentId = $this->authStudentId($request);
        return parent::exportStudentPdf($request, $studentId);
    }

    // GET /api/student/reports/export/excel
    public function exportMyExcel(Request $request)
    {
        $studentId = $this->authStudentId($request);
        return parent::exportStudentExcel($request, $studentId);
    }
}