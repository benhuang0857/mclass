<?php

namespace App\Http\Controllers;

use App\Models\NoticeType;
use Illuminate\Http\Request;

class NoticeTypeController extends Controller
{
    /**
     * @OA\Get(
     *     path="/notice-types",
     *     summary="Get all notice types",
     *     description="Retrieve a list of all notice types",
     *     operationId="getNoticeTypes",
     *     tags={"Types - Notice Types"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Announcement"),
     *                 @OA\Property(property="slug", type="string", example="announcement"),
     *                 @OA\Property(property="note", type="string", example="General announcements"),
     *                 @OA\Property(property="sort", type="integer", example=1),
     *                 @OA\Property(property="status", type="boolean", example=true)
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $noticeTypes = NoticeType::all();
        return response()->json($noticeTypes);
    }

    /**
     * @OA\Post(
     *     path="/notice-types",
     *     summary="Create a new notice type",
     *     description="Create a new notice type",
     *     operationId="createNoticeType",
     *     tags={"Types - Notice Types"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","slug"},
     *             @OA\Property(property="name", type="string", maxLength=255, example="Alert"),
     *             @OA\Property(property="slug", type="string", maxLength=255, example="alert"),
     *             @OA\Property(property="note", type="string", example="Important alerts"),
     *             @OA\Property(property="sort", type="integer", example=2),
     *             @OA\Property(property="status", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Notice type created successfully"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/notice-types/{id}",
     *     summary="Get specific notice type",
     *     description="Retrieve detailed information about a specific notice type",
     *     operationId="getNoticeType",
     *     tags={"Types - Notice Types"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Notice type ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Notice type not found"
     *     )
     * )
     */
    public function show($id)
    {
        $noticeType = NoticeType::findOrFail($id);
        return response()->json($noticeType);
    }

    /**
     * @OA\Put(
     *     path="/notice-types/{id}",
     *     summary="Update notice type",
     *     description="Update an existing notice type",
     *     operationId="updateNoticeType",
     *     tags={"Types - Notice Types"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Notice type ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","slug"},
     *             @OA\Property(property="name", type="string", maxLength=255, example="System Alert"),
     *             @OA\Property(property="slug", type="string", maxLength=255, example="system-alert"),
     *             @OA\Property(property="note", type="string", example="Critical system alerts"),
     *             @OA\Property(property="sort", type="integer", example=1),
     *             @OA\Property(property="status", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Notice type updated successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Notice type not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
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

    /**
     * @OA\Delete(
     *     path="/notice-types/{id}",
     *     summary="Delete notice type",
     *     description="Delete a notice type",
     *     operationId="deleteNoticeType",
     *     tags={"Types - Notice Types"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Notice type ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Notice type deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Notice Type deleted successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Notice type not found"
     *     )
     * )
     */
    public function destroy($id)
    {
        $noticeType = NoticeType::findOrFail($id);
        $noticeType->delete();
        return response()->json(['message' => 'Notice Type deleted successfully.']);
    }
}
