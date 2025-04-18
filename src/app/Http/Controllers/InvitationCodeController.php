<?php

namespace App\Http\Controllers;

use App\Models\InvitationCode;
use Illuminate\Http\Request;

class InvitationCodeController extends Controller
{
    public function index()
    {
        $invitationCodes = InvitationCode::with(['fromMember', 'toMember'])->get();
        return response()->json($invitationCodes);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:invitation_codes,code',
            'from_member_id' => 'nullable|exists:members,id',
            'to_member_id' => 'nullable|exists:members,id',
            'email' => 'nullable|email',
            'expired' => 'nullable|date',
            'used' => 'boolean',
            'status' => 'boolean',
        ]);

        $invitationCode = InvitationCode::create($validated);

        return response()->json($invitationCode->load(['fromMember', 'toMember']), 201);
    }

    public function show($id)
    {
        $invitationCode = InvitationCode::with(['fromMember', 'toMember'])->findOrFail($id);

        return response()->json($invitationCode);
    }

    public function update(Request $request, $id)
    {
        $invitationCode = InvitationCode::findOrFail($id);

        $validated = $request->validate([
            'code' => 'string|unique:invitation_codes,code,' . $invitationCode->id,
            'from_member_id' => 'nullable|exists:members,id',
            'to_member_id' => 'nullable|exists:members,id',
            'email' => 'nullable|email',
            'expired' => 'nullable|date',
            'used' => 'boolean',
            'status' => 'boolean',
        ]);

        $invitationCode->update($validated);

        return response()->json($invitationCode->load(['fromMember', 'toMember']));
    }

    public function destroy($id)
    {
        $invitationCode = InvitationCode::findOrFail($id);
        $invitationCode->delete();

        return response()->json(['message' => 'Invitation code deleted successfully.']);
    }
}
