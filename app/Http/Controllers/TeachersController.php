<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Teachers;

class TeachersController extends Controller
{
    // ─────────────────────────────
    // LIST ALL TEACHERS
    // ─────────────────────────────
    function list(){
        return response()->json([
            "success" => true,
            "data" => Teachers::all()
        ]);
    }

    // ─────────────────────────────
    // ADD TEACHER
    // ─────────────────────────────
    function addTeacher(Request $request){

        $request->validate([
            'username' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:6',
            'phone' => 'nullable'
        ]);

        $teacher = new Teachers();
        $teacher->username = $request->username;
        $teacher->email = $request->email;
        $teacher->password = bcrypt($request->password);
        $teacher->phone = $request->phone;

        if($teacher->save()){
            return response()->json([
                "success" => true,
                "message" => "Teacher added successfully"
            ]);
        }

        return response()->json([
            "success" => false,
            "message" => "Failed to add teacher"
        ]);
    }

    // ─────────────────────────────
    // GET SINGLE TEACHER (EDIT)
    // ─────────────────────────────
    function editTeacher($id){

        $teacher = Teachers::find($id);

        if(!$teacher){
            return response()->json([
                "success" => false,
                "message" => "Teacher not found"
            ]);
        }

        return response()->json([
            "success" => true,
            "data" => $teacher
        ]);
    }

    // ─────────────────────────────
    // UPDATE TEACHER
    // ─────────────────────────────
    function updateTeacher(Request $request, $id){

        $teacher = Teachers::find($id);

        if(!$teacher){
            return response()->json([
                "success" => false,
                "message" => "Teacher not found"
            ], 404);
        }

        $request->validate([
            'username' => 'required',
            'email' => 'required|email',
            'password' => 'nullable|min:6',
            'phone' => 'nullable'
        ]);

        $data = [
            'username' => $request->username,
            'email' => $request->email,
            'phone' => $request->phone
        ];

        if($request->password){
            $data['password'] = bcrypt($request->password);
        }

        $teacher->update($data);

        return response()->json([
            "success" => true,
            "message" => "Teacher updated successfully"
        ]);
    }

    // ─────────────────────────────
    // DELETE TEACHER
    // ─────────────────────────────
    function deleteTeacher($id){

        $teacher = Teachers::find($id);

        if(!$teacher){
            return response()->json([
                "success" => false,
                "message" => "Teacher not found"
            ]);
        }

        $teacher->delete();

        return response()->json([
            "success" => true,
            "message" => "Teacher deleted successfully"
        ]);
    }

    // ─────────────────────────────
    // SEARCH TEACHER
    // ─────────────────────────────
    function searchTeacher($username){

        $teacher = Teachers::where("username", "like", "%$username%")->get();

        if($teacher->isEmpty()){
            return response()->json([
                "success" => false,
                "message" => "Teacher not found"
            ]);
        }

        return response()->json([
            "success" => true,
            "data" => $teacher
        ]);
    }
}