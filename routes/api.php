<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Teachers;
use App\Http\Controllers\TeachersController;
use App\Http\Controllers\ManageClassController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;



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
    Route::post('/create-permission', [RoleController::class, 'createPermission']);
    Route::post('/assign-role', [RoleController::class, 'assignRole']);
    Route::post('/assign-permission', [RoleController::class, 'assignPermissionToRole']);
    Route::get('/check-access', [RoleController::class, 'checkAccess']);

});

Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::post('/create-role', [RoleController::class, 'createRole']);
});