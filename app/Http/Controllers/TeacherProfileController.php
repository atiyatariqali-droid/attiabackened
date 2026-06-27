<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\UpdateProfileRequest;
use App\Http\Requests\Teacher\ChangePasswordRequest;
use App\Http\Requests\Teacher\ChangeEmailRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TeacherProfileController extends Controller
{
    /**
     * GET /api/teacher/profile
     */
    public function show(Request $request): JsonResponse
    {
        $teacher = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'id'         => $teacher->id,
                'name'       => $teacher->username,
                'email'      => $teacher->email,
                'phone'      => $teacher->phone ?? '',
                'role'       => $teacher->role,
                'last_login' => $teacher->last_login_at
                    ? \Carbon\Carbon::parse($teacher->last_login_at)->format('d M Y, h:i A')
                    : null,
                'created_at' => $teacher->created_at?->format('d M Y'),
            ],
        ]);
    }

    /**
     * PUT /api/teacher/profile
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $teacher = $request->user();

        $teacher->update([
            'username' => $request->name,
            'phone'    => $request->phone ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data'    => [
                'name'  => $teacher->fresh()->username,
                'phone' => $teacher->fresh()->phone ?? '',
            ],
        ]);
    }

    /**
     * POST /api/teacher/profile/change-password
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $teacher = $request->user();

        if (!Hash::check($request->current_password, $teacher->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect',
                'errors'  => ['current_password' => ['Current password is incorrect']],
            ], 422);
        }

        $teacher->update(['password' => Hash::make($request->new_password)]);

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully',
        ]);
    }

    /**
     * POST /api/teacher/profile/change-email
     */
    public function changeEmail(ChangeEmailRequest $request): JsonResponse
    {
        $teacher = $request->user();

        if (!Hash::check($request->current_password, $teacher->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect',
                'errors'  => ['current_password' => ['Current password is incorrect']],
            ], 422);
        }

        $teacher->update(['email' => $request->new_email]);

        return response()->json([
            'success' => true,
            'message' => 'Email updated successfully',
        ]);
    }

    /**
     * POST /api/teacher/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * POST /api/teacher/logout-all
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out from all devices successfully',
        ]);
    }
}