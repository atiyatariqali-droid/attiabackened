<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Teachers;
use App\Http\Controllers\TeachersController;
use App\Http\Controllers\ManageClassController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\UserSessionController;
use App\Http\Controllers\SystemConfiController;



Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum'); 


Route::get("/test", function(){
    return ["name" => "Atiya", "age" =>21];
});

Route::get("/login", [UserController::class, "login"])->name('login');

                  //Teacher routes
Route::get("/teachers", [TeachersController::class, "list"]);
Route::post("/add-teacher", [TeachersController::class, "addTeacher"]);
Route::post("/edit-teacher/{id}", [TeachersController::class, "editTeacher"]);
Route::put("/update-teacher/{id}", [TeachersController::class, "updateTeacher"]);
Route::delete("/delete-teacher/{id}", [TeachersController::class, "deleteTeacher"]);
Route::get("/search-teacher/{username}", [TeachersController::class, "searchTeacher"]);


                    //Manage classes routes
Route::get("/manage_classes", [ManageClassController::class, "list"]);
Route::post("/add-manage_classes", [ManageClassController::class, "addClass"]);
Route::post("/edit-manage_classes/{id}", [ManageClassController::class, "editClass"]);
Route::put("/update-manage_classes/{id}", [ManageClassController::class, "updateClass"]);
Route::delete("/delete-manage_classes/{id}", [ManageClassController::class, "deleteClass"]);
Route::get("/search-manage_classes/{username}", [ManageClassController::class, "searchClass"]);

                    //Role based route
// Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
//     Route::get('/admin-only', function () {
//         return "Only Admin";
//     });
// });
//                    //Permission based route
// Route::middleware(['auth:sanctum', 'permission:edit posts'])->group(function () {
//     Route::get('/edit', function () {
//         return "Can edit";
//     });
// });




Route::middleware(['auth:sanctum'])->group(function () {

    Route::post('/create-role', [RoleController::class, 'createRole']);
    //Route::get('/session', [UserController::class, 'getSession']);
    //Route::post('/session', [SessionController::class, 'store']);
    //edit, delete, update role
    Route::post('/create-permission', [RoleController::class, 'createPermission']);
    Route::post('/assign-role', [RoleController::class, 'assignRole']);
    Route::post('/assign-permission', [RoleController::class, 'assignPermissionToRole']);
    Route::get('/check-access', [RoleController::class, 'checkAccess']);

});

Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::post('/create-role', [RoleController::class, 'createRole']);
});

                     //Mark attendance
Route::post('/mark-attendance',
    [AttendanceController::class, 'markAttendance']
);

          //session create
Route::post('/login-session',[UserSessionController::class, 'login']);
Route::post('/logout-session/{id}',[UserSessionController::class, 'logout']);
Route::get('/active-sessions',[UserSessionController::class, 'activeSessions']);    

//System configuration routes
Route::get('/system-confi', [SystemConfiController::class, 'index']);
Route::post('/system-confi', [SystemConfiController::class, 'store']);
Route::get('/system-confi/{id}', [SystemConfiController::class, 'show']);
Route::put('/system-confi/{id}', [SystemConfiController::class, 'update']);
Route::delete('/system-confi/{id}', [SystemConfiController::class, 'destroy']);
Route::get('/system-confi/edit/{id}', [SystemConfiController::class, 'edit']);

//create seesion  only insert check fields from chatgpt
//table system_confi (fields(primary key, lonitude latitude, school name, school address, school contact, created at, updated at))
//logic
//we need to check the current longituted and latitude of teacher before creating session if the teacher is in the range of school then create session otherwise return error message
//topic
//study laravel google map /API 