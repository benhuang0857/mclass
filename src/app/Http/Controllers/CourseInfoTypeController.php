<?php

namespace App\Http\Controllers;

use App\Models\CourseInfoType;
use Illuminate\Http\Request;

class CourseInfoTypeController extends Controller
{
    public function index()
    {
        $courseInfoTypes = CourseInfoType::all();
        return response()->json($courseInfoTypes);
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

        $courseInfoType = CourseInfoType::create($validated);
        return response()->json($courseInfoType, 201);
    }

    public function show($id)
    {
        $courseInfoType = CourseInfoType::findOrFail($id);
        return response()->json($courseInfoType);
    }

    public function update(Request $request, $id)
    {
        $courseInfoType = CourseInfoType::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:roles,slug',
            'note' => 'nullable|string',
            'sort' => 'integer',
            'status' => 'boolean',
        ]);

        $courseInfoType->update($validated);
        return response()->json($courseInfoType);
    }

    public function destroy($id)
    {
        $courseInfoType = CourseInfoType::findOrFail($id);
        $courseInfoType->delete();
        return response()->json(['message' => 'CourseInfoType deleted successfully.']);
    }
}
