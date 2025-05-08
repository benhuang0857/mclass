<?php

namespace App\Http\Controllers;

use App\Models\NoticeType;
use Illuminate\Http\Request;

class NoticeTypeController extends Controller
{
    public function index()
    {
        $noticeTypes = NoticeType::all();
        return response()->json($noticeTypes);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:notice_types,slug',
            'note' => 'nullable|string',
            'sort' => 'integer',
            'status' => 'boolean',
        ]);

        $noticeType = NoticeType::create($validated);
        return response()->json($noticeType, 201);
    }

    public function show($id)
    {
        $noticeType = NoticeType::findOrFail($id);
        return response()->json($noticeType);
    }

    public function update(Request $request, $id)
    {
        $noticeType = NoticeType::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:notice_types,slug',
            'note' => 'nullable|string',
            'sort' => 'integer',
            'status' => 'boolean',
        ]);

        $noticeType->update($validated);
        return response()->json($noticeType);
    }

    public function destroy($id)
    {
        $noticeType = NoticeType::findOrFail($id);
        $noticeType->delete();
        return response()->json(['message' => 'Notice Type deleted successfully.']);
    }
}
