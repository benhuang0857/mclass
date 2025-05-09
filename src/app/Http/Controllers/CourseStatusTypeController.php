<?php

namespace App\Http\Controllers;

use App\Models\CourseStatusType;
use Illuminate\Http\Request;

class CourseStatusTypeController extends Controller
{
    public function index()
    {
        $courseStatusTypes = CourseStatusType::all();
        return response()->json($courseStatusTypes);
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

        $courseStatusType = CourseStatusType::create($validated);
        return response()->json($courseStatusType, 201);
    }

    public function show($id)
    {
        $courseStatusType = CourseStatusType::findOrFail($id);
        return response()->json($courseStatusType);
    }

    public function update(Request $request, $id)
    {
        $courseStatusType = CourseStatusType::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:roles,slug',
            'note' => 'nullable|string',
            'sort' => 'integer',
            'status' => 'boolean',
        ]);

        $courseStatusType->update($validated);
        return response()->json($courseStatusType);
    }

    public function destroy($id)
    {
        $courseStatusType = CourseStatusType::findOrFail($id);
        $courseStatusType->delete();
        return response()->json(['message' => 'CourseStatusType deleted successfully.']);
    }
}
