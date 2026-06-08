<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Session; 
use Carbon\Carbon;

class SessionController extends Controller
{
    public function login(Request $request)
    {
        return response()->json([
            "success" => true,
            "message" => "Session login working"
        ]);
    }

    public function logout($id)
    {
        return response()->json([
            "success" => true,
            "message" => "Session logout working",
            "id" => $id
        ]);
    }

    public function activeSessions()
    {
        return response()->json([
            "success" => true,
            "data" => []
        ]);
        
        public function createSession(Request $request) {
            $request->validate([
                'teacher_id' => 'required|integer',
                'class_id' => 'required|integer',
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric',
            ]);
    
            // Check: 1 class ki 1 hi active session
            $existing = Session::where('class_id', $request->class_id)
                                ->where('status', 'active')
                                ->first();
            
            if($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Is class ki session pehle se active hai'
                ], 400);
            }
    
            $session = Session::create([
                'teacher_id' => $request->teacher_id,
                'class_id' => $request->class_id,
                'start_time' => Carbon::now(),
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'status' => 'active'
            ]);
    
            return response()->json([
                'success' => true,
                'message' => 'Session create ho gayi',
                'data' => $session
            ], 201);
    }
}
