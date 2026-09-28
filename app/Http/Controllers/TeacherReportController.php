<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeacherReportController extends Controller
{
    // Resolve authenticated teacher id  403 if not a teacher
    private function authTeacherId(Request $request)
    {
        $user = $request->user();
        if (!$user || $user->role !== 'teacher') {
            abort(403, 'Unauthorized Access');
        }
        return $user->id;
    }

    // Verify a student belongs to this teacher 403 otherwise
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

    //Stats for the logged-in teacher
    public function getMyStats(Request $request)
    {
        $teacherId = $this->authTeacherId($request);
        $request->query->set('teacher_id', $teacherId); // force scope — overrides anything client sent

        $admin = new AdminReportController();
        return $admin->getStats($request);
    }

    // Get all students 
    public function getMyStudents(Request $request)
    {
        $teacherId = $this->authTeacherId($request);
        $request->query->set('teacher_id', $teacherId); 

        $admin = new AdminReportController();
        return $admin->getStudentsList($request);
    }

    //Student detail report for a student 
    public function getStudentDetailReport(Request $request, $id)
    {
        $teacherId = $this->authTeacherId($request);
        $this->assertOwnsStudent($teacherId, $id);

        $admin = new AdminReportController();
        return $admin->getStudentDetailReport($request, $id);
    }

    //Chart data
    public function getChartData(Request $request)
    {
        $teacherId = $this->authTeacherId($request);
        $request->query->set('teacher_id', $teacherId);

        $admin = new AdminReportController();
        return $admin->getChartData($request);
    }

    public function getSessionsSummaryReport(Request $request)
    {
        $teacherId = $this->authTeacherId($request);
        $request->query->set('teacher_id', $teacherId); 
        $admin = new AdminReportController();
        return $admin->getSessionsSummaryReport($request);
    }

    public function getClassesSummaryReport(Request $request)
    {
        $teacherId = $this->authTeacherId($request);
        $request->query->set('teacher_id', $teacherId); 
        $admin = new AdminReportController();
        return $admin->getClassesSummaryReport($request);
    }
}