<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class PendingStudentController extends Controller
{
    public function list(Request $request)
    {
        $user = $request->user();
        $query = User::where('role', 'student')->where('status', 0);

        $pending = $query->orderBy('created_at', 'desc')
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

    // NEW: lightweight count for badges — no need to pull the full list
    public function count(Request $request)
    {
        $count = User::where('role', 'student')->where('status', 0)->count();
        return response()->json(['count' => $count]);
    }

    public function store(Request $request)
    {
        // unchanged
    }

    public function approve(Request $request, $id)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Only admins can approve students'], 403);
        }

        $student = User::find($id);
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Student not found'], 404);
        }

        $student->update(['status' => 1]);
        return response()->json(['success' => true]);
    }

    public function reject(Request $request, $id)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Only admins can reject students'], 403);
        }

        $student = User::find($id);
        if ($student) {
            $student->delete();
        }
        return response()->json(['success' => true]);
    }

    public function approveAll(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Only admins can approve students'], 403);
        }

        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return response()->json(['success' => false, 'message' => 'No IDs provided'], 400);
        }

        User::whereIn('id', $ids)->update(['status' => 1]);
        return response()->json(['success' => true]);
    }
}