<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PendingStudent;
use App\Models\Student;
use App\Models\User;

class StudentController extends Controller {
    
    // 1. Admin ke liye pending list
    public function pending() {
        $pending = PendingStudent::where('approval_status','pending')
            ->with('teacher:id,name')
            ->latest()
            ->get()
            ->map(function($s){
                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'class' => $s->class,
                    'roll_no' => $s->roll_no,
                    'student_status' => $s->student_status,
                    'teacher_name' => $s->teacher->name ?? 'Teacher',
                    'created_at' => $s->created_at->diffForHumans()
                ];
            });
        return response()->json(['pending_students' => $pending]);
    }

    // 2. Teacher se data save
    public function storePending(Request $request) {
        $data = $request->validate([
            'name' => 'required|string',
            'class' => 'required|string',
            'roll_no' => 'required|string',
            'student_status' => 'required|string',
            'teacher_id' => 'required'
        ]);
        PendingStudent::create($data);
        return response()->json(['success' => true, 'message' => 'Sent for approval']);
    }

    // 3. Approve karega admin
    public function approve($id) {
        $pending = PendingStudent::find($id);
        if(!$pending) return response()->json(['error'=>'Not found'],404);
        
        Student::create([
            'name' => $pending->name,
            'class' => $pending->class,
            'roll_no' => $pending->roll_no,
            'student_status' => $pending->student_status,
            'teacher_id' => $pending->teacher_id,
            'status' => 'approved'
        ]);
        $pending->delete();
        return response()->json(['success' => true]);
    }

    // 4. Reject karega admin
    public function reject($id) {
        PendingStudent::find($id)?->delete();
        return response()->json(['success' => true]);
    }

    // 5. Teacher ki approved list
    public function approvedByTeacher($teacherId) {
        $students = Student::where('teacher_id',$teacherId)
            ->where('status','approved')
            ->get();
        return response()->json(['students' => $students]);
    }

    // 6. Bulk approve
    public function approveAll(Request $request) {
        $ids = $request->ids ?? [];
        foreach($ids as $id) {
            $this->approve($id);
        }
        return response()->json(['success' => true]);
    }
}