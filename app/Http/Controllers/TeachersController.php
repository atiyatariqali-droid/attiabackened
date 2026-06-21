<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Teachers;

class TeachersController extends Controller
{
    // ─────────────────────────────
    // LIST ALL TEACHERS
    // ─────────────────────────────
    public function list()
    {
        return response()->json([
            "success" => true,
            "data"    => Teachers::where('role', 'teacher')->get()
        ]);
    }

    // ─────────────────────────────
    // ADD TEACHER
    // ─────────────────────────────
    public function addTeacher(Request $request)
    {
        // ✅ FIX 1: Added device_mac_address to validation
        // ✅ FIX 2: Explicit 422 response with validation errors so Flutter can debug
        $validated = $request->validate([
            'username'           => 'required|string|max:255',
            'email'              => 'required|email|unique:users,email',
            'password'           => 'required|min:6',
            'phone'              => 'nullable|string|max:20',
            'device_id' => 'nullable|string|max:255',
            'status'    => 'nullable|in:0,1',   // NEW
        ]);

        // ✅ FIX 3: Use validated data only — prevents unexpected field injection
        $teacher = new Teachers();
        $teacher->username           = $validated['username'];
        $teacher->email              = $validated['email'];
        $teacher->password           = bcrypt($validated['password']);
        $teacher->phone              = $validated['phone'] ?? null;
        $teacher->role               = 'teacher';   // always forced server-side
        $teacher->status             = $validated['status'] ?? 1;   // active by default, but respects incoming value
        
        $teacher->device_id = $validated['device_id'] ?? null;

        if ($teacher->save()) {
            return response()->json([
                "success" => true,
                "message" => "Teacher added successfully",
                "data"    => $teacher   // ✅ return created teacher so Flutter can use the id
            ], 201);
        }

        return response()->json([
            "success" => false,
            "message" => "Failed to add teacher"
        ], 500);
    }

    // ─────────────────────────────
    // GET SINGLE TEACHER (EDIT)
    // ─────────────────────────────
    public function editTeacher($id)
    {
        $teacher = Teachers::where('id', $id)
                           ->where('role', 'teacher')
                           ->first();

        if (!$teacher) {
            return response()->json([
                "success" => false,
                "message" => "Teacher not found"
            ], 404);
        }

        return response()->json([
            "success" => true,
            "data"    => $teacher
        ]);
    }

    // ─────────────────────────────
    // UPDATE TEACHER
    // ─────────────────────────────
    public function updateTeacher(Request $request, $id)
    {
        $teacher = Teachers::where('id', $id)
                           ->where('role', 'teacher')
                           ->first();

        if (!$teacher) {
            return response()->json([
                "success" => false,
                "message" => "Teacher not found"
            ], 404);
        }

        // ✅ FIX 4: unique rule now correctly ignores the current teacher's own row
        //           using the actual DB id — prevents false unique email validation failure
        // ✅ FIX 5: device_id added to validation
        $validated = $request->validate([
            'username'  => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email,' . $teacher->id,
            'password'  => 'nullable|min:6',
            'phone'     => 'nullable|string|max:20',
            'device_id' => 'nullable|string|max:255',
            'status'    => 'nullable|in:0,1', 
        ]);

        // ✅ FIX 6: Build update array from validated data only
        $data = [
            'username'  => $validated['username'],
            'email'     => $validated['email'],
            'phone'     => $validated['phone'] ?? $teacher->phone,
            'device_id' => $validated['device_id'] ?? null,
            'status'    => $validated['status'] ?? $teacher->status,
        ];

        // ✅ Only update password if provided
        if (!empty($validated['password'])) {
            $data['password'] = bcrypt($validated['password']);
        }

        $teacher->update($data);

        return response()->json([
            "success" => true,
            "message" => "Teacher updated successfully",
            "data"    => $teacher->fresh()  // return updated record
        ]);
    }

    // ─────────────────────────────
    // DELETE TEACHER
    // ─────────────────────────────
    public function deleteTeacher($id)
    {
        $teacher = Teachers::where('id', $id)
                           ->where('role', 'teacher')
                           ->first();

        if (!$teacher) {
            return response()->json([
                "success" => false,
                "message" => "Teacher not found"
            ], 404);
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
    public function searchTeacher($username)
    {
        $teachers = Teachers::where('role', 'teacher')
            ->where("username", "like", "%$username%")
            ->get();

        if ($teachers->isEmpty()) {
            return response()->json([
                "success" => false,
                "message" => "Teacher not found"
            ], 404);
        }

        return response()->json([
            "success" => true,
            "data"    => $teachers
        ]);
    }

    // ─────────────────────────────
    // SELF-REGISTER TEACHER (PENDING)
    // ─────────────────────────────
    public function registerTeacher(Request $request)
    {
        $validated = $request->validate([
            'username'  => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|min:6',
            'phone'     => 'nullable|string|max:20',
            'device_id' => 'required|string|max:255',
        ]);

        $teacher = new Teachers();
        $teacher->username = $validated['username'];
        $teacher->email    = $validated['email'];
        $teacher->password = bcrypt($validated['password']);
        $teacher->phone    = $validated['phone'] ?? null;
        $teacher->role     = 'teacher';
        $teacher->status   = 0; // pending by default for self-registration!
        $teacher->device_id = $validated['device_id'];

        if ($teacher->save()) {
            return response()->json([
                "success" => true,
                "message" => "Registration successful. Pending admin approval.",
                "data"    => $teacher
            ], 201);
        }

        return response()->json([
            "success" => false,
            "message" => "Failed to register teacher"
        ], 500);
    }

    // ─────────────────────────────
    // APPROVE TEACHER
    // ─────────────────────────────
    public function approve($id)
    {
        $teacher = Teachers::where('id', $id)
                           ->where('role', 'teacher')
                           ->first();

        if (!$teacher) {
            return response()->json([
                "success" => false,
                "message" => "Teacher not found"
            ], 404);
        }

        $teacher->status = 1;
        if ($teacher->save()) {
            return response()->json([
                "success" => true,
                "message" => "Teacher approved successfully"
            ]);
        }

        return response()->json([
            "success" => false,
            "message" => "Failed to approve teacher"
        ], 500);
    }
}