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
     *     description="Retrieve a list of all notices with their types, filtering and pagination",
     *     operationId="getNoticesList",
     *     tags={"Notices"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="notice_type_id",
     *         in="query",
     *         description="Filter by notice type ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status (1=active, 0=inactive)",
     *         @OA\Schema(type="string", enum={"1", "0"}, example="1")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of items per page",
     *         @OA\Schema(type="integer", minimum=1, maximum=100, example=20)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="title", type="string", example="Important Announcement"),
     *                         @OA\Property(property="feature_img", type="string", nullable=true, example="https://example.com/image.jpg"),
     *                         @OA\Property(property="notice_type_id", type="integer", example=1),
     *                         @OA\Property(property="body", type="string", example="This is the notice content"),
     *                         @OA\Property(property="status", type="boolean", example=true),
     *                         @OA\Property(property="notice_type", type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="name", type="string", example="General")
     *                         )
     *                     )
     *                 ),
     *                 @OA\Property(property="first_page_url", type="string", example="http://localhost/api/notices?page=1"),
     *                 @OA\Property(property="from", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=5),
     *                 @OA\Property(property="last_page_url", type="string", example="http://localhost/api/notices?page=5"),
     *                 @OA\Property(property="links", type="array",
     *                     @OA\Items(type="object",
     *                         @OA\Property(property="url", type="string", nullable=true, example="http://localhost/api/notices?page=1"),
     *                         @OA\Property(property="label", type="string", example="1"),
     *                         @OA\Property(property="active", type="boolean", example=true)
     *                     )
     *                 ),
     *                 @OA\Property(property="next_page_url", type="string", nullable=true, example="http://localhost/api/notices?page=2"),
     *                 @OA\Property(property="path", type="string", example="http://localhost/api/notices"),
     *                 @OA\Property(property="per_page", type="integer", example=20),
     *                 @OA\Property(property="prev_page_url", type="string", nullable=true, example=null),
     *                 @OA\Property(property="to", type="integer", example=20),
     *                 @OA\Property(property="total", type="integer", example=95)
     *             ),
     *             @OA\Property(property="stats", type="object",
     *                 @OA\Property(property="total_count", type="integer", example=95),
     *                 @OA\Property(property="active_count", type="integer", example=80)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to retrieve notices"),
     *             @OA\Property(property="error", type="string", example="Error message details")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        try {
            $request->validate([
                'notice_type_id' => 'sometimes|exists:notice_types,id',
                'status' => 'sometimes|in:1,0',
                'limit' => 'sometimes|integer|min:1|max:100',
            ]);

            $limit = $request->input('limit', 20);

            // 基底 query（給 stats 用）
            $baseQuery = Notice::query();

            // 列表 query
            $query = Notice::with('noticeType')
                ->orderByDesc('created_at');

            if ($request->filled('notice_type_id')) {
                $query->where('notice_type_id', $request->notice_type_id);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->boolean('status'));
            }

            $notices = $query->paginate($limit);

            return response()->json([
                'success' => true,
                'data' => $notices,
                'stats' => [
                    'total_count' => (clone $baseQuery)->count(),
                    'active_count' => (clone $baseQuery)->where('status', true)->count(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve notices',
                'error' => $e->getMessage(),
            ], 500);
        }
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
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="Important Announcement"),
     *                 @OA\Property(property="feature_img", type="string", nullable=true, example="https://example.com/image.jpg"),
     *                 @OA\Property(property="notice_type_id", type="integer", example=1),
     *                 @OA\Property(property="body", type="string", example="This is the notice content"),
     *                 @OA\Property(property="status", type="boolean", example=true),
     *                 @OA\Property(property="notice_type", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="General")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to create notice"),
     *             @OA\Property(property="error", type="string", example="Error message details")
     *         )
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

        try {
            $notice = Notice::create($validated);
            return response()->json([
                'success' => true,
                'data' => $notice->load('noticeType')
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create notice',
                'error' => $e->getMessage(),
            ], 500);
        }
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
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="Important Announcement"),
     *                 @OA\Property(property="feature_img", type="string", nullable=true, example="https://example.com/image.jpg"),
     *                 @OA\Property(property="notice_type_id", type="integer", example=1),
     *                 @OA\Property(property="body", type="string", example="This is the notice content"),
     *                 @OA\Property(property="status", type="boolean", example=true),
     *                 @OA\Property(property="notice_type", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="General")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Notice not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Notice not found"),
     *             @OA\Property(property="error", type="string", example="Error message details")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function show($id)
    {
        try {
            $notice = Notice::with('noticeType')->findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => $notice
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Notice not found',
                'error' => $e->getMessage(),
            ], 404);
        }
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
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="Updated Announcement"),
     *                 @OA\Property(property="feature_img", type="string", nullable=true, example="https://example.com/new-image.jpg"),
     *                 @OA\Property(property="notice_type_id", type="integer", example=2),
     *                 @OA\Property(property="body", type="string", example="Updated notice content"),
     *                 @OA\Property(property="status", type="boolean", example=false),
     *                 @OA\Property(property="notice_type", type="object",
     *                     @OA\Property(property="id", type="integer", example=2),
     *                     @OA\Property(property="name", type="string", example="Announcement")
     *                 )
     *             )
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
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to update notice"),
     *             @OA\Property(property="error", type="string", example="Error message details")
     *         )
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'title' => 'string|max:255',
            'feature_img' => 'string|nullable',
            'notice_type_id' => 'exists:notice_types,id',
            'body' => 'string',
            'status' => 'boolean',
        ]);

        try {
            $notice = Notice::findOrFail($id);
            $notice->update($validated);
            return response()->json([
                'success' => true,
                'data' => $notice->load('noticeType')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update notice',
                'error' => $e->getMessage(),
            ], 500);
        }
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
     *             @OA\Property(property="success", type="boolean", example=true),
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
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to delete notice"),
     *             @OA\Property(property="error", type="string", example="Error message details")
     *         )
     *     )
     * )
     */
    public function destroy($id)
    {
        try {
            $notice = Notice::findOrFail($id);
            $notice->delete();
            return response()->json([
                'success' => true,
                'message' => 'Notice deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete notice',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
