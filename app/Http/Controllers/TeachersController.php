<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Teachers;

class TeachersController extends Controller
{
    //List all teachers
    public function list()
    {
        return response()->json([
            "success" => true,
            "data"    => Teachers::where('role', 'teacher')->get()
        ]);
    }

    //Add teacher
    public function addTeacher(Request $request)
    {
        $validated = $request->validate([
            'username'           => 'required|string|max:255',
            'email'              => 'required|email|unique:users,email',
            'password'           => 'required|min:6',
            'phone'              => 'required|regex:/^[0-9]{11}$/',
            'device_id' => 'nullable|string|max:255',
            'status'    => 'nullable|in:0,1',   
            'address' => 'required'
        ], [
            'phone.required' => 'Please enter the correct number',
            'phone.regex'    => 'Please enter the correct number',
            'address.required' => 'Please enter the address'
        ]);

        // ✅ FIX 3: Use validated data only — prevents unexpected field injection
        $teacher = new Teachers();
        $teacher->username           = $validated['username'];
        $teacher->email              = $validated['email'];
        $teacher->password           = bcrypt($validated['password']);
        $teacher->phone              = $validated['phone'] ?? null;
        $teacher->role               = 'teacher';  
        $teacher->status             = $validated['status'] ?? 1;
        $teacher->address            = $validated['address'];
        $teacher->device_id = $validated['device_id'] ?? null;

        if ($teacher->save()) {
            return response()->json([
                "success" => true,
                "message" => "Teacher added successfully",
                "data"    => $teacher 
            ], 201);
        }

        return response()->json([
            "success" => false,
            "message" => "Failed to add teacher"
        ], 500);
    }

    //Edit teacher 
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

    //Update teacher
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

        $validated = $request->validate([
            'username'  => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email,' . $teacher->id,
            'password'  => 'nullable|min:6',
            'phone'     => 'required|regex:/^[0-9]{11}$/',
            'device_id' => 'nullable|string|max:255',
            'status'    => 'nullable|in:0,1', 
        ], [
            'phone.required' => 'Please enter the correct number',
            'phone.regex'    => 'Please enter the correct number',
        ]);

        $data = [
            'username'  => $validated['username'],
            'email'     => $validated['email'],
            'phone'     => $validated['phone'] ?? $teacher->phone,
            'device_id' => $validated['device_id'] ?? null,
            'status'    => $validated['status'] ?? $teacher->status,
        ];

        if (!empty($validated['password'])) {
            $data['password'] = bcrypt($validated['password']);
        }

        $teacher->update($data);

        return response()->json([
            "success" => true,
            "message" => "Teacher updated successfully",
            "data"    => $teacher->fresh() 
        ]);
    }

    //Delete teacher
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

    //Search teacher
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

    //Register teacher (self-registration)
    public function registerTeacher(Request $request)
    {
        $validated = $request->validate([
            'username'  => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|min:6',
            'phone'     => 'required|regex:/^[0-9]{11}$/',
            'device_id' => 'required|string|max:255',
        ], [
            'phone.required' => 'Please enter the correct number',
            'phone.regex'    => 'Please enter the correct number',
        ]);

        $teacher = new Teachers();
        $teacher->username = $validated['username'];
        $teacher->email    = $validated['email'];
        $teacher->password = bcrypt($validated['password']);
        $teacher->phone    = $validated['phone'] ?? null;
        $teacher->role     = 'teacher';
        $teacher->status   = 0;
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

    //Approve teacher (admin)
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