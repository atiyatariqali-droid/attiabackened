<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    // ─────────────────────────────
    // ROLE CRUD FOR FLUTTER
    // ─────────────────────────────
    
    // GET /roles - List all roles
    public function listRoles() {
        $roles = Role::select('id', 'name', 'created_at')->orderBy('id', 'desc')->get();
        return response()->json([
            "success" => true,
            "data" => $roles
        ]);
    }

    // GET /roles/{id} - Get single role
    public function editRole($id) {
        $role = Role::find($id);
        if(!$role) {
            return response()->json([
                "success" => false,
                "message" => "Role not found"
            ], 404);
        }
        return response()->json([
            "success" => true,
            "data" => $role
        ]);
    }

    // PUT /roles/{id} - Update role
    public function updateRole(Request $request, $id) {
        $role = Role::find($id);
        if(!$role) {
            return response()->json([
                "success" => false,
                "message" => "Role not found"
            ], 404);
        }

        $request->validate([
            'name' => 'required|unique:roles,name,'.$id
        ]);

        $role->name = $request->name;
        $role->save();

        return response()->json([
            "success" => true,
            "message" => "Role updated successfully"
        ]);
    }

    // DELETE /roles/{id} - Delete role
    public function deleteRole($id) {
        $role = Role::find($id);
        if(!$role) {
            return response()->json([
                "success" => false,
                "message" => "Role not found"
            ], 404);
        }

        $role->delete();
        return response()->json([
            "success" => true,
            "message" => "Role deleted successfully"
        ]);
    }

    // ─────────────────────────────
    // EXISTING FUNCTIONS - NO CHANGE
    // ─────────────────────────────

    //Create role
    public function createRole(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:roles,name'
        ]);

        $role = Role::create(['name' => $request->name]);

        return response()->json([
            'success' => true,
            'role' => $role
        ]);
    }

    //Create permission
    public function createPermission(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:permissions,name'
        ]);

        $permission = Permission::create(['name' => $request->name]);

        return response()->json([
            'success' => true,
            'permission' => $permission
        ]);
    } 

    //Assign Role to User
    public function assignRole(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required'
        ]);

        $user = User::find($request->user_id);
        $user->assignRole($request->role);

        return response()->json([
            'success' => true,
            'msg' => 'Role assigned'
        ]);
    }                           

    //Assign Permission to Role
    public function assignPermissionToRole(Request $request)
    {
        $request->validate([
            'role' => 'required',
            'permission' => 'required'
        ]);

        $role = Role::findByName($request->role);
        $role->givePermissionTo($request->permission);

        return response()->json([
            'success' => true,
            'msg' => 'Permission assigned to role'
        ]);
    }
    
    //Check Role / Permission (important for auth)
    public function checkAccess(Request $request)
    {
        $user = auth()->user();

        return response()->json([
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()
        ]);
    }               
}