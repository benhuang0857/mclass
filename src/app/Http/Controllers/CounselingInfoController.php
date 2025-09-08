<?php

namespace App\Http\Controllers;

use App\Models\CounselingInfo;
use Illuminate\Http\Request;

class CounselingInfoController extends Controller
{
    public function index()
    {
        $counselingInfos = CounselingInfo::with(['product', 'counselors'])->get();
        return response()->json($counselingInfos);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:counseling_infos,code',
            'description' => 'nullable|string',
            'details' => 'nullable|string',
            'feature_img' => 'nullable|string',
            'counseling_mode' => 'required|in:online,offline,both',
            'session_duration' => 'integer|min:15|max:480',
            'total_sessions' => 'integer|min:1',
            'allow_reschedule' => 'boolean',
            'status' => 'required|in:active,inactive,suspended',
        ]);

        $counselingInfo = CounselingInfo::create($validated);
        return response()->json($counselingInfo, 201);
    }

    public function show($id)
    {
        $counselingInfo = CounselingInfo::with(['product', 'counselors', 'appointments'])
            ->findOrFail($id);
        return response()->json($counselingInfo);
    }

    public function update(Request $request, $id)
    {
        $counselingInfo = CounselingInfo::findOrFail($id);

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:counseling_infos,code,' . $id,
            'description' => 'nullable|string',
            'details' => 'nullable|string',
            'feature_img' => 'nullable|string',
            'counseling_mode' => 'required|in:online,offline,both',
            'session_duration' => 'integer|min:15|max:480',
            'total_sessions' => 'integer|min:1',
            'allow_reschedule' => 'boolean',
            'status' => 'required|in:active,inactive,suspended',
        ]);

        $counselingInfo->update($validated);
        return response()->json($counselingInfo);
    }

    public function destroy($id)
    {
        $counselingInfo = CounselingInfo::findOrFail($id);
        $counselingInfo->delete();
        return response()->json(['message' => 'Counseling info deleted successfully.']);
    }

    public function assignCounselor(Request $request, $id)
    {
        $counselingInfo = CounselingInfo::findOrFail($id);
        
        $validated = $request->validate([
            'counselor_id' => 'required|exists:members,id',
            'is_primary' => 'boolean'
        ]);

        $counselingInfo->counselors()->attach($validated['counselor_id'], [
            'is_primary' => $validated['is_primary'] ?? false
        ]);

        return response()->json(['message' => 'Counselor assigned successfully.']);
    }

    public function removeCounselor(Request $request, $id)
    {
        $counselingInfo = CounselingInfo::findOrFail($id);
        
        $validated = $request->validate([
            'counselor_id' => 'required|exists:members,id'
        ]);

        $counselingInfo->counselors()->detach($validated['counselor_id']);

        return response()->json(['message' => 'Counselor removed successfully.']);
    }
}
