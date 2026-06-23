<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateProfileRequest;
use App\Http\Requests\Admin\ChangePasswordRequest;
use App\Http\Requests\Admin\ChangeEmailRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AdminProfileController extends Controller
{
    /**
     * GET /api/admin/profile
     * Returns the authenticated admin's profile only.
     */
    public function show(Request $request): JsonResponse
    {
        $admin = $request->user();

        // Security: ensure the user is an admin
        if ($admin->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id'         => $admin->id,
                'name'       => $admin->name,
                'email'      => $admin->email,
                'phone'      => $admin->phone ?? '',
                'role'       => $admin->role,
                'last_login' => $admin->last_login_at
                    ? \Carbon\Carbon::parse($admin->last_login_at)->format('d M Y, h:i A')
                    : null,
                'created_at' => $admin->created_at?->format('d M Y'),
            ],
        ]);
    }

    /**
     * PUT /api/admin/profile
     * Updates only name and phone of the authenticated admin.
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $admin = $request->user();

        if ($admin->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $admin->update([
            'name'  => $request->name,
            'phone' => $request->phone ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data'    => [
                'name'  => $admin->fresh()->name,
                'phone' => $admin->fresh()->phone ?? '',
            ],
        ]);
    }

    /**
     * POST /api/admin/profile/change-password
     * Verifies current password before setting a new one.
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $admin = $request->user();

        if ($admin->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Verify current password
        if (!Hash::check($request->current_password, $admin->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect',
                'errors'  => ['current_password' => ['Current password is incorrect']],
            ], 422);
        }

        $admin->update(['password' => Hash::make($request->new_password)]);

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully',
        ]);
    }

    /**
     * POST /api/admin/profile/change-email
     * Verifies current password and checks email uniqueness before updating.
     */
    public function changeEmail(ChangeEmailRequest $request): JsonResponse
    {
        $admin = $request->user();

        if ($admin->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Verify current password
        if (!Hash::check($request->current_password, $admin->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect',
                'errors'  => ['current_password' => ['Current password is incorrect']],
            ], 422);
        }

        $admin->update(['email' => $request->new_email]);

        return response()->json([
            'success' => true,
            'message' => 'Email updated successfully',
        ]);
    }

    /**
     * POST /api/admin/logout
     * Revokes the current token.
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
     * POST /api/admin/logout-all
     * Revokes all tokens for this admin (logout from all devices).
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $admin = $request->user();

        if ($admin->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Revoke all Sanctum tokens
        $admin->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out from all devices successfully',
        ]);
    }
}