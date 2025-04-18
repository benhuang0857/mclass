<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Profile;
use App\Models\Contact;
use App\Models\Background;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    public function index()
    {
        $members = Member::with(['profile', 'contact', 'background'])->get();
        return response()->json($members);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'member.nickname' => 'required|string|max:255',
            'member.account' => 'required|string|max:255|unique:members,account',
            'member.email' => 'required|email|max:255|unique:members,email',
            'member.email_valid' => 'required|boolean',
            'member.password' => 'required|string|min:8',
            'member.status' => 'required|boolean',

            'profile.lastname' => 'required|string|max:255',
            'profile.firstname' => 'required|string|max:255',
            'profile.gender' => 'required|in:male,female,other',
            'profile.birthday' => 'required|date',
            'profile.job' => 'required|string|max:255',

            'contact.city' => 'required|string|max:255',
            'contact.region' => 'required|string|max:255',
            'contact.address' => 'required|string|max:255',
            'contact.mobile' => 'required|string|max:20|unique:contacts,mobile',
            'contact.mobile_valid' => 'required|boolean',

            'background.lang_types' => 'required|array',
            'background.goals' => 'required|array',
            'background.purposes' => 'required|array',
            'background.level' => 'required|string|max:255',
            'background.highest_education' => 'required|string|max:255',
            'background.school' => 'nullable|string|max:255',
            'background.department' => 'nullable|string|max:255',
            'background.certificates' => 'required|array',
        ]);

        $member = Member::create($validated['member']);
        $member->profile()->create($validated['profile']);
        $member->contact()->create($validated['contact']);
        $member->background()->create($validated['background']);

        return response()->json($member->load(['profile', 'contact', 'background']), 201);
    }

    public function show($id)
    {
        $member = Member::with(['profile', 'contact', 'background'])->findOrFail($id);
        return response()->json($member);
    }

    public function update(Request $request, $id)
    {
        $member = Member::findOrFail($id);
    
        $validated = $request->validate([
            'member.nickname' => 'string|max:255',
            'member.account' => 'string|max:255|unique:members,account,' . $id,
            'member.email' => 'email|max:255|unique:members,email,' . $id,
            'member.email_valid' => 'boolean',
            'member.password' => 'string|min:8',
            'member.status' => 'boolean',
    
            'profile.lastname' => 'string|max:255',
            'profile.firstname' => 'string|max:255',
            'profile.gender' => 'string',
            'profile.birthday' => 'date',
            'profile.job' => 'string|max:255',
    
            'contact.city' => 'string|max:255',
            'contact.region' => 'string|max:255',
            'contact.address' => 'string|max:255',
            'contact.mobile' => 'string|max:20|unique:contacts,mobile,' . ($member->contact->id ?? 'NULL'),
            'contact.mobile_valid' => 'boolean',
    
            'background.lang_types' => 'array',
            'background.goals' => 'array',
            'background.purposes' => 'array',
            'background.level' => 'string',
            'background.highest_education' => 'string|max:255',
            'background.school' => 'string|max:255',
            'background.department' => 'string|max:255',
            'background.certificates' => 'array',
        ]);

        $member->update($validated['member'] ?? []);

        if (isset($validated['profile'])) {
            if ($member->profile) {
                $member->profile->update($validated['profile']);
            } else {
                $member->profile()->create($validated['profile']);
            }
        }

        if (isset($validated['contact'])) {
            if ($member->contact) {
                $member->contact->update($validated['contact']);
            } else {
                $member->contact()->create($validated['contact']);
            }
        }

        if (isset($validated['background'])) {
            if ($member->background) {
                $member->background->update($validated['background']);
            } else {
                $member->background()->create($validated['background']);
            }
        }
    
        return response()->json($member->load(['profile', 'contact', 'background']));
    }    

    public function destroy($id)
    {
        $member = Member::findOrFail($id);

        $member->profile()->delete();
        $member->contact()->delete();
        $member->background()->delete();
        $member->delete();

        return response()->json(['message' => 'Member deleted successfully.']);
    }
}
