<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Member;
use App\Services\CommentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    protected $commentService;

    public function __construct(CommentService $commentService)
    {
        $this->commentService = $commentService;
    }

    /**
     * @OA\Get(
     *     path="/comments",
     *     tags={"Comments"},
     *     summary="Get comments for an entity",
     *     description="Retrieve comments for a specific commentable entity",
     *     @OA\Parameter(
     *         name="commentable_type",
     *         in="query",
     *         required=true,
     *         description="Type of entity (e.g., App\Models\Product)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="commentable_id",
     *         in="query",
     *         required=true,
     *         description="ID of the entity",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Sort order",
     *         @OA\Schema(type="string", enum={"newest", "oldest", "popular", "rating_high", "rating_low"})
     *     ),
     *     @OA\Parameter(
     *         name="has_rating",
     *         in="query",
     *         description="Filter comments with ratings",
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="rating",
     *         in="query",
     *         description="Filter by specific rating",
     *         @OA\Schema(type="integer", minimum=1, maximum=5)
     *     ),
     *     @OA\Parameter(
     *         name="root_only",
     *         in="query",
     *         description="Only return root comments (no replies)",
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="pinned_first",
     *         in="query",
     *         description="Show pinned comments first",
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         @OA\Schema(type="integer", minimum=1, maximum=100)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Commentable entity not found"
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'commentable_type' => 'required|string',
            'commentable_id' => 'required|integer',
            'sort' => 'nullable|string|in:newest,oldest,popular,rating_high,rating_low',
            'has_rating' => 'nullable|boolean',
            'rating' => 'nullable|integer|min:1|max:5',
            'root_only' => 'nullable|boolean',
            'pinned_first' => 'nullable|boolean',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        try {
            $commentableType = $validated['commentable_type'];
            $commentableId = $validated['commentable_id'];
            
            // 根據 type 獲取對應的 model
            $commentable = $this->getCommentableModel($commentableType, $commentableId);
            
            if (!$commentable) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commentable entity not found',
                ], 404);
            }

            $comments = $this->commentService->getComments($commentable, $validated);

            return response()->json([
                'success' => true,
                'data' => $comments,
                'commentable' => [
                    'type' => $commentableType,
                    'id' => $commentableId,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get comments',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/comments",
     *     tags={"Comments"},
     *     summary="Create a new comment",
     *     description="Create a comment on an entity",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"commentable_type", "commentable_id", "content"},
     *             @OA\Property(property="commentable_type", type="string", description="Type of entity"),
     *             @OA\Property(property="commentable_id", type="integer", description="ID of the entity"),
     *             @OA\Property(property="content", type="string", maxLength=2000, description="Comment content"),
     *             @OA\Property(property="rating", type="integer", minimum=1, maximum=5, description="Rating (optional)"),
     *             @OA\Property(property="parent_id", type="integer", description="Parent comment ID for replies"),
     *             @OA\Property(property="metadata", type="object", description="Additional metadata")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Comment created successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Commentable entity not found"
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'commentable_type' => 'required|string',
            'commentable_id' => 'required|integer',
            'content' => 'required|string|max:2000',
            'rating' => 'nullable|integer|min:1|max:5',
            'parent_id' => 'nullable|integer|exists:comments,id',
            'metadata' => 'nullable|array',
        ]);

        try {
            $commentableType = $validated['commentable_type'];
            $commentableId = $validated['commentable_id'];
            
            $commentable = $this->getCommentableModel($commentableType, $commentableId);
            
            if (!$commentable) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commentable entity not found',
                ], 404);
            }

            // 這裡應該從認證中獲取用戶，暫時使用 ID 1 作為示例
            $author = Member::find(1);
            if (!$author) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            $comment = $this->commentService->createComment($commentable, $author, $validated);

            return response()->json([
                'success' => true,
                'data' => $comment,
                'message' => 'Comment created successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create comment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/comments/{comment}",
     *     tags={"Comments"},
     *     summary="Get a specific comment",
     *     description="Retrieve details of a specific comment",
     *     @OA\Parameter(
     *         name="comment",
     *         in="path",
     *         description="Comment ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Comment not found"
     *     )
     * )
     */
    public function show(Comment $comment): JsonResponse
    {
        try {
            $comment->load(['author.profile', 'replies.author.profile', 'commentable']);
            
            return response()->json([
                'success' => true,
                'data' => $comment,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get comment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/comments/{comment}",
     *     tags={"Comments"},
     *     summary="Update a comment",
     *     description="Update an existing comment (author only)",
     *     @OA\Parameter(
     *         name="comment",
     *         in="path",
     *         description="Comment ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"content"},
     *             @OA\Property(property="content", type="string", maxLength=2000, description="Updated comment content"),
     *             @OA\Property(property="rating", type="integer", minimum=1, maximum=5, description="Updated rating"),
     *             @OA\Property(property="metadata", type="object", description="Updated metadata")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Comment updated successfully"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized - not the comment author"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Comment not found"
     *     )
     * )
     */
    public function update(Request $request, Comment $comment): JsonResponse
    {
        $validated = $request->validate([
            'content' => 'required|string|max:2000',
            'rating' => 'nullable|integer|min:1|max:5',
            'metadata' => 'nullable|array',
        ]);

        try {
            // 檢查權限：只有作者可以編輯評論
            if ($comment->member_id !== 1) { // 暫時使用 ID 1，實際應該從認證獲取
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            $comment = $this->commentService->updateComment($comment, $validated);

            return response()->json([
                'success' => true,
                'data' => $comment,
                'message' => 'Comment updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update comment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/comments/{comment}",
     *     tags={"Comments"},
     *     summary="Delete a comment",
     *     description="Delete a comment (author only)",
     *     @OA\Parameter(
     *         name="comment",
     *         in="path",
     *         description="Comment ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Comment deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized - not the comment author"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Comment not found"
     *     )
     * )
     */
    public function destroy(Comment $comment): JsonResponse
    {
        try {
            // 檢查權限：只有作者可以刪除評論
            if ($comment->member_id !== 1) { // 暫時使用 ID 1，實際應該從認證獲取
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            $this->commentService->deleteComment($comment);

            return response()->json([
                'success' => true,
                'message' => 'Comment deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete comment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/comments/{comment}/like",
     *     tags={"Comments"},
     *     summary="Toggle like on a comment",
     *     description="Like or unlike a comment",
     *     @OA\Parameter(
     *         name="comment",
     *         in="path",
     *         description="Comment ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Like status updated"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Comment not found"
     *     )
     * )
     */
    public function toggleLike(Comment $comment): JsonResponse
    {
        try {
            // 暫時使用 ID 1，實際應該從認證獲取
            $member = Member::find(1);
            if (!$member) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            $result = $this->commentService->toggleLike($comment, $member);

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Like status updated',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle like',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/comments/{comment}/reaction",
     *     tags={"Comments"},
     *     summary="Add a reaction to a comment",
     *     description="Add an emoji reaction to a comment",
     *     @OA\Parameter(
     *         name="comment",
     *         in="path",
     *         description="Comment ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"type"},
     *             @OA\Property(property="type", type="string", enum={"like", "love", "laugh", "angry", "sad", "wow"}, description="Reaction type")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reaction added successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Comment not found"
     *     )
     * )
     */
    public function addReaction(Request $request, Comment $comment): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|string|in:like,love,laugh,angry,sad,wow',
        ]);

        try {
            $member = Member::find(1);
            if (!$member) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            $reaction = $this->commentService->addReaction($comment, $member, $validated['type']);

            return response()->json([
                'success' => true,
                'data' => $reaction,
                'message' => 'Reaction added successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add reaction',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/comments/{comment}/report",
     *     tags={"Comments"},
     *     summary="Report a comment",
     *     description="Report a comment for inappropriate content",
     *     @OA\Parameter(
     *         name="comment",
     *         in="path",
     *         description="Comment ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"reason"},
     *             @OA\Property(property="reason", type="string", enum={"spam", "inappropriate", "harassment", "misinformation", "other"}, description="Report reason"),
     *             @OA\Property(property="description", type="string", maxLength=500, description="Additional details")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Comment reported successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Comment not found"
     *     )
     * )
     */
    public function report(Request $request, Comment $comment): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|in:spam,inappropriate,harassment,misinformation,other',
            'description' => 'nullable|string|max:500',
        ]);

        try {
            $reporter = Member::find(1);
            if (!$reporter) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            $report = $this->commentService->reportComment($comment, $reporter, $validated);

            return response()->json([
                'success' => true,
                'data' => $report,
                'message' => 'Comment reported successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to report comment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/comments/statistics",
     *     tags={"Comments"},
     *     summary="Get comment statistics",
     *     description="Get statistics for comments on an entity",
     *     @OA\Parameter(
     *         name="commentable_type",
     *         in="query",
     *         required=true,
     *         description="Type of entity",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="commentable_id",
     *         in="query",
     *         required=true,
     *         description="ID of the entity",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Commentable entity not found"
     *     )
     * )
     */
    public function statistics(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'commentable_type' => 'required|string',
            'commentable_id' => 'required|integer',
        ]);

        try {
            $commentable = $this->getCommentableModel($validated['commentable_type'], $validated['commentable_id']);
            
            if (!$commentable) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commentable entity not found',
                ], 404);
            }

            $statistics = $this->commentService->getCommentStatistics($commentable);

            return response()->json([
                'success' => true,
                'data' => $statistics,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/comments/search",
     *     tags={"Comments"},
     *     summary="Search comments",
     *     description="Search for comments with filters",
     *     @OA\Parameter(
     *         name="query",
     *         in="query",
     *         description="Search query string",
     *         @OA\Schema(type="string", maxLength=255)
     *     ),
     *     @OA\Parameter(
     *         name="commentable_type",
     *         in="query",
     *         description="Filter by entity type",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="rating",
     *         in="query",
     *         description="Filter by rating",
     *         @OA\Schema(type="integer", minimum=1, maximum=5)
     *     ),
     *     @OA\Parameter(
     *         name="date_from",
     *         in="query",
     *         description="Filter comments from this date",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="date_to",
     *         in="query",
     *         description="Filter comments to this date",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Sort order",
     *         @OA\Schema(type="string", enum={"newest", "oldest", "popular"})
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         @OA\Schema(type="integer", minimum=1, maximum=100)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => 'nullable|string|max:255',
            'commentable_type' => 'nullable|string',
            'rating' => 'nullable|integer|min:1|max:5',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'sort' => 'nullable|string|in:newest,oldest,popular',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        try {
            $comments = $this->commentService->searchComments($validated['query'] ?? '', $validated);

            return response()->json([
                'success' => true,
                'data' => $comments,
                'query' => $validated['query'] ?? '',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to search comments',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/comments/trending",
     *     tags={"Comments"},
     *     summary="Get trending comments",
     *     description="Retrieve the most popular/trending comments",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function trending(): JsonResponse
    {
        try {
            $comments = $this->commentService->getTrendingComments();

            return response()->json([
                'success' => true,
                'data' => $comments,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get trending comments',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 根據類型獲取可評論的模型
     */
    private function getCommentableModel(string $type, int $id)
    {
        switch ($type) {
            case 'App\\Models\\Product':
                return \App\Models\Product::find($id);
            case 'App\\Models\\ClubCourseInfo':
                return \App\Models\ClubCourseInfo::find($id);
            case 'App\\Models\\ClubCourse':
                return \App\Models\ClubCourse::find($id);
            case 'App\\Models\\Notice':
                return \App\Models\Notice::find($id);
            case 'App\\Models\\Member':
                return \App\Models\Member::find($id);
            default:
                return null;
        }
    }
}