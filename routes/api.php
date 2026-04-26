<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Teachers;
use App\Http\Controllers\TeachersController;
use App\Http\Controllers\ManageClassController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;



Route::middleware(['auth:sanctum', 'role:admin'])->get('/admin-only', function () {
    return response()->json(['msg' => 'You are admin']);
});


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum', 'role:admin'); // Example of role-based access control


Route::get("/test", function(){
    return ["name" => "Atiya", "age" =>21];
});

Route::post("/login", [UserController::class, "login"])->name('login');

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
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/admin-only', function () {
        return "Only Admin";
    });
});
                   //Permission based route
Route::middleware(['auth:sanctum', 'permission:edit posts'])->group(function () {
    Route::get('/edit', function () {
        return "Can edit";
    });
});