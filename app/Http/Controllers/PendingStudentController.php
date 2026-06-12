<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class PendingStudentController extends Controller
{
    // List all pending students
    public function list()
    {
        $pending = User::where('role', 'student')
            ->where('status', 0) // pending
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($s) {
                $teacher = $s->teacher_id ? User::find($s->teacher_id) : null;
                return [
                    'id' => $s->id,
                    'name' => $s->username,
                    'class' => $s->class ?? 'N/A',
                    'roll_no' => $s->roll_no ?? 'N/A',
                    'student_status' => 'Pending',
                    'teacher_name' => $teacher ? $teacher->username : 'Admin',
                    'created_at' => $s->created_at ? $s->created_at->diffForHumans() : 'Just Now',
                ];
            });

        return response()->json($pending);
    }

    // Add a student in pending state
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'class' => 'required|string',
            'roll_no' => 'required|string',
            'teacherId' => 'nullable|string',
        ]);

        $cleanName = str_replace(' ', '', strtolower($validated['name']));
        $email = $cleanName . '_' . strtolower($validated['roll_no']) . '@school.com';

        if (User::where('email', $email)->exists()) {
            $email = $cleanName . '_' . strtolower($validated['roll_no']) . '_' . rand(100, 999) . '@school.com';
        }

        User::create([
            'username' => $validated['name'],
            'email' => $email,
            'password' => bcrypt('123456'),
            'role' => 'student',
            'status' => 0, // pending
            'class' => $validated['class'],
            'roll_no' => $validated['roll_no'],
            'teacher_id' => $validated['teacherId'] ?? null,
        ]);

        return response()->json(['success' => true], 201);
    }

    // Approve student
    public function approve($id)
    {
        $student = User::find($id);
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Student not found'], 404);
        }

        $student->update(['status' => 1]);

        return response()->json(['success' => true]);
    }

    // Reject student
    public function reject($id)
    {
        $student = User::find($id);
        if ($student) {
            $student->delete();
        }
        return response()->json(['success' => true]);
    }

    // Approve all selected students
    public function approveAll(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return response()->json(['success' => false, 'message' => 'No IDs provided'], 400);
        }

        User::whereIn('id', $ids)->update(['status' => 1]);

        return response()->json(['success' => true]);
    }
}