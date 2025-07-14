<?php
namespace App\Http\Controllers;

use App\Actions\Role\GetRoles;
use App\Actions\Role\GetRolesEdit;
use App\Actions\Role\StoreRole;
use App\Actions\Role\UpdateRole;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;


class RoleController extends Controller
{
    public function index(GetRoles $getRoles)
    {
        return Inertia::render('Admin/Roles/Index',$getRoles->handle());

    }
    public function store(StoreRoleRequest $request,StoreRole $storeRole)
    {
        $storeRole->handle($request->validated());
        return to_route('roles.index')->with('success', 'Role created successfully.');
    }
    public function edit(Role $role,GetRolesEdit $getrolesedit)
    {
        return Inertia::render('Admin/Roles/Edit',$getrolesedit->handle($role));
    }
    public function update(UpdateRoleRequest $request, Role $role, UpdateRole $updateRole)
    {
        $updateRole->handle($request->validated(), $role);
        return redirect()->route('roles.index')->with('success', 'Role updated successfully.');
    }
}
