<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TeachersController;
use App\Http\Controllers\ManageClassController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\SystemSettingController;
use App\Http\Controllers\StudentsController;
use App\Http\Controllers\PendingStudentController;



/*
|--------------------------------------------------------------------------
| AUTH USER
|--------------------------------------------------------------------------
*/
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| TEST ROUTE
|--------------------------------------------------------------------------
*/
Route::get("/test", function () {
    return response()->json([
        "success" => true,
        "message" => "API Working",
        "data" => [
            "name" => "Atiya",
            "age" => 21
        ]
    ]);
});

/*
|--------------------------------------------------------------------------
| LOGIN
|--------------------------------------------------------------------------
*/
Route::post("/login", [UserController::class, "login"])->name('login');

/*
|--------------------------------------------------------------------------
| TEACHERS ROUTES (CLEANED)
|--------------------------------------------------------------------------
*/
Route::get("/teachers", [TeachersController::class, "list"]);
Route::post("/teachers", [TeachersController::class, "addTeacher"]);
Route::get("/teachers/{id}", [TeachersController::class, "editTeacher"]);
Route::put("/teachers/{id}", [TeachersController::class, "updateTeacher"]);
Route::delete("/teachers/{id}", [TeachersController::class, "deleteTeacher"]);
Route::get("/search-teachers/{name}", [TeachersController::class, "searchTeacher"]);

/*
|--------------------------------------------------------------------------
| STUDENTS ROUTES (CRUD)
|--------------------------------------------------------------------------
*/
Route::get("/students", [StudentsController::class, "list"]);
Route::post("/students", [StudentsController::class, "addStudent"]);
Route::get("/students/{id}", [StudentsController::class, "editStudent"]);
Route::put("/students/{id}", [StudentsController::class, "updateStudent"]);
Route::delete("/students/{id}", [StudentsController::class, "deleteStudent"]);
Route::get("/search-students/{name}", [StudentsController::class, "searchStudent"]);

/*
|--------------------------------------------------------------------------
| PENDING STUDENTS ROUTES
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| ROLE CRUD - ADMIN ONLY
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get("/roles", [RoleController::class, "listRoles"]);
    Route::post("/roles", [RoleController::class, "createRole"]);
    Route::get("/roles/{id}", [RoleController::class, "editRole"]);
    Route::put("/roles/{id}", [RoleController::class, "updateRole"]);
    Route::delete("/roles/{id}", [RoleController::class, "deleteRole"]);
});

Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {

    Route::get("/pending-students", [PendingStudentController::class, "list"]);
    Route::post("/pending-students", [PendingStudentController::class, "store"]);
    Route::post("/pending-students/approve/{id}", [PendingStudentController::class, "approve"]);
    Route::post("/pending-students/reject/{id}", [PendingStudentController::class, "reject"]);
    Route::post("/pending-students/approve-all", [PendingStudentController::class, "approveAll"]);
});
/*
|--------------------------------------------------------------------------
| MANAGE CLASSES ROUTES (FIXED FOR FLUTTER)
|--------------------------------------------------------------------------
*/
Route::get("/classes", [ManageClassController::class, "list"]);
Route::post("/classes", [ManageClassController::class, "addClass"]);
Route::get("/classes/{id}", [ManageClassController::class, "editClass"]);
Route::put("/classes/{id}", [ManageClassController::class, "updateClass"]);
Route::delete("/classes/{id}", [ManageClassController::class, "deleteClass"]);
Route::get("/search-classes/{name}", [ManageClassController::class, "searchClass"]);

/*
|--------------------------------------------------------------------------
| ATTENDANCE
|--------------------------------------------------------------------------
*/
Route::post('/mark-attendance', [AttendanceController::class, 'markAttendance']);
Route::post('/create-session', [SessionController::class, 'createSession']);

/*
|--------------------------------------------------------------------------
| SESSION MANAGEMENT
|--------------------------------------------------------------------------
*/
Route::post('/login-session', [SessionController::class, 'login']);
Route::post('/logout-session/{id}', [SessionController::class, 'logout']);
Route::get('/active-sessions', [SessionController::class, 'activeSessions']);

/*
|--------------------------------------------------------------------------
| ROLE & PERMISSION (SANCTUM)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/create-permission', [RoleController::class, 'createPermission']);
    Route::post('/assign-role', [RoleController::class, 'assignRole']);
    Route::post('/assign-permission', [RoleController::class, 'assignPermissionToRole']);
    Route::get('/check-access', [RoleController::class, 'checkAccess']);
    Route::apiResource('settings', SystemSettingController::class);


});
