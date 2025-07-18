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
     * 獲取實體的評論列表
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
     * 創建評論
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
     * 顯示單個評論
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
     * 更新評論
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
     * 刪除評論
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
     * 切換點讚狀態
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
     * 添加反應
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
     * 檢舉評論
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
     * 獲取評論統計
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
     * 搜尋評論
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
     * 獲取熱門評論
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