<?php

namespace App\Http\Controllers;

use App\Models\LangType;
use Illuminate\Http\Request;

class LangTypeController extends Controller
{
    public function index()
    {
        $langTypes = LangType::all();
        return response()->json($langTypes);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:lang_types,slug',
            'note' => 'nullable|string',
            'sort' => 'integer',
            'status' => 'boolean',
        ]);

        $langType = LangType::create($validated);
        return response()->json($langType, 201);
    }

    public function show($id)
    {
        $langType = LangType::findOrFail($id);
        return response()->json($langType);
    }

    public function update(Request $request, $id)
    {
        $langType = LangType::findOrFail($id);

        $validated = $request->validate([
            'name' => 'string|max:255',
            'slug' => 'string|max:255|unique:lang_types,slug,' . $id,
            'note' => 'nullable|string',
            'sort' => 'integer',
            'status' => 'boolean',
        ]);

        $langType->update($validated);
        return response()->json($langType);
    }

    public function destroy($id)
    {
        $langType = LangType::findOrFail($id);
        $langType->delete();
        return response()->json(['message' => 'Lang Type deleted successfully.']);
    }
}
