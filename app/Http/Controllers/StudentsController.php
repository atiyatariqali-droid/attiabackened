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
            "data" => Students::where('role', 'student')->where('status', 1)->get()
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
            'phone' => 'nullable',
            'class' => 'nullable|string',
            'roll_no' => 'nullable|string',
        ]);

        $user = $request->user();

$userRole = $user ? $user->role : null;

if (!$userRole) {
    return response()->json([
        "success" => false,
        "message" => "Unauthorized"
    ], 401);
}
        $status = ($userRole === 'admin') ? 1 : 0;

        $student = new Students();
        $student->username = $request->username;
        $student->email = $request->email;
        $student->password = bcrypt($request->password);
        $student->phone = $request->phone;
        $student->role = 'student';
        $student->status = $status;
        $student->class = $request->class;
        $student->roll_no = $request->roll_no;
        $student->teacher_id = ($userRole === 'teacher') ? $request->user()->id : null;

        if($student->save()){
            return response()->json([
                "success" => true,
                "message" => $status === 1 ? "Student added successfully" : "Student submitted for approval"
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
            'email'    => 'required|email|unique:users,email,' . $id,  // ← sirf yeh badla
            'password' => 'nullable|min:6',
            'phone'    => 'nullable',
            'class'    => 'nullable|string',
            'roll_no'  => 'nullable|string',
        ]);
    
        $data = [
            'username' => $request->username,
            'email'    => $request->email,
            'phone'    => $request->phone,
            'class'    => $request->class,
            'roll_no'  => $request->roll_no,
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

    // ─────────────────────────────
    // LIST TEACHER'S STUDENTS
    // ─────────────────────────────
    public function teacherStudents($teacher_id)
    {
        $query = Students::where('role', 'student')->where('status', 1);

        if ($teacher_id && $teacher_id != '0') {
            $query->where('teacher_id', $teacher_id);
        }

        $students = $query->get()->map(function($s) {
            return [
                'id' => $s->id,
                'name' => $s->username,
                'class' => $s->class ?? 'N/A',
                'roll_no' => $s->roll_no ?? 'N/A',
                'student_status' => $s->status == 1 ? 'Active' : 'Pending',
            ];
        });

        return response()->json([
            'students' => $students
        ]);
    }
}
