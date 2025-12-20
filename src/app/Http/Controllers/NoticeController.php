<?php

namespace App\Http\Controllers;

use App\Models\Notice;
use Illuminate\Http\Request;

class NoticeController extends Controller
{
    /**
     * @OA\Get(
     *     path="/notices",
     *     summary="Get all notices",
     *     description="Retrieve a list of all notices with their types",
     *     operationId="getNoticesList",
     *     tags={"Notices"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="Important Announcement"),
     *                 @OA\Property(property="feature_img", type="string", nullable=true, example="https://example.com/image.jpg"),
     *                 @OA\Property(property="notice_type_id", type="integer", example=1),
     *                 @OA\Property(property="body", type="string", example="This is the notice content"),
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
        $notices = Notice::with('noticeType')->get();
        return response()->json($notices);
    }

    /**
     * @OA\Post(
     *     path="/notices",
     *     summary="Create a new notice",
     *     description="Create a new notice with title, type, and content",
     *     operationId="createNotice",
     *     tags={"Notices"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Notice data",
     *         @OA\JsonContent(
     *             required={"title", "notice_type_id", "body"},
     *             @OA\Property(property="title", type="string", example="Important Announcement"),
     *             @OA\Property(property="feature_img", type="string", nullable=true, example="https://example.com/image.jpg"),
     *             @OA\Property(property="notice_type_id", type="integer", example=1),
     *             @OA\Property(property="body", type="string", example="This is the notice content"),
     *             @OA\Property(property="status", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Notice created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="title", type="string", example="Important Announcement"),
     *             @OA\Property(property="feature_img", type="string", nullable=true, example="https://example.com/image.jpg"),
     *             @OA\Property(property="notice_type_id", type="integer", example=1),
     *             @OA\Property(property="body", type="string", example="This is the notice content"),
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
            'title' => 'required|string|max:255',
            'feature_img' => 'string|nullable',
            'notice_type_id' => 'required|exists:notice_types,id',
            'body' => 'required|string',
            'status' => 'boolean',
        ]);

        $notice = Notice::create($validated);
        return response()->json($notice->load('noticeType'), 201);
    }

    /**
     * @OA\Get(
     *     path="/notices/{id}",
     *     summary="Get a specific notice",
     *     description="Retrieve detailed information about a specific notice",
     *     operationId="getNoticeById",
     *     tags={"Notices"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Notice ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="title", type="string", example="Important Announcement"),
     *             @OA\Property(property="feature_img", type="string", nullable=true, example="https://example.com/image.jpg"),
     *             @OA\Property(property="notice_type_id", type="integer", example=1),
     *             @OA\Property(property="body", type="string", example="This is the notice content"),
     *             @OA\Property(property="status", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Notice not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function show($id)
    {
        $notice = Notice::with('noticeType')->findOrFail($id);
        return response()->json($notice);
    }

    /**
     * @OA\Put(
     *     path="/notices/{id}",
     *     summary="Update a notice",
     *     description="Update an existing notice's information",
     *     operationId="updateNotice",
     *     tags={"Notices"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Notice ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         description="Notice data to update",
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string", example="Updated Announcement"),
     *             @OA\Property(property="feature_img", type="string", nullable=true, example="https://example.com/new-image.jpg"),
     *             @OA\Property(property="notice_type_id", type="integer", example=2),
     *             @OA\Property(property="body", type="string", example="Updated notice content"),
     *             @OA\Property(property="status", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Notice updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="title", type="string", example="Updated Announcement"),
     *             @OA\Property(property="feature_img", type="string", nullable=true, example="https://example.com/new-image.jpg"),
     *             @OA\Property(property="notice_type_id", type="integer", example=2),
     *             @OA\Property(property="body", type="string", example="Updated notice content"),
     *             @OA\Property(property="status", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Notice not found"
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

    /**
     * @OA\Delete(
     *     path="/notices/{id}",
     *     summary="Delete a notice",
     *     description="Delete a specific notice from the system",
     *     operationId="deleteNotice",
     *     tags={"Notices"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Notice ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Notice deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Notice deleted successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Notice not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function destroy($id)
    {
        $notice = Notice::findOrFail($id);
        $notice->delete();
        return response()->json(['message' => 'Notice deleted successfully.']);
    }
}
