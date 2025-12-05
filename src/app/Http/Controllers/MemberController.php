<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Profile;
use App\Models\Contact;
use App\Models\Background;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/members",
     *     summary="Get all members",
     *     description="Retrieve a list of all members with their profiles, contacts, and backgrounds",
     *     operationId="getMembersList",
     *     tags={"Members"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="nickname", type="string", example="JohnDoe"),
     *                 @OA\Property(property="account", type="string", example="john.doe"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="status", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function index()
    {
        $members = Member::with([
            'profile', 
            'contact', 
            'background',
            'background.languages',
            'background.levels',
        ])->get();
        return response()->json($members);
    }

    /**
     * @OA\Post(
     *     path="/api/members",
     *     summary="Create a new member",
     *     description="Create a new member with profile, contact, and background information",
     *     operationId="createMember",
     *     tags={"Members"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Member data",
     *         @OA\JsonContent(
     *             required={"member", "profile", "contact", "background"},
     *             @OA\Property(
     *                 property="member",
     *                 type="object",
     *                 @OA\Property(property="nickname", type="string", example="JohnDoe"),
     *                 @OA\Property(property="account", type="string", example="john.doe"),
     *                 @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *                 @OA\Property(property="email_valid", type="boolean", example=true),
     *                 @OA\Property(property="password", type="string", format="password", example="password123"),
     *                 @OA\Property(property="status", type="boolean", example=true)
     *             ),
     *             @OA\Property(
     *                 property="profile",
     *                 type="object",
     *                 @OA\Property(property="lastname", type="string", example="Doe"),
     *                 @OA\Property(property="firstname", type="string", example="John"),
     *                 @OA\Property(property="gender", type="string", enum={"male", "female", "other"}, example="male"),
     *                 @OA\Property(property="birthday", type="string", format="date", example="1990-01-01"),
     *                 @OA\Property(property="job", type="string", example="Software Engineer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Member created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="nickname", type="string", example="JohnDoe"),
     *             @OA\Property(property="account", type="string", example="john.doe"),
     *             @OA\Property(property="email", type="string", example="john@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
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

            'contact.city' => 'sometimes|required|string|max:255',
            'contact.region' => 'sometimes|required|string|max:255',
            'contact.address' => 'sometimes|required|string|max:255',
            'contact.mobile' => 'sometimes|required|string|max:20|unique:contacts,mobile',
            'contact.mobile_valid' => 'sometimes|required|boolean',

            'background.goals' => 'sometimes|required|array',
            'background.purposes' => 'sometimes|required|array',
            'background.highest_education' => 'sometimes|required|string|max:255',
            'background.schools' => 'sometimes|nullable|array',
            'background.departments' => 'sometimes|nullable|array',
            'background.certificates' => 'sometimes|required|array',

            // relate with background
            'background.languages' => 'sometimes|required|array',
            'background.languages.*' => 'exists:lang_types,id',
            'background.levels' => 'sometimes|required|array',
            'background.levels.*' => 'exists:level_types,id',
        ]);

        $member = Member::create($validated['member']);
        $member->profile()->create($validated['profile']);

        if (isset($validated['contact'])) {
            $member->contact()->create($validated['contact']);
        }

        if (isset($validated['background'])) {
            $member->background()->create($validated['background']);

            // do sync relation
            $background = $member->background;
            if ($background && isset($validated['background']['languages'])) {
                $background->languages()->sync($validated['background']['languages']);
            }
            if ($background && isset($validated['background']['levels'])) {
                $background->levels()->sync($validated['background']['levels']);
            }
        }

        return response()->json($member->load(['profile', 'contact', 'background']), 201);
    }

    /**
     * @OA\Get(
     *     path="/api/members/{id}",
     *     summary="Get specific member",
     *     description="Retrieve detailed information about a specific member including profile, contact, and background",
     *     operationId="getMember",
     *     tags={"Members"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Member ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="nickname", type="string", example="JohnDoe"),
     *             @OA\Property(property="account", type="string", example="john.doe"),
     *             @OA\Property(property="email", type="string", example="john@example.com"),
     *             @OA\Property(property="profile", type="object",
     *                 @OA\Property(property="lastname", type="string", example="Doe"),
     *                 @OA\Property(property="firstname", type="string", example="John"),
     *                 @OA\Property(property="gender", type="string", example="male"),
     *                 @OA\Property(property="birthday", type="string", format="date", example="1990-01-01"),
     *                 @OA\Property(property="job", type="string", example="Software Engineer")
     *             ),
     *             @OA\Property(property="contact", type="object"),
     *             @OA\Property(property="background", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Member not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function show($id)
    {
        $member = Member::with([
            'profile',
            'contact',
            'background',
            'background.languages',
            'background.levels',
        ])->findOrFail($id);
        return response()->json($member);
    }

    /**
     * @OA\Put(
     *     path="/api/members/{id}",
     *     summary="Update member information",
     *     description="Update an existing member's information including profile, contact, and background data",
     *     operationId="updateMember",
     *     tags={"Members"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Member ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="member",
     *                 type="object",
     *                 @OA\Property(property="nickname", type="string", example="JaneDoe"),
     *                 @OA\Property(property="account", type="string", example="jane.doe"),
     *                 @OA\Property(property="email", type="string", format="email", example="jane@example.com"),
     *                 @OA\Property(property="email_valid", type="boolean", example=true),
     *                 @OA\Property(property="password", type="string", format="password", example="newpassword123"),
     *                 @OA\Property(property="status", type="boolean", example=true)
     *             ),
     *             @OA\Property(
     *                 property="profile",
     *                 type="object",
     *                 @OA\Property(property="lastname", type="string", example="Doe"),
     *                 @OA\Property(property="firstname", type="string", example="Jane"),
     *                 @OA\Property(property="gender", type="string", example="female"),
     *                 @OA\Property(property="birthday", type="string", format="date", example="1992-05-15"),
     *                 @OA\Property(property="job", type="string", example="Data Scientist")
     *             ),
     *             @OA\Property(
     *                 property="contact",
     *                 type="object",
     *                 @OA\Property(property="city", type="string", example="New York"),
     *                 @OA\Property(property="region", type="string", example="NY"),
     *                 @OA\Property(property="address", type="string", example="123 Main St"),
     *                 @OA\Property(property="mobile", type="string", example="1234567890"),
     *                 @OA\Property(property="mobile_valid", type="boolean", example=true)
     *             ),
     *             @OA\Property(
     *                 property="background",
     *                 type="object",
     *                 @OA\Property(property="goals", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="purposes", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="highest_education", type="string", example="Master's Degree"),
     *                 @OA\Property(property="schools", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="departments", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="certificates", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="languages", type="array", @OA\Items(type="integer"), example={1, 2}),
     *                 @OA\Property(property="levels", type="array", @OA\Items(type="integer"), example={1, 2})
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Member updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="nickname", type="string", example="JaneDoe"),
     *             @OA\Property(property="profile", type="object"),
     *             @OA\Property(property="contact", type="object"),
     *             @OA\Property(property="background", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Member not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $member = Member::findOrFail($id);
    
        $validated = $request->validate([
            'member.nickname' => 'sometimes|string|max:255',
            'member.account' => 'sometimes|string|max:255|unique:members,account,' . $id,
            'member.email' => 'sometimes|email|max:255|unique:members,email,' . $id,
            'member.email_valid' => 'sometimes|boolean',
            'member.password' => 'sometimes|string|min:8',
            'member.status' => 'sometimes|boolean',

            'profile.lastname' => 'sometimes|string|max:255',
            'profile.firstname' => 'sometimes|string|max:255',
            'profile.gender' => 'sometimes|string',
            'profile.birthday' => 'sometimes|date',
            'profile.job' => 'sometimes|string|max:255',

            'contact.city' => 'sometimes|string|max:255',
            'contact.region' => 'sometimes|string|max:255',
            'contact.address' => 'sometimes|string|max:255',
            'contact.mobile' => 'sometimes|string|max:20|unique:contacts,mobile,' . ($member->contact->id ?? 'NULL'),
            'contact.mobile_valid' => 'sometimes|boolean',

            'background.goals' => 'sometimes|array',
            'background.purposes' => 'sometimes|array',
            'background.highest_education' => 'sometimes|string|max:255',
            'background.schools' => 'sometimes|nullable|array',
            'background.departments' => 'sometimes|nullable|array',
            'background.certificates' => 'sometimes|array',

            // relate with background
            'background.languages' => 'sometimes|array',
            'background.languages.*' => 'exists:lang_types,id',
            'background.levels' => 'sometimes|array',
            'background.levels.*' => 'exists:level_types,id',
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

        // do sync relation
        $background = $member->background;
        if ($background) {
            $background->languages()->sync($validated['background']['languages']);
            $background->levels()->sync($validated['background']['levels']);
        }
    
        return response()->json($member->load(['profile', 'contact', 'background']));
    }    

    /**
     * @OA\Delete(
     *     path="/api/members/{id}",
     *     summary="Delete member",
     *     description="Delete a member and all associated profile, contact, and background information",
     *     operationId="deleteMember",
     *     tags={"Members"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Member ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Member deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Member deleted successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Member not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
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
