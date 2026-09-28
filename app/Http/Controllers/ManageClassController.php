<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\ManageClass;
use App\Models\ClassGroup;
use App\Models\Students;
use App\Models\Teachers;

class ManageClassController extends Controller
{
    //List all classes with their student counts
    public function list(Request $request){
        $user = $request->user();

        $query = ManageClass::with(['teacher', 'classGroup']);

        if ($user && $user->role === 'teacher') {
            $query->where('teacher_id', $user->id);
        }

        $classes = $query->get();

        $classes = $classes->map(function ($class) {
            $class->teacher_name = $class->teacher->username ?? '';
            $class->class_name = $class->name;

            $class->students_count = $class->class_group_id
                ? Students::where('class_id', $class->class_group_id)
                    ->where('role', 'student')
                    ->where('status', 1)
                    ->count()
                : 0;

            return $class;
        });

        return response()->json([
            "success" => true,
            "data" => $classes
        ]);
    }

    //Add Class
    public function addClass(Request $request)
    {
        $request->validate([
            'class_name' => [
                'required',
                Rule::unique('manage_classes', 'name')->where(function ($query) use ($request) {
                    return $query->where('subject', $request->subject);
                }),
            ],
            'teacher_id' => 'nullable|integer|exists:users,id',
            'subject' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $classGroup = ClassGroup::firstOrCreate(
            ['name' => $request->class_name],
            ['status' => $request->status]
        );

        $manageClass = new ManageClass();

        $manageClass->name = $request->class_name;
        $manageClass->class_group_id = $classGroup->id;
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

    //Edit class
    public function editClass($id)
    {
        $manageClass = ManageClass::with(['teacher', 'classGroup'])->find($id);

        if(!$manageClass){
            return response()->json([
                "success" => false,
                "message" => "Class not found"
            ]);
        }

        $manageClass->students_count = $manageClass->class_group_id
            ? Students::where('class_id', $manageClass->class_group_id)
                ->where('role', 'student')
                ->where('status', 1)
                ->count()
            : 0;
        $manageClass->teacher_name = $manageClass->teacher->username ?? '';
        $manageClass->class_name = $manageClass->name; // legacy

        return response()->json([
            "success" => true,
            "data" => $manageClass
        ]);
    }

    //Update class
    public function updateClass(Request $request, $id)
    {
        $request->validate([
            'class_name' => [
                'required',
                Rule::unique('manage_classes', 'name')->ignore($id)->where(function ($query) use ($request) {
                    return $query->where('subject', $request->subject);
                }),
            ],
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

        $classGroup = ClassGroup::firstOrCreate(
            ['name' => $request->class_name],
            ['status' => $request->status]
        );

        $manageClass->name = $request->class_name;
        $manageClass->class_group_id = $classGroup->id;
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

    //Delete class
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

    //Search class by name
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