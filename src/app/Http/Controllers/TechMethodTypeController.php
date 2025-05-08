<?php

namespace App\Http\Controllers;

use App\Models\TeachMethodType;
use Illuminate\Http\Request;

class TechMethodTypeController extends Controller
{
    public function index()
    {
        $teachMethodTypes = TeachMethodType::all();
        return response()->json($teachMethodTypes);
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

        $teachMethodType = TeachMethodType::create($validated);
        return response()->json($teachMethodType, 201);
    }

    public function show($id)
    {
        $teachMethodType = TeachMethodType::findOrFail($id);
        return response()->json($teachMethodType);
    }

    public function update(Request $request, $id)
    {
        $teachMethodType = TeachMethodType::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:roles,slug',
            'note' => 'nullable|string',
            'sort' => 'integer',
            'status' => 'boolean',
        ]);

        $teachMethodType->update($validated);
        return response()->json($teachMethodType);
    }

    public function destroy($id)
    {
        $teachMethodType = TeachMethodType::findOrFail($id);
        $teachMethodType->delete();
        return response()->json(['message' => 'TeachMethodType deleted successfully.']);
    }
}
