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
            'device_mac_address' => 'nullable|string|max:255',
        ]);

        // ✅ FIX 3: Use validated data only — prevents unexpected field injection
        $teacher = new Teachers();
        $teacher->username           = $validated['username'];
        $teacher->email              = $validated['email'];
        $teacher->password           = bcrypt($validated['password']);
        $teacher->phone              = $validated['phone'] ?? null;
        $teacher->role               = 'teacher';   // always forced server-side
        $teacher->status             = 1;           // active by default
        $teacher->device_mac_address = $validated['device_mac_address'] ?? null;

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
        // ✅ FIX 5: device_mac_address added to validation
        $validated = $request->validate([
            'username'           => 'required|string|max:255',
            'email'              => 'required|email|unique:users,email,' . $teacher->id,
            'password'           => 'nullable|min:6',
            'phone'              => 'nullable|string|max:20',
            'device_mac_address' => 'nullable|string|max:255',
        ]);

        // ✅ FIX 6: Build update array from validated data only
        $data = [
            'username'           => $validated['username'],
            'email'              => $validated['email'],
            'phone'              => $validated['phone'] ?? $teacher->phone,
            'device_mac_address' => $validated['device_mac_address'] ?? null,
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
}