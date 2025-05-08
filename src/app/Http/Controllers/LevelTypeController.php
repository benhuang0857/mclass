<?php

namespace App\Http\Controllers;

use App\Models\LevelType;
use Illuminate\Http\Request;

class LevelTypeController extends Controller
{
    public function index()
    {
        $levelTypes = LevelType::all();
        return response()->json($levelTypes);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:level_types,slug',
            'note' => 'nullable|string',
            'sort' => 'integer',
            'status' => 'boolean',
        ]);

        $levelType = LevelType::create($validated);
        return response()->json($levelType, 201);
    }

    public function show($id)
    {
        $levelType = LevelType::findOrFail($id);
        return response()->json($levelType);
    }

    public function update(Request $request, $id)
    {
        $levelType = LevelType::findOrFail($id);

        $validated = $request->validate([
            'name' => 'string|max:255',
            'slug' => 'string|max:255|unique:level_types,slug,' . $id,
            'note' => 'nullable|string',
            'sort' => 'integer',
            'status' => 'boolean',
        ]);

        $levelType->update($validated);
        return response()->json($levelType);
    }

    public function destroy($id)
    {
        $levelType = LevelType::findOrFail($id);
        $levelType->delete();
        return response()->json(['message' => 'Level Type deleted successfully.']);
    }
}
