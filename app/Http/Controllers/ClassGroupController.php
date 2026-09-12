<?php

namespace App\Http\Controllers;

use App\Models\ClassGroup;
use App\Models\Students;
use Illuminate\Http\Request;

class ClassGroupController extends Controller
{
    // List all physical classes (class groups). Use this — NOT /classes —
    // wherever a student is being enrolled or moved, since a student
    // belongs to a class group, not to one specific subject-offering.
    public function list()
    {
        $groups = ClassGroup::all()->map(function ($group) {
            return [
                'id' => $group->id,
                'name' => $group->name,
                'status' => $group->status,
                'students_count' => Students::where('class_id', $group->id)
                    ->where('role', 'student')
                    ->where('status', 1)
                    ->count(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $groups,
        ]);
    }
}