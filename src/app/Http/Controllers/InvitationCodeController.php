<?php

namespace App\Http\Controllers;

use App\Models\InvitationCode;
use Illuminate\Http\Request;

class InvitationCodeController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/invitation-codes",
     *     summary="Get all invitation codes",
     *     description="Retrieve a list of all invitation codes with their associated members",
     *     operationId="getInvitationCodesList",
     *     tags={"Invitation Codes"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="code", type="string", example="INV12345"),
     *                 @OA\Property(property="from_member_id", type="integer", nullable=true, example=1),
     *                 @OA\Property(property="to_member_id", type="integer", nullable=true, example=2),
     *                 @OA\Property(property="email", type="string", format="email", nullable=true, example="invitee@example.com"),
     *                 @OA\Property(property="expired", type="string", format="date", nullable=true, example="2025-12-31"),
     *                 @OA\Property(property="used", type="boolean", example=false),
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
        $invitationCodes = InvitationCode::with(['fromMember', 'toMember'])->get();
        return response()->json($invitationCodes);
    }

    /**
     * @OA\Post(
     *     path="/api/invitation-codes",
     *     summary="Create a new invitation code",
     *     description="Create a new invitation code with optional member and email associations",
     *     operationId="createInvitationCode",
     *     tags={"Invitation Codes"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Invitation code data",
     *         @OA\JsonContent(
     *             required={"code"},
     *             @OA\Property(property="code", type="string", example="INV12345"),
     *             @OA\Property(property="from_member_id", type="integer", nullable=true, example=1),
     *             @OA\Property(property="to_member_id", type="integer", nullable=true, example=2),
     *             @OA\Property(property="email", type="string", format="email", nullable=true, example="invitee@example.com"),
     *             @OA\Property(property="expired", type="string", format="date", nullable=true, example="2025-12-31"),
     *             @OA\Property(property="used", type="boolean", example=false),
     *             @OA\Property(property="status", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Invitation code created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="code", type="string", example="INV12345"),
     *             @OA\Property(property="from_member_id", type="integer", nullable=true, example=1),
     *             @OA\Property(property="to_member_id", type="integer", nullable=true, example=2),
     *             @OA\Property(property="email", type="string", format="email", nullable=true, example="invitee@example.com"),
     *             @OA\Property(property="expired", type="string", format="date", nullable=true, example="2025-12-31"),
     *             @OA\Property(property="used", type="boolean", example=false),
     *             @OA\Property(property="status", type="boolean", example=true)
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

    /**
     * @OA\Get(
     *     path="/api/invitation-codes/{id}",
     *     summary="Get a specific invitation code",
     *     description="Retrieve detailed information about a specific invitation code",
     *     operationId="getInvitationCodeById",
     *     tags={"Invitation Codes"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Invitation Code ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="code", type="string", example="INV12345"),
     *             @OA\Property(property="from_member_id", type="integer", nullable=true, example=1),
     *             @OA\Property(property="to_member_id", type="integer", nullable=true, example=2),
     *             @OA\Property(property="email", type="string", format="email", nullable=true, example="invitee@example.com"),
     *             @OA\Property(property="expired", type="string", format="date", nullable=true, example="2025-12-31"),
     *             @OA\Property(property="used", type="boolean", example=false),
     *             @OA\Property(property="status", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Invitation code not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function show($id)
    {
        $invitationCode = InvitationCode::with(['fromMember', 'toMember'])->findOrFail($id);

        return response()->json($invitationCode);
    }

    /**
     * @OA\Put(
     *     path="/api/invitation-codes/{id}",
     *     summary="Update an invitation code",
     *     description="Update an existing invitation code's information",
     *     operationId="updateInvitationCode",
     *     tags={"Invitation Codes"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Invitation Code ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         description="Invitation code data to update",
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="string", example="INV12345"),
     *             @OA\Property(property="from_member_id", type="integer", nullable=true, example=1),
     *             @OA\Property(property="to_member_id", type="integer", nullable=true, example=2),
     *             @OA\Property(property="email", type="string", format="email", nullable=true, example="invitee@example.com"),
     *             @OA\Property(property="expired", type="string", format="date", nullable=true, example="2025-12-31"),
     *             @OA\Property(property="used", type="boolean", example=false),
     *             @OA\Property(property="status", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Invitation code updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="code", type="string", example="INV12345"),
     *             @OA\Property(property="from_member_id", type="integer", nullable=true, example=1),
     *             @OA\Property(property="to_member_id", type="integer", nullable=true, example=2),
     *             @OA\Property(property="email", type="string", format="email", nullable=true, example="invitee@example.com"),
     *             @OA\Property(property="expired", type="string", format="date", nullable=true, example="2025-12-31"),
     *             @OA\Property(property="used", type="boolean", example=false),
     *             @OA\Property(property="status", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Invitation code not found"
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

    /**
     * @OA\Delete(
     *     path="/api/invitation-codes/{id}",
     *     summary="Delete an invitation code",
     *     description="Delete a specific invitation code from the system",
     *     operationId="deleteInvitationCode",
     *     tags={"Invitation Codes"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Invitation Code ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Invitation code deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Invitation code deleted successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Invitation code not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function destroy($id)
    {
        $invitationCode = InvitationCode::findOrFail($id);
        $invitationCode->delete();

        return response()->json(['message' => 'Invitation code deleted successfully.']);
    }
}
