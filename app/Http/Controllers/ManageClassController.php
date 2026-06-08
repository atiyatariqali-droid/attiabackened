<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ManageClass;

class ManageClassController extends Controller
{
    // ─────────────────────────────
    // LIST ALL CLASSES
    // ─────────────────────────────
    function list(){
        return response()->json([
            "success" => true,
            "data" => ManageClass::all()
        ]);
    }

    // ─────────────────────────────
    // ADD CLASS
    // ─────────────────────────────
    public function addClass(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'class_name' => 'required',
            'students_count' => 'required|integer',
            'status' => 'required'
        ]);

        $manageClass = new ManageClass();
        $manageClass->name = $request->name;
        $manageClass->class_name = $request->class_name;
$manageClass->students_count = $request->students_count;
        $manageClass->status = $request->status;

        if($manageClass->save()){
            return response()->json([
                "success" => true,
                "message" => "Class added successfully"
            ]);
        }

        return response()->json([
            "success" => false,
            "message" => "Failed to add class"
        ]);
    }

    // ─────────────────────────────
    // EDIT CLASS (GET SINGLE)
    // ─────────────────────────────
    public function editClass($id)
    {
        $manageClass = ManageClass::find($id);

        if(!$manageClass){
            return response()->json([
                "success" => false,
                "message" => "Class not found"
            ]);
        }

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
        $manageClass = ManageClass::find($id);

        if(!$manageClass){
            return response()->json([
                "success" => false,
                "message" => "Class not found"
            ]);
        }

        $manageClass->name = $request->name;
        $manageClass->class_name = $request->class_name;
$manageClass->students_count = $request->students_count;
        $manageClass->status = $request->status;

        if($manageClass->save()){
            return response()->json([
                "success" => true,
                "message" => "Class updated successfully"
            ]);
        }

        return response()->json([
            "success" => false,
            "message" => "Class not updated"
        ]);
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
    function searchClass($name)
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