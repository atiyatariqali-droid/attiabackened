<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Students;
use App\Models\ManageClass;

class StudentsController extends Controller
{
    // ─────────────────────────────
    // LIST ALL STUDENTS (Filtered for Teacher, All for Admin)
    // ─────────────────────────────
    function list(Request $request){
        $user = $request->user();

        $query = Students::where('role', 'student')->where('status', 1);

        if ($user && $user->role === 'teacher') {
            $teacherClasses = ManageClass::where('teacher_id', $user->id)
                                          ->pluck('id')
                                          ->filter()
                                          ->toArray();

            $query->where(function($q) use ($teacherClasses) {
                if (!empty($teacherClasses)) {
                    $q->whereIn('class_id', $teacherClasses);
                } else {
                    $q->whereRaw('1 = 0');
                }
            });
        }

        return response()->json([
            "success" => true,
            "data" => $query->get()
        ]);
    }


    // ─────────────────────────────
    // HELPER: Next available roll number (global max + 1, no gap-fill)
    // ─────────────────────────────
    private function getNextRollNo()
    {
        $max = \DB::table('users')
            ->whereNotNull('roll_no')
            ->max(\DB::raw('CAST(roll_no AS UNSIGNED)'));

        return (string) (($max ?? 0) + 1);
    }

    // ─────────────────────────────
    // GET NEXT AVAILABLE ROLL NUMBER (for auto-fill on Add Student form)
    // ─────────────────────────────
    public function nextRollNo()
    {
        return response()->json([
            "success" => true,
            "next_roll_no" => $this->getNextRollNo()
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
            'class_id' => 'nullable|exists:manage_classes,id',
            'roll_no' => 'nullable|string|unique:users,roll_no',
        ], [
            'roll_no.unique' => 'This roll number already exists, please assign a unique number',
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

        $classId = $request->class_id;
        if (!$classId && $request->class) {
            $classId = ManageClass::where('name', $request->class)->value('id');
        }

        // Auto-assign roll_no agar frontend se nahi bheja gaya (max+1, global)
        $rollNo = $request->filled('roll_no') ? $request->roll_no : $this->getNextRollNo();

        $student = new Students();
        $student->username = $request->username;
        $student->email = $request->email;
        $student->password = bcrypt($request->password);
        $student->phone = $request->phone;
        $student->role = 'student';
        $student->status = $status;
        $student->class_id = $classId;
        $student->roll_no = $rollNo;
        try {
            if($student->save()){
                return response()->json([
                    "success" => true,
                    "message" => $status === 1 ? "Student added successfully" : "Student submitted for approval"
                ]);
            }
        } catch (\Illuminate\Database\QueryException $e) {
            // Race-condition fallback: DB unique constraint hit ho gaya
            if ((int) $e->getCode() === 23000) {
                return response()->json([
                    "success" => false,
                    "message" => "This roll number already exists, please assign a unique number"
                ], 422);
            }
            throw $e;
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

        $attendances = \DB::table('attendance as a')
            ->where('a.student_id', $id)
            ->leftJoin('manage_classes as c', 'c.id', '=', 'a.class_id')
            ->select('a.status', 'a.attendance_date', 'a.class_id', 'c.name as class_name')
            ->get();

        $studentData = $student->toArray();
        $studentData['attendances'] = $attendances;

        return response()->json([
            "success" => true,
            "data" => $studentData
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
            'class_id' => 'nullable|exists:manage_classes,id',
            'roll_no'  => 'nullable|string|unique:users,roll_no,' . $id,
        ], [
            'roll_no.unique' => 'This roll number already exists, please assign a unique number',
        ]);
    
        $data = [
            'username' => $request->username,
            'email'    => $request->email,
            'phone'    => $request->phone,
            'roll_no'  => $request->roll_no,
        ];

        $classId = $request->class_id;
        if (!$classId && $request->filled('class')) {
            $classId = ManageClass::where('name', $request->class)->value('id');
        }
        if ($classId) {
            $data['class_id'] = $classId;
        }
    
        if($request->password){
            $data['password'] = bcrypt($request->password);
        }
    
        try {
            $student->update($data);
        } catch (\Illuminate\Database\QueryException $e) {
            if ((int) $e->getCode() === 23000) {
                return response()->json([
                    "success" => false,
                    "message" => "This roll number already exists, please assign a unique number"
                ], 422);
            }
            throw $e;
        }
    
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
            $teacherClasses = ManageClass::where('teacher_id', $teacher_id)
                                          ->pluck('id')
                                          ->filter()
                                          ->toArray();

            $query->where(function($q) use ($teacherClasses) {
                if (!empty($teacherClasses)) {
                    $q->whereIn('class_id', $teacherClasses);
                } else {
                    $q->whereRaw('1 = 0');
                }
            });
        }

        $students = $query->get()->map(function($s) {
            return [
                'id' => $s->id,
                'name' => $s->username,
                'class' => $s->class_name ?? 'N/A',
                'roll_no' => $s->roll_no ?? 'N/A',
                'student_status' => $s->status == 1 ? 'Active' : 'Pending',
            ];
        });

        return response()->json([
            'students' => $students
        ]);
    }
}