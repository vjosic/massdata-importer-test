<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:user-management');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $permissions = Permission::paginate(10);
        return view('admin.permissions.index', compact('permissions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.permissions.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:permissions',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        Permission::create(['name' => $request->name]);

        return redirect()->route('admin.permissions.index')
                        ->with('success', 'Permission created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Permission $permission)
    {
        return view('admin.permissions.edit', compact('permission'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Permission $permission)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:permissions,name,' . $permission->id,
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $permission->update(['name' => $request->name]);

        return redirect()->route('admin.permissions.index')
                        ->with('success', 'Permission updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Permission $permission)
    {
        $permission->delete();
        
        return redirect()->route('admin.permissions.index')
                        ->with('success', 'Permission deleted successfully.');
    }

    /**
     * Show form to assign permissions to users
     */
    public function assign()
    {
        $users = User::with('permissions')->get();
        $permissions = Permission::all();
        
        return view('admin.permissions.assign', compact('users', 'permissions'));
    }

    /**
     * Store permission assignments
     */
    public function storeAssignment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $user = User::findOrFail($request->user_id);
        $user->syncPermissions($request->permissions ?? []);

        return redirect()->route('admin.permissions.assign')
                        ->with('success', 'Permissions assigned successfully.');
    }
}
