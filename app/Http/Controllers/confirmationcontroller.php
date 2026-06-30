<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Session;
use App\Models\Attendance;
use Carbon\Carbon;

class ConfirmationController extends Controller
{
    // ─────────────────────────────────────────────
    // TEACHER: Send confirmation request to students
    // ─────────────────────────────────────────────
    public function requestConfirmation(Request $request)
    {
        $request->validate([
            'session_id' => 'required|integer|exists:attendance_sessions,id',
        ]);

        $session = Session::find($request->session_id);

        if ($session->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Session is not active'
            ], 400);
        }

        // Close any existing pending request for this session
        \DB::table('confirmation_requests')
            ->where('session_id', $request->session_id)
            ->where('status', 'pending')
            ->update(['status' => 'closed']);

        // Create new request — expires in 2 minutes
        $confirmationRequest = \DB::table('confirmation_requests')->insertGetId([
            'session_id' => $request->session_id,
            'status'     => 'pending',
            'expires_at' => Carbon::now()->addMinutes(2),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return response()->json([
            'success'    => true,
            'message'    => 'Confirmation request sent to all students',
            'request_id' => $confirmationRequest,
        ]);
    }
 //get pending confirmation
    public function getPendingConfirmation(Request $request)
{
    $request->validate(['student_id' => 'required|integer']);
    $studentId = $request->student_id;

    // Step 1: Check karo yeh student teacher-verification ke liye selected hua tha
    $notification = \DB::table('notifications')
        ->where('student_id', $studentId)
        ->where('type', 'teacher_verification')
        ->where('is_read', 0)
        ->latest('created_at')
        ->first();

    if (!$notification) {
        return response()->json(['success' => true, 'pending' => false]);
    }

    // Step 2: Confirmation request find karo session ke liye
    $confirmationRequest = \DB::table('confirmation_requests')
        ->where('session_id', $notification->session_id)
        ->where('status', 'pending')
        ->where('expires_at', '>', Carbon::now())
        ->first();

    if (!$confirmationRequest) {
        return response()->json(['success' => true, 'pending' => false]);
    }

    // Step 3: Check already responded
    $alreadyResponded = \DB::table('confirmation_responses')
        ->where('request_id', $confirmationRequest->id)
        ->where('student_id', $studentId)
        ->exists();

    if ($alreadyResponded) {
        return response()->json(['success' => true, 'pending' => false]);
    }

    return response()->json([
        'success'    => true,
        'pending'    => true,
        'request_id' => $confirmationRequest->id,
        'session_id' => $notification->session_id,
        'expires_at' => $confirmationRequest->expires_at,
        'message'    => 'Is your teacher physically present in the classroom?',
    ]);
}

    
    // STUDENT: Submit YES/NO response

    public function submitResponse(Request $request)
    {
        $request->validate([
            'request_id' => 'required|integer',
            'student_id' => 'required|integer',
            'response'   => 'required|in:yes,no',
        ]);

        $confirmationRequest = \DB::table('confirmation_requests')
            ->where('id', $request->request_id)
            ->where('status', 'pending')
            ->first();

        if (!$confirmationRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Request expired or not found'
            ], 404);
        }

        if (Carbon::parse($confirmationRequest->expires_at)->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'This confirmation request has expired'
            ], 400);
        }

        // Prevent duplicate responses
        $exists = \DB::table('confirmation_responses')
            ->where('request_id', $request->request_id)
            ->where('student_id', $request->student_id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'You have already responded'
            ], 400);
        }

        \DB::table('confirmation_responses')->insert([
            'request_id' => $request->request_id,
            'student_id' => $request->student_id,
            'response'   => $request->response,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
        // Response submit hone ke baad notification bhi read mark 
\DB::table('notifications')
    ->where('student_id', $request->student_id)
    ->where('type', 'teacher_verification')
    ->update(['is_read' => 1]);

        return response()->json([
            'success' => true,
            'message' => 'Response submitted successfully',
        ]);
    }

    // ─────────────────────────────────────────────
    // TEACHER/ADMIN: Get confirmation results
    // ─────────────────────────────────────────────
    public function getResults(Request $request)
    {
        $request->validate([
            'session_id' => 'required|integer',
        ]);

        $confirmationRequest = \DB::table('confirmation_requests')
            ->where('session_id', $request->session_id)
            ->latest('created_at')
            ->first();

        if (!$confirmationRequest) {
            return response()->json([
                'success'  => true,
                'requested' => false,
                'message'  => 'No confirmation request sent yet',
            ]);
        }

        $responses = \DB::table('confirmation_responses')
            ->where('request_id', $confirmationRequest->id)
            ->get();

        $yesCount = $responses->where('response', 'yes')->count();
        $noCount  = $responses->where('response', 'no')->count();
        $total    = $responses->count();

        //sirf woh students count karo jinko notification mili thi
    $totalSelected = \DB::table('notifications')
        ->where('session_id', $request->session_id)
        ->where('type', 'teacher_verification')
        ->count();

        $verdict = 'Awaiting responses';
        if ($total > 0) {
            $verdict = $yesCount > $noCount ? 'Teacher Present ✓' : 'Teacher NOT Present ✗';
        }

        return response()->json([
            'success'        => true,
            'requested'      => true,
            'status'         => $confirmationRequest->status,
            'expires_at'     => $confirmationRequest->expires_at,
            'yes_count'      => $yesCount,
            'no_count'       => $noCount,
            'total_responded'=> $total,
            'total_students' => $totalSelected,
            'verdict'        => $verdict,
        ]);
    }
    
// TEACHER: Get response directory (name, status, response, time)
public function getResponseDirectory(Request $request)
{
    $request->validate([
        'session_id' => 'required|integer',
    ]);

    // Latest confirmation request for this session
    $confirmationRequest = \DB::table('confirmation_requests')
        ->where('session_id', $request->session_id)
        ->latest('created_at')
        ->first();

    if (!$confirmationRequest) {
        return response()->json([
            'success'   => true,
            'requested' => false,
            'data'      => [],
        ]);
    }

     // FIX: sirf notification wale 3 students fetch karo
    $selectedStudentIds = \DB::table('notifications')
        ->where('session_id', $request->session_id)
        ->where('type', 'teacher_verification')
        ->pluck('student_id');

    $selectedStudents = Attendance::with('student')
        ->where('session_id', $request->session_id)
        ->whereIn('student_id', $selectedStudentIds)
        ->get();

    // All responses for this request
    $responses = \DB::table('confirmation_responses')
        ->where('request_id', $confirmationRequest->id)
        ->get()
        ->keyBy('student_id'); // student_id => response row

    $directory = $presentStudents->map(function ($attendance) use ($responses) {
        $studentId = $attendance->student_id;
        $response  = $responses->get($studentId);

        return [
            'student_id'    => $studentId,
            'student_name'  => $attendance->student->username ?? 'Unknown',
            'roll_no'       => $attendance->student->roll_no ?? '-',
            'status'        => $attendance->status,
            'response'      => $response ? $response->response : 'pending',
            'responded_at'  => $response
                ? Carbon::parse($response->created_at)->format('h:i A')
                : '-',
        ];
    });

    $total     = $presentStudents->count();
    $yesCount  = $directory->where('response', 'yes')->count();
    $noCount   = $directory->where('response', 'no')->count();
    $pending   = $directory->where('response', 'pending')->count();

    return response()->json([
        'success'          => true,
        'requested'        => true,
        'expires_at'       => $confirmationRequest->expires_at,
        'request_status'   => $confirmationRequest->status,
        'total_students'   => $total,
        'yes_count'        => $yesCount,
        'no_count'         => $noCount,
        'pending_count'    => $pending,
        'verdict'          => $total > 0 && ($yesCount + $noCount) > 0
            ? ($yesCount >= $noCount ? 'Teacher Present ✓' : 'Teacher NOT Present ✗')
            : 'Awaiting responses',
        'data'             => $directory->values(),
    ]);
}
}