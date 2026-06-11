<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PendingStudent;
use App\Models\User;

class PendingStudentController extends Controller
{
    // List all pending students
    public function list()
    {
        $pending = PendingStudent::all()->map(function ($s) {
            $teacher = User::find($s->teacher_id);
            return [
                'id' => $s->id,
                'name' => $s->name,
                'class' => $s->class,
                'roll_no' => $s->roll_no,
                'student_status' => $s->student_status,
                'teacher_id' => $s->teacher_id,
                'teacher_name' => $teacher ? $teacher->username : 'Teacher',
                'approval_status' => $s->approval_status,
                'created_at' => $s->created_at ? $s->created_at->diffForHumans() : 'Just Now',
            ];
        });

        return response()->json([
            'success' => true,
            'pending_students' => $pending
        ]);
    }

    // Add a student in pending state
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'class' => 'required|string',
            'roll_no' => 'required|string',
            'student_status' => 'nullable|string',
            'teacher_id' => 'nullable',
        ]);

        $pending = PendingStudent::create([
            'name' => $request->name,
            'class' => $request->class,
            'roll_no' => $request->roll_no,
            'student_status' => $request->student_status ?? 'Active',
            'teacher_id' => $request->teacher_id,
            'approval_status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Student approval request submitted successfully',
            'data' => $pending
        ], 201);
    }

    // Approve student (adds to users table, status active, deletes pending record)
    public function approve($id)
    {
        $pending = PendingStudent::find($id);

        if (!$pending) {
            return response()->json([
                'success' => false,
                'message' => 'Pending request not found'
            ], 404);
        }

        // Generate email/password since table users columns are NOT nullable
        $cleanName = str_replace(' ', '', strtolower($pending->name));
        $email = $cleanName . '_' . strtolower($pending->roll_no) . '@school.com';

        // Check if email already exists in users to prevent unique constraint crash
        $existing = User::where('email', $email)->first();
        if ($existing) {
            $email = $cleanName . '_' . strtolower($pending->roll_no) . '_' . rand(100, 999) . '@school.com';
        }

        // Create student in users table
        $user = User::create([
            'username' => $pending->name,
            'email' => $email,
            'password' => bcrypt('123456'), // default password
            'phone' => null,
            'role' => 'student',
        ]);

        // Delete pending request
        $pending->delete();

        return response()->json([
            'success' => true,
            'message' => 'Student approved and added successfully',
            'student' => $user
        ]);
    }

    // Reject student (deletes pending record)
    public function reject($id)
    {
        $pending = PendingStudent::find($id);

        if (!$pending) {
            return response()->json([
                'success' => false,
                'message' => 'Pending request not found'
            ], 404);
        }

        $pending->delete();

        return response()->json([
            'success' => true,
            'message' => 'Student request rejected successfully'
        ]);
    }

    // Approve all selected students
    public function approveAll(Request $request)
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'No IDs provided'
            ], 400);
        }

        $pendingStudents = PendingStudent::whereIn('id', $ids)->get();

        foreach ($pendingStudents as $pending) {
            $cleanName = str_replace(' ', '', strtolower($pending->name));
            $email = $cleanName . '_' . strtolower($pending->roll_no) . '@school.com';

            $existing = User::where('email', $email)->first();
            if ($existing) {
                $email = $cleanName . '_' . strtolower($pending->roll_no) . '_' . rand(100, 999) . '@school.com';
            }

            User::create([
                'username' => $pending->name,
                'email' => $email,
                'password' => bcrypt('123456'),
                'phone' => null,
                'role' => 'student',
            ]);

            $pending->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Selected student requests approved'
        ]);
    }
}
