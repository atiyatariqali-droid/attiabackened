<?php

namespace App\Http\Controllers;

use App\Mail\OtpMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class PasswordResetController extends Controller
{
    //Send OTP to email for password reset
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $email = $request->email;

        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'No account found with this email'], 404);
        }

        $recent = DB::table('password_otps')
            ->where('email', $email)
            ->where('created_at', '>', now()->subSeconds(60))
            ->first();

        if ($recent) {
            return response()->json(['success' => false, 'message' => 'Please wait before requesting another code'], 429);
        }

        $otp = (string) random_int(100000, 999999);

        DB::table('password_otps')->where('email', $email)->delete();

        DB::table('password_otps')->insert([
            'email' => $email,
            'otp' => Hash::make($otp),
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            Mail::to($email)->send(new OtpMail($otp, $user->username));
        } catch (\Throwable $e) {
            Log::error('OTP mail failed: ' . $e->getMessage());
            DB::table('password_otps')->where('email', $email)->delete();
            return response()->json(['success' => false, 'message' => 'Could not send email, please try again'], 500);
        }

        return response()->json(['success' => true, 'message' => 'OTP sent to your email']);
    }

    //Verify OTP
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp' => 'required|digits:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $record = DB::table('password_otps')->where('email', $request->email)->first();

        if (!$record) {
            return response()->json(['success' => false, 'message' => 'No OTP request found, please request a new code'], 404);
        }

        if (now()->greaterThan($record->expires_at)) {
            DB::table('password_otps')->where('email', $request->email)->delete();
            return response()->json(['success' => false, 'message' => 'OTP has expired, please request a new code'], 410);
        }

        if (!Hash::check($request->otp, $record->otp)) {
            return response()->json(['success' => false, 'message' => 'Invalid OTP code'], 422);
        }

        return response()->json(['success' => true, 'message' => 'OTP verified']);
    }

    //Reset password after OTP verification
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp' => 'required|digits:6',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $record = DB::table('password_otps')->where('email', $request->email)->first();

        if (!$record) {
            return response()->json(['success' => false, 'message' => 'No OTP request found, please request a new code'], 404);
        }

        if (now()->greaterThan($record->expires_at)) {
            DB::table('password_otps')->where('email', $request->email)->delete();
            return response()->json(['success' => false, 'message' => 'OTP has expired, please request a new code'], 410);
        }

        if (!Hash::check($request->otp, $record->otp)) {
            return response()->json(['success' => false, 'message' => 'Invalid OTP code'], 422);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        DB::table('password_otps')->where('email', $request->email)->delete();

        return response()->json(['success' => true, 'message' => 'Password reset successfully']);
    }
}