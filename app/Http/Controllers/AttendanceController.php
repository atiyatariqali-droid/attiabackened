<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
class AttendanceController extends Controller
{
     public function markAttendance(Request $request)
    {
        $request->validate([

            'student_id' => 'required',
            'class_id' => 'required',
            'attendance_date' => 'required|date',
            'status' => 'required'

        ]);

        $attendance = Attendance::create([

            'student_id' => $request->student_id,
            'class_id' => $request->class_id,
            'attendance_date' => $request->attendance_date,
            'status' => $request->status

        ]);

        return response()->json([

            'message' => 'Attendance Marked Successfully',
            'data' => $attendance

        ]);
    }
}
