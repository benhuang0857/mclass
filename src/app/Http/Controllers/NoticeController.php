<?php

namespace App\Http\Controllers;

use App\Models\Notice;
use Illuminate\Http\Request;

class NoticeController extends Controller
{
    public function index()
    {
        $notices = Notice::with('noticeType')->get();
        return response()->json($notices);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'feature_img' => 'string|nullable',
            'notice_type_id' => 'required|exists:notice_types,id',
            'body' => 'required|string',
            'status' => 'boolean',
        ]);

        $notice = Notice::create($validated);
        return response()->json($notice->load('noticeType'), 201);
    }

    public function show($id)
    {
        $notice = Notice::with('noticeType')->findOrFail($id);
        return response()->json($notice);
    }

    public function update(Request $request, $id)
    {
        $notice = Notice::findOrFail($id);

        $validated = $request->validate([
            'title' => 'string|max:255',
            'feature_img' => 'string|nullable',
            'notice_type_id' => 'exists:notice_types,id',
            'body' => 'string',
            'status' => 'boolean',
        ]);

        $notice->update($validated);
        return response()->json($notice->load('noticeType'));
    }

    public function destroy($id)
    {
        $notice = Notice::findOrFail($id);
        $notice->delete();
        return response()->json(['message' => 'Notice deleted successfully.']);
    }
}
