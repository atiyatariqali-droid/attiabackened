<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Session;
use App\Models\Attendance;
use Carbon\Carbon;

class ConfirmationController extends Controller
{
    // Calculate how many present students should receive verification notifications (20% rule)
    private function calculateNotificationCount($presentCount)
    {
        if ($presentCount <= 0) {
            return 0;
        }
        return max(1, (int) round($presentCount * 0.20));
    }

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
        // ── NEW: restrict this notification to BS classes only ──
    $class = \App\Models\ManageClass::find($session->class_id);
    if (!$class || stripos(trim($class->class_name), 'BS') !== 0) {
        return response()->json([
            'success' => false,
            'message' => 'Confirmation requests are only enabled for BS classes'
        ], 403);
    }

        \DB::table('confirmation_requests')
            ->where('session_id', $request->session_id)
            ->where('status', 'pending')
            ->update(['status' => 'closed']);

        $markedStudentIds = \DB::table('attendance')
            ->where('session_id', $request->session_id)
            ->whereIn('status', ['present', 'late'])
            ->pluck('student_id')
            ->toArray();

        $totalPresent = count($markedStudentIds);
        $targetUserIds = [];
        $message = 'Please confirm: is your teacher present in the classroom?';

        // Only sends a notification if at least 1 student is present/late.
        // Zero-present sessions get NO notification at all (nothing to verify).
        $targetUserIds = [];
        if ($totalPresent > 0) {
            $pool = $markedStudentIds;
            shuffle($pool);
            $notifyCount   = $this->calculateNotificationCount($totalPresent);
            $targetUserIds = array_slice($pool, 0, $notifyCount);
        }

        $parentMode = \DB::table('system_settings')->where('key', 'parent_verification_mode')->value('value');
        $studentExpiryMinutes = ($parentMode === 'true' || $parentMode === '1' || $parentMode === 1) ? 1440 : 5;

        foreach ($targetUserIds as $chosenId) {
            \DB::table('confirmation_requests')
                ->where('student_id', $chosenId)
                ->where('status', 'pending')
                ->update(['status' => 'closed']);

            $expiryMinutes = $studentExpiryMinutes;

            \DB::table('confirmation_requests')->insert([
                'session_id' => $request->session_id,
                'student_id' => $chosenId,
                'status'     => 'pending',
                'expires_at' => Carbon::now()->addMinutes($expiryMinutes),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            \DB::table('notifications')->insert([
                'student_id'  => $chosenId,
                'session_id'  => $request->session_id,
                'message'     => $message,
                'type'        => 'teacher_verification',
                'is_read'     => 0,
                'created_at'  => Carbon::now(),
                'updated_at'  => Carbon::now(),
            ]);
        }

        return response()->json([
            'success'    => true,
            'message'    => $totalPresent > 0
                ? 'Confirmation request sent to students'
                : 'No students present - no confirmation request sent',
        ]);
    }

    // ── FIXED: directly filter by student_id, no notification dependency ──
    public function getPendingConfirmation(Request $request)
    {
        $request->validate(['student_id' => 'required|integer']);
        $studentId = $request->student_id;

        $confirmationRequest = \DB::table('confirmation_requests')
            ->where('student_id', $studentId)
            ->where('status', 'pending')
            ->where('expires_at', '>', Carbon::now())
            ->latest('created_at')
            ->first();

        if (!$confirmationRequest) {
            return response()->json(['success' => true, 'pending' => false]);
        }

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
            'session_id' => $confirmationRequest->session_id,
            'expires_at' => $confirmationRequest->expires_at,
            'message'    => 'Is your teacher physically present in the classroom?',
        ]);
    }

    public function submitResponse(Request $request)
    {
        $request->validate([
            'request_id' => 'required|integer',
            'student_id' => 'required|integer',
            'response'   => 'required|in:yes,no',
        ]);

        $confirmationRequest = \DB::table('confirmation_requests')
            ->where('id', $request->request_id)
            ->where('student_id', $request->student_id) // ← extra safety check
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

        \DB::table('notifications')
            ->where('student_id', $request->student_id)
            ->where('type', 'teacher_verification')
            ->update(['is_read' => 1]);

        return response()->json([
            'success' => true,
            'message' => 'Response submitted successfully',
        ]);
    }

    public function getResults(Request $request)
    {
        $request->validate(['session_id' => 'required|integer']);

        $requestIds = \DB::table('confirmation_requests')
            ->where('session_id', $request->session_id)
            ->pluck('id');

        if ($requestIds->isEmpty()) {
            return response()->json([
                'success'  => true,
                'requested' => false,
                'message'  => 'No confirmation request sent yet',
            ]);
        }

        $responses = \DB::table('confirmation_responses')
            ->whereIn('request_id', $requestIds)
            ->get();

        $yesCount = $responses->where('response', 'yes')->count();
        $noCount  = $responses->where('response', 'no')->count();
        $total    = $responses->count();
        $totalSelected = $requestIds->count();

        $verdict = 'Awaiting responses';
        if ($total > 0) {
            $hasAdminRequest = \DB::table('confirmation_requests')
                ->join('users', 'users.id', '=', 'confirmation_requests.student_id')
                ->whereIn('confirmation_requests.id', $requestIds)
                ->where('users.role', 'admin')
                ->exists();

            if ($hasAdminRequest) {
                $verdict = $yesCount > 0 ? 'Teacher Present ✓' : 'Teacher NOT Present ✗';
            } else {
                $verdict = $yesCount >= $noCount ? 'Teacher Present ✓' : 'Teacher NOT Present ✗';
            }
        }

        $latestRequest = \DB::table('confirmation_requests')
            ->where('session_id', $request->session_id)
            ->latest('created_at')
            ->first();

        return response()->json([
            'success'        => true,
            'requested'      => true,
            'status'         => $latestRequest->status,
            'expires_at'     => $latestRequest->expires_at,
            'yes_count'      => $yesCount,
            'no_count'       => $noCount,
            'total_responded'=> $total,
            'total_students' => $totalSelected,
            'verdict'        => $verdict,
        ]);
    }

    public function getResponseDirectory(Request $request)
    {
        $request->validate(['session_id' => 'required|integer']);

        $confirmationRequests = \DB::table('confirmation_requests')
            ->where('session_id', $request->session_id)
            ->get();

        if ($confirmationRequests->isEmpty()) {
            return response()->json([
                'success'   => true,
                'requested' => false,
                'data'      => [],
            ]);
        }

        $selectedStudentIds = $confirmationRequests->pluck('student_id');

        $selectedStudents = Attendance::with('student')
            ->where('session_id', $request->session_id)
            ->whereIn('student_id', $selectedStudentIds)
            ->get();

        $requestIdsByStudent = $confirmationRequests->keyBy('student_id');

        $responses = \DB::table('confirmation_responses')
            ->whereIn('request_id', $confirmationRequests->pluck('id'))
            ->get()
            ->keyBy('student_id');

        $directory = $selectedStudents->map(function ($attendance) use ($responses) {
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

        $total    = $selectedStudents->count();
        $yesCount = $directory->where('response', 'yes')->count();
        $noCount  = $directory->where('response', 'no')->count();
        $pending  = $directory->where('response', 'pending')->count();

        $latestRequest = $confirmationRequests->sortByDesc('created_at')->first();

        return response()->json([
            'success'          => true,
            'requested'        => true,
            'expires_at'       => $latestRequest->expires_at,
            'request_status'   => $latestRequest->status,
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