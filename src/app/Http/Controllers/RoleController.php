<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        $res = Role::all();
        return response()->json($res);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:roles,slug',
            'note' => 'nullable|string',
            'sort' => 'integer',
            'status' => 'boolean',
        ]);

        $res = Role::create($validated);
        return response()->json($res, 201);
    }

    public function show($id)
    {
        $res = Role::findOrFail($id);
        return response()->json($res);
    }

    public function update(Request $request, $id)
    {
        $res = Role::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:roles,slug',
            'note' => 'nullable|string',
            'sort' => 'integer',
            'status' => 'boolean',
        ]);

        $res->update($validated);
        return response()->json($res);
    }

    public function destroy($id)
    {
        $res = Role::findOrFail($id);
        $res->delete();
        return response()->json(['message' => 'Role deleted successfully.']);
    }
}
