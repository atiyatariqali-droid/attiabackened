<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Teachers;
use App\Http\Controllers\TeachersController;
use App\Http\Controllers\ManageClassController;
use App\Http\Controllers\UserController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::get("/test", function(){
    return ["name" => "Atiya", "age" =>21];
});

Route::post("/login", [UserController::class, "login"]);

Route::get("/teachers", [TeachersController::class, "list"]);
Route::post("/add-teacher", [TeachersController::class, "addTeacher"]);
Route::post("/edit-teacher/{id}", [TeachersController::class, "editTeacher"]);
Route::put("/update-teacher/{id}", [TeachersController::class, "updateTeacher"]);
Route::delete("/delete-teacher/{id}", [TeachersController::class, "deleteTeacher"]);
Route::get("/search-teacher/{username}", [TeachersController::class, "searchTeacher"]);


Route::get("/manage_classes", [ManageClassController::class, "list"]);
Route::post("/add-manage_classes", [ManageClassController::class, "addClass"]);
Route::post("/edit-manage_classes/{id}", [ManageClassController::class, "editClass"]);
Route::put("/update-manage_classes/{id}", [ManageClassController::class, "updateClass"]);
Route::delete("/delete-manage_classes/{id}", [ManageClassController::class, "deleteClass"]);
Route::get("/search-manage_classes/{username}", [ManageClassController::class, "searchClass"]);