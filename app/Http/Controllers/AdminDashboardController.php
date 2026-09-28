<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ManageClass; 

class AdminDashboardController extends Controller
{
    // Admin dashboard's stat cards.
    public function adminStats(Request $request)
    {
        $totalStudents = User::where('role', 'student')
            ->where('status', 1) 
            ->count();

        $totalTeachers = User::where('role', 'teacher')->count();

        $totalClasses  = ManageClass::count();
        $activeClasses = ManageClass::where('status', 'active')->count();

        // Distinct subjects across all classes.
        $totalSubjects = ManageClass::whereNotNull('subject')
            ->distinct()
            ->count('subject');

        return response()->json([
            'total_students'  => $totalStudents,
            'total_teachers'  => $totalTeachers,
            'total_classes'   => $totalClasses,
            'active_classes'  => $activeClasses,
            'total_subjects'  => $totalSubjects,
        ]);
    }
}