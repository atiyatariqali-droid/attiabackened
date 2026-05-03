<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;


class RoleController extends Controller
{
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

    return [
        'success' => true,
        'permission' => $permission
    ];
} 
           //  Assign Role to User
public function assignRole(Request $request)
{
    $request->validate([
        'user_id' => 'required|exists:users,id',
        'role' => 'required'
    ]);

    $user = User::find($request->user_id);
    $user->assignRole($request->role);

    return [
        'success' => true,
        'msg' => 'Role assigned'
    ];
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

    return [
        'success' => true,
        'msg' => 'Permission assigned to role'
    ];
}
               //Check Role / Permission (important for auth)
public function checkAccess(Request $request)
{
    $user = auth()->user();

    return [
        'roles' => $user->getRoleNames(),
        'permissions' => $user->getAllPermissions()
    ];
}               

}
