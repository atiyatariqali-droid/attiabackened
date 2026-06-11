<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Students;

class StudentsController extends Controller
{
    // ─────────────────────────────
    // LIST ALL STUDENTS
    // ─────────────────────────────
    function list(){
        return response()->json([
            "success" => true,
            "data" => Students::where('role', 'student')->get()
        ]);
    }

    // ─────────────────────────────
    // ADD STUDENT
    // ─────────────────────────────
    function addStudent(Request $request){
        $request->validate([
            'username' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'phone' => 'nullable'
        ]);

        $student = new Students();
        $student->username = $request->username;
        $student->email = $request->email;
        $student->password = bcrypt($request->password);
        $student->phone = $request->phone;
        $student->role = 'student';

        if($student->save()){
            return response()->json([
                "success" => true,
                "message" => "Student added successfully"
            ]);
        }

        return response()->json([
            "success" => false,
            "message" => "Failed to add student"
        ]);
    }

    // ─────────────────────────────
    // GET SINGLE STUDENT (EDIT)
    // ─────────────────────────────
    function editStudent($id){
        $student = Students::where('id', $id)->where('role', 'student')->first();

        if(!$student){
            return response()->json([
                "success" => false,
                "message" => "Student not found"
            ]);
        }

        return response()->json([
            "success" => true,
            "data" => $student
        ]);
    }

    // ─────────────────────────────
    // UPDATE STUDENT
    // ─────────────────────────────
    function updateStudent(Request $request, $id){
        $student = Students::where('id', $id)->where('role', 'student')->first();

        if(!$student){
            return response()->json([
                "success" => false,
                "message" => "Student not found"
            ], 404);
        }

        $request->validate([
            'username' => 'required',
            'email' => 'required|email|unique:users,email,'.$id,
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

        $student->update($data);

        return response()->json([
            "success" => true,
            "message" => "Student updated successfully"
        ]);
    }

    // ─────────────────────────────
    // DELETE STUDENT
    // ─────────────────────────────
    function deleteStudent($id){
        $student = Students::where('id', $id)->where('role', 'student')->first();

        if(!$student){
            return response()->json([
                "success" => false,
                "message" => "Student not found"
            ]);
        }

        $student->delete();

        return response()->json([
            "success" => true,
            "message" => "Student deleted successfully"
        ]);
    }

    // ─────────────────────────────
    // SEARCH STUDENT
    // ─────────────────────────────
    function searchStudent($username){
        $students = Students::where('role', 'student')
            ->where("username", "like", "%$username%")
            ->get();

        if($students->isEmpty()){
            return response()->json([
                "success" => false,
                "message" => "Student not found"
            ]);
        }

        return response()->json([
            "success" => true,
            "data" => $students
        ]);
    }
}
