<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PendingStudent;
use App\Models\User;

class PendingStudentController extends Controller
{
    // List all pending students - Flutter ko direct array chahiye
    public function list()
    {
        $pending = PendingStudent::where('approval_status', 'pending') // sirf pending wale
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($s) {
                $teacher = $s->teacher_id ? User::find($s->teacher_id) : null;
                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'class' => $s->class,
                    'roll_no' => $s->roll_no,
                    'student_status' => $s->student_status,
                    'teacher_name' => $teacher ? $teacher->username : 'Admin', // Flutter isi key se read karega
                    'created_at' => $s->created_at ? $s->created_at->diffForHumans() : 'Just Now',
                ];
            });

        return response()->json($pending); // wrapper hata diya
    }

    // Add a student in pending state
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'class' => 'required|string',
            'roll_no' => 'required|string',
            'student_status' => 'nullable|string',
            'teacherId' => 'nullable|string', // Flutter yahi bhej raha hai
        ]);

        PendingStudent::create([
            'name' => $validated['name'],
            'class' => $validated['class'],
            'roll_no' => $validated['roll_no'],
            'student_status' => $validated['student_status'] ?? 'Active',
            'teacher_id' => $validated['teacherId'] ?? null, // Admin = null
            'approval_status' => 'pending',
        ]);

        return response()->json(['success' => true], 201);
    }

    // Approve student
    public function approve($id)
    {
        $pending = PendingStudent::find($id);
        if (!$pending) {
            return response()->json(['success' => false, 'message' => 'Pending request not found'], 404);
        }

        $cleanName = str_replace(' ', '', strtolower($pending->name));
        $email = $cleanName . '_' . strtolower($pending->roll_no) . '@school.com';

        if (User::where('email', $email)->exists()) {
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

        return response()->json(['success' => true]);
    }

    // Reject student
    public function reject($id)
    {
        PendingStudent::find($id)?->delete();
        return response()->json(['success' => true]);
    }

    // Approve all selected students
    public function approveAll(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return response()->json(['success' => false, 'message' => 'No IDs provided'], 400);
        }

        $pendings = PendingStudent::whereIn('id', $ids)->get();

        foreach ($pendings as $pending) {
            $cleanName = str_replace(' ', '', strtolower($pending->name));
            $email = $cleanName . '_' . strtolower($pending->roll_no) . '@school.com';

            if (User::where('email', $email)->exists()) {
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

        return response()->json(['success' => true]);
    }
}