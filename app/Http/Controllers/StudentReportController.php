<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StudentReportController extends Controller
{
    private function authStudentId(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            abort(403, 'Unauthorized Access');
        }
        return $user->id; // identity always from token, never from client input
    }

    // GET /api/student/reports/my-report  (supports ?start_date=&end_date= filters)
    public function getMyReport(Request $request)
    {
        $studentId = $this->authStudentId($request);

        $admin = new AdminReportController();
        return $admin->getStudentDetailReport($request, $studentId);
    }
}