<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $roles (comma or pipe separated)
     */
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        // Get the authenticated user
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Please login.'
            ], 401);
        }

        // Split the roles by pipe (e.g. 'admin|teacher') or comma
        $allowedRoles = explode('|', str_replace(',', '|', $roles));

        // Check if user's string role column matches any of the allowed roles
        if (!in_array($user->role, $allowedRoles)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. You do not have the required role to access this route.'
            ], 403);
        }

        return $next($request);
    }
}