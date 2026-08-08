<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\TeachersController;
use App\Http\Controllers\ManageClassController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\SystemSettingController; 
use App\Http\Controllers\StudentsController;
use App\Http\Controllers\PendingStudentController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\AdminProfileController;
use App\Http\Controllers\TeacherProfileController; 
use App\Http\Controllers\ConfirmationController; 
use App\Http\Controllers\AdminReportController;
use App\Http\Controllers\AdminReportExportController;

// PUBLIC ROUTES
Route::post("/login", [UserController::class, "login"])->name('login');
Route::post("/register-teacher", [TeachersController::class, "registerTeacher"]);

Route::get("/test", function () {
    return response()->json([
        "success" => true,
        "message" => "API Working",
        "data"    => ["name" => "Atiya", "age" => 21]
    ]);
});

Route::middleware(['auth:sanctum'])->group(function () {

    // Auth user
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Teachers
    Route::get("/teachers",               [TeachersController::class, "list"]);
    Route::post("/teachers",              [TeachersController::class, "addTeacher"]);
    Route::get("/teachers/{id}",          [TeachersController::class, "editTeacher"]);
    Route::put("/teachers/{id}",          [TeachersController::class, "updateTeacher"]);
    Route::delete("/teachers/{id}",       [TeachersController::class, "deleteTeacher"]);
    Route::get("/search-teachers/{name}", [TeachersController::class, "searchTeacher"]);
    Route::post("/teachers/approve/{id}", [TeachersController::class, "approve"]);

    // Classes CRUD
    Route::get("/classes",               [ManageClassController::class, "list"]);
    Route::post("/classes",              [ManageClassController::class, "addClass"]);
    Route::get("/classes/{id}",          [ManageClassController::class, "editClass"]);
    Route::put("/classes/{id}",          [ManageClassController::class, "updateClass"]);
    Route::delete("/classes/{id}",       [ManageClassController::class, "deleteClass"]);
    Route::get("/search-classes/{name}", [ManageClassController::class, "searchClass"]);

    // Role CRUD - admin only
    Route::middleware(['role:admin'])->group(function () {
        Route::get("/roles",         [RoleController::class, "listRoles"]);
        Route::post("/roles",        [RoleController::class, "createRole"]);
        Route::get("/roles/{id}",    [RoleController::class, "editRole"]);
        Route::put("/roles/{id}",    [RoleController::class, "updateRole"]);
        Route::delete("/roles/{id}", [RoleController::class, "deleteRole"]);
        Route::post('/create-role',  [RoleController::class, 'createRole']);
    });

    // Students CRUD
    Route::get("/students",                [StudentsController::class, "list"]);
    Route::post("/students",               [StudentsController::class, "addStudent"]);
    Route::get("/students/{id}",           [StudentsController::class, "editStudent"]);
    Route::put("/students/{id}",           [StudentsController::class, "updateStudent"]);
    Route::delete("/students/{id}",        [StudentsController::class, "deleteStudent"]);
    Route::get("/search-students/{name}",  [StudentsController::class, "searchStudent"]);
    Route::get("/teacher/{teacher_id}/approved-students", [StudentsController::class, "teacherStudents"]);

    // Pending students
    Route::get("/pending-students",               [PendingStudentController::class, "list"]);
    Route::post("/pending-students",              [PendingStudentController::class, "store"]);
    Route::post("/pending-students/approve/{id}", [PendingStudentController::class, "approve"]);
    Route::post("/pending-students/reject/{id}",  [PendingStudentController::class, "reject"]);
    Route::post("/pending-students/approve-all",  [PendingStudentController::class, "approveAll"]);

    // Roles & permissions
    Route::post('/create-permission',  [RoleController::class, 'createPermission']);
    Route::post('/assign-role',        [RoleController::class, 'assignRole']);
    Route::post('/assign-permission',  [RoleController::class, 'assignPermissionToRole']);
    Route::get('/check-access',        [RoleController::class, 'checkAccess']);
    Route::apiResource('settings',      SystemSettingController::class);

    // Attendance
    Route::post('/mark-attendance',     [AttendanceController::class, 'markAttendance']);
    Route::post('/submit-attendance',   [AttendanceController::class, 'submitAttendance']);
    Route::post('/session-students',    [AttendanceController::class, 'saveSessionStudents']);
    Route::get('/attendance/report',    [SessionController::class, 'attendanceReport']);

    // Notifications
    Route::get('/notifications/{student_id}',             [AttendanceController::class, 'getNotifications']);
    Route::post('/notifications/{student_id}/mark-read',  [AttendanceController::class, 'markNotificationsRead']);

    // Sessions
    Route::post('/create-session',              [SessionController::class, 'createSession']);
    Route::get('/sessions',                     [SessionController::class, 'index']);
    Route::get('/sessions/report',              [SessionController::class, 'sessionReport']);
    Route::get('/sessions/{id}/students',       [SessionController::class, 'getSessionStudents']);
    Route::put('/sessions/{id}/status',         [SessionController::class, 'updateSessionStatus']);
    Route::post('/sessions/{id}/toggle-status', [SessionController::class, 'toggleStatus']);
    Route::delete('/sessions/{id}',             [SessionController::class, 'deleteSession']);
    Route::get('/teacher-sessions/{teacher_id}',[SessionController::class, 'getTeacherSessions']);
    Route::post('/end-session/{id}',            [SessionController::class, 'endSession']);

    // Dashboard / reports
    Route::get('/report/dashboard', [SessionController::class, 'reportDashboard']);

    // Teacher confirmation flow
    Route::post('/confirmation/request',  [ConfirmationController::class, 'requestConfirmation']);
    Route::get('/confirmation/results',   [ConfirmationController::class, 'getResults']);
    Route::get('/confirmation/pending',   [ConfirmationController::class, 'getPendingConfirmation']);
    Route::post('/confirmation/respond',  [ConfirmationController::class, 'submitResponse']);
    Route::get('/confirmation/directory', [ConfirmationController::class, 'getResponseDirectory']);

    // Admin profile
    Route::get ('admin/profile',                 [AdminProfileController::class, 'show']);
    Route::put ('admin/profile',                 [AdminProfileController::class, 'update']);
    Route::post('admin/profile/change-password', [AdminProfileController::class, 'changePassword']);
    Route::post('admin/profile/change-email',    [AdminProfileController::class, 'changeEmail']);
    Route::post('admin/logout',                  [AdminProfileController::class, 'logout']);
    Route::post('admin/logout-all',              [AdminProfileController::class, 'logoutAll']);

    // Student profile
    Route::post('student/profile/change-password', [AdminProfileController::class, 'studentChangePassword']);
    Route::post('student/logout',                   [AdminProfileController::class, 'logout']);
    // Student dashboard summary
    Route::get('/student/{student_id}/dashboard-status', [AttendanceController::class, 'getStudentDashboardStatus']);

    // Teacher profile
    Route::get ('teacher/profile',                 [TeacherProfileController::class, 'show']);
    Route::put ('teacher/profile',                 [TeacherProfileController::class, 'update']);
    Route::post('teacher/profile/change-password', [TeacherProfileController::class, 'changePassword']);
    Route::post('teacher/profile/change-email',    [TeacherProfileController::class, 'changeEmail']);
    Route::post('teacher/logout',                  [TeacherProfileController::class, 'logout']);
    Route::post('teacher/logout-all',              [TeacherProfileController::class, 'logoutAll']);

   //Admin reports
   Route::prefix('admin/reports')->group(function () {
    Route::get('stats',    [AdminReportController::class, 'getStats']);
    Route::get('chart',    [AdminReportController::class, 'getChartData']);
    Route::get('students', [AdminReportController::class, 'getStudentsList']);
    Route::get('classes',  [AdminReportController::class, 'getClasses']);
    Route::get('teachers', [AdminReportController::class, 'getTeachers']);
    Route::get('student/{id}', [AdminReportController::class, 'getStudentDetailReport']);
    Route::put('attendance/{id}', [AdminReportController::class, 'updateAttendance']);
    Route::get('teachers-summary', [AdminReportController::class, 'getTeachersSummaryReport']);
    Route::get('classes-summary', [AdminReportController::class, 'getClassesSummaryReport']);
    Route::get('sessions-summary', [AdminReportController::class, 'getSessionsSummaryReport']);
    // Export routes
    Route::get('export/pdf', [AdminReportExportController::class, 'exportPdf']);
    Route::get('export/excel', [AdminReportExportController::class, 'exportExcel']);
    Route::get('student/{id}/export/pdf', [AdminReportExportController::class, 'exportStudentPdf']);
    Route::get('student/{id}/export/excel', [AdminReportExportController::class, 'exportStudentExcel']);
});

    // FIX 3: Late students route — ab yahan add kiya
    Route::get('admin/late-students', function () {
        $late = DB::table('attendance')
            ->join('attendance_sessions', 'attendance.session_id', '=', 'attendance_sessions.id')
            ->join('users as students', 'attendance.student_id', '=', 'students.id')
            ->leftJoin('users as teachers', 'teachers.id', '=', 'attendance_sessions.teacher_id')
            ->leftJoin('classes', 'classes.teacher_id', '=', 'attendance_sessions.teacher_id')
            ->where('attendance.status', 'late')
            ->orderByDesc('attendance_sessions.created_at')
            ->select(
                'students.id as student_id',
                'students.name as student_name',
                DB::raw('COALESCE(students.roll_number, "-") as roll_no'),
                DB::raw('COALESCE(classes.class_name, "-") as class_name'),
                DB::raw('COALESCE(teachers.name, "Not Assigned") as teacher_name'),
                DB::raw('DATE(attendance_sessions.created_at) as session_date'),
                DB::raw('TIME_FORMAT(attendance.created_at, "%h:%i %p") as marked_time')
            )
            ->get();

        return response()->json(['success' => true, 'data' => $late]);
    });

});