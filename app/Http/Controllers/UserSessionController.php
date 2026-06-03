<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserSession;
use Illuminate\Support\Facades\Hash;

class UserSessionController extends Controller
{
    /**
     * User Login Session
     */
    public function login(Request $request)
    {
        $request->validate([

            'email' => 'required|email',
            'password' => 'required',
            'device_id' => 'required',
            'latitude' => 'required',
            'longitude' => 'required'

        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {

            return response()->json([
                'message' => 'Invalid Credentials'
            ], 401);
        }

        // deactivate old sessions

        UserSession::where('user_id', $user->id)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'logout_time' => now()
            ]);

        // create new session

        $session = UserSession::create([

            'user_id' => $user->id,

            'device_id' => $request->device_id,

            'ip_address' => $request->ip(),

            'latitude' => $request->latitude,

            'longitude' => $request->longitude,

            'login_time' => now(),

            'is_active' => true

        ]);

        return response()->json([

            'message' => 'Login Successful',

            'session' => $session

        ]);
    }

    /**
     * Logout Session
     */
    public function logout($id)
    {
        $session = UserSession::find($id);

        if (!$session) {

            return response()->json([
                'message' => 'Session Not Found'
            ], 404);
        }

        $session->update([

            'logout_time' => now(),

            'is_active' => false

        ]);

        return response()->json([

            'message' => 'Logout Successful'

        ]);
    }

    
     
}
