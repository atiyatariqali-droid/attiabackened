<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ManageClass;
use App\Models\Students;
use App\Models\Teachers;

class ManageClassController extends Controller
{
    // ─────────────────────────────
    // LIST ALL CLASSES
    // ─────────────────────────────

    public function list(){
        $classes = ManageClass::with('teacher')->withCount('students')->get();

        $classes = $classes->map(function ($class) {
            $class->students_count = $class->students_count ?? 0;
            $class->teacher_name = $class->teacher->username ?? '';
            $class->class_name = $class->name;
            return $class;
        });

        return response()->json([
            "success" => true,
            "data" => $classes
        ]);
    }

    // ─────────────────────────────
    // ADD CLASS
    // ─────────────────────────────

    public function addClass(Request $request)
    {
        $request->validate([
            'class_name' => 'required|unique:manage_classes,name',
            'teacher_id' => 'nullable|integer|exists:users,id',
            'subject' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $manageClass = new ManageClass();

        $manageClass->name = $request->class_name;
        $manageClass->teacher_id = $request->teacher_id;
        $manageClass->subject = $request->subject;
        $manageClass->students_count = $request->students_count ?? 0;
        $manageClass->status = $request->status;

        if ($manageClass->save()) {
            return response()->json([
                "success" => true,
                "message" => "Class added successfully",
                "data" => $manageClass
            ]);
        }

        return response()->json([
            "success" => false,
            "message" => "Failed to add class"
        ], 500);
    }

    // ─────────────────────────────
    // EDIT CLASS (GET SINGLE)
    // ─────────────────────────────
    public function editClass($id)
    {
        $manageClass = ManageClass::with('teacher')->find($id);

        if(!$manageClass){
            return response()->json([
                "success" => false,
                "message" => "Class not found"
            ]);
        }

        $manageClass->students_count = $manageClass->students()->count();
        $manageClass->teacher_name = $manageClass->teacher->username ?? '';
        $manageClass->class_name = $manageClass->name; // legacy

        return response()->json([
            "success" => true,
            "data" => $manageClass
        ]);
    }

    // ─────────────────────────────
    // UPDATE CLASS
    // ─────────────────────────────
    public function updateClass(Request $request, $id)
    {
        $request->validate([
            'class_name' => 'required|unique:manage_classes,name,' . $id,
            'teacher_id' => 'nullable|integer|exists:users,id',
            'subject' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $manageClass = ManageClass::find($id);

        if (!$manageClass) {
            return response()->json([
                "success" => false,
                "message" => "Class not found"
            ], 404);
        }

        $manageClass->name = $request->class_name;
        $manageClass->teacher_id = $request->teacher_id;
        $manageClass->subject = $request->subject;

        if ($request->has('students_count')) {
            $manageClass->students_count = $request->students_count;
        }

        $manageClass->status = $request->status;

        if ($manageClass->save()) {
            return response()->json([
                "success" => true,
                "message" => "Class updated successfully",
                "data" => $manageClass
            ]);
        }

        return response()->json([
            "success" => false,
            "message" => "Class not updated"
        ], 500);
    }

    // ─────────────────────────────
    // DELETE CLASS
    // ─────────────────────────────
    public function deleteClass($id)
    {
        $manageClass = ManageClass::find($id);

        if(!$manageClass){
            return response()->json([
                "success" => false,
                "message" => "Class not found"
            ]);
        }

        $manageClass->delete();

        return response()->json([
            "success" => true,
            "message" => "Class deleted successfully"
        ]);
    }

    // ─────────────────────────────
    // SEARCH CLASS
    // ─────────────────────────────
    public function searchClass($name)
    {
        $class = ManageClass::where("name", "like", "%$name%")->get();

        if($class->isEmpty()){
            return response()->json([
                "success" => false,
                "message" => "Class record not found"
            ]);
        }

        return response()->json([
            "success" => true,
            "data" => $class
        ]);
    }
}