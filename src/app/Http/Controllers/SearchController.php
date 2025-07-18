<?php

namespace App\Http\Controllers;

use App\Services\SearchService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SearchController extends Controller
{
    protected $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * 通用搜尋 API
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => 'nullable|string|max:255',
            'types' => 'nullable|array',
            'types.*' => 'string|in:members,products,club_course_infos,notices,orders',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'filters' => 'nullable|array',
            'filters.status' => 'nullable|string',
            'filters.date_from' => 'nullable|date',
            'filters.date_to' => 'nullable|date|after_or_equal:filters.date_from',
            'filters.price_min' => 'nullable|numeric|min:0',
            'filters.price_max' => 'nullable|numeric|min:0',
            'filters.teaching_mode' => 'nullable|string|in:online,offline,hybrid',
            'filters.is_periodic' => 'nullable|boolean',
            'filters.gender' => 'nullable|string|in:male,female,other',
            'filters.city' => 'nullable|string|max:255',
            'filters.language_id' => 'nullable|integer|exists:lang_types,id',
            'filters.level_id' => 'nullable|integer|exists:level_types,id',
        ]);

        try {
            $results = $this->searchService->search($validated);
            
            return response()->json([
                'success' => true,
                'data' => $results,
                'query' => $validated['query'] ?? '',
                'types' => $validated['types'] ?? [],
                'filters' => $validated['filters'] ?? [],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Search failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 全域快速搜尋
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function globalSearch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => 'required|string|max:255',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        try {
            $results = $this->searchService->globalSearch(
                $validated['query'],
                $validated['limit'] ?? 10
            );
            
            return response()->json([
                'success' => true,
                'data' => $results,
                'query' => $validated['query'],
                'total_types' => count($results),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Global search failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 搜尋建議
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function suggestions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => 'required|string|max:255',
            'limit' => 'nullable|integer|min:1|max:20',
        ]);

        try {
            $suggestions = $this->searchService->getSearchSuggestions(
                $validated['query'],
                $validated['limit'] ?? 5
            );
            
            return response()->json([
                'success' => true,
                'data' => $suggestions,
                'query' => $validated['query'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get suggestions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 取得可用的篩選器
     * 
     * @return JsonResponse
     */
    public function getAvailableFilters(): JsonResponse
    {
        try {
            $filters = $this->searchService->getAvailableFilters();
            
            return response()->json([
                'success' => true,
                'data' => $filters,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get available filters',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 會員搜尋
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function searchMembers(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => 'nullable|string|max:255',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'filters' => 'nullable|array',
            'filters.status' => 'nullable|string|in:active,inactive',
            'filters.gender' => 'nullable|string|in:male,female,other',
            'filters.city' => 'nullable|string|max:255',
            'filters.language_id' => 'nullable|integer|exists:lang_types,id',
            'filters.level_id' => 'nullable|integer|exists:level_types,id',
        ]);

        $searchParams = array_merge($validated, ['types' => ['members']]);

        try {
            $results = $this->searchService->search($searchParams);
            
            return response()->json([
                'success' => true,
                'data' => $results['members'] ?? [],
                'query' => $validated['query'] ?? '',
                'filters' => $validated['filters'] ?? [],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Member search failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 商品搜尋
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function searchProducts(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => 'nullable|string|max:255',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'filters' => 'nullable|array',
            'filters.status' => 'nullable|string|in:published,unpublished,sold-out',
            'filters.price_min' => 'nullable|numeric|min:0',
            'filters.price_max' => 'nullable|numeric|min:0',
        ]);

        $searchParams = array_merge($validated, ['types' => ['products']]);

        try {
            $results = $this->searchService->search($searchParams);
            
            return response()->json([
                'success' => true,
                'data' => $results['products'] ?? [],
                'query' => $validated['query'] ?? '',
                'filters' => $validated['filters'] ?? [],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product search failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 課程搜尋
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function searchCourses(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => 'nullable|string|max:255',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'filters' => 'nullable|array',
            'filters.status' => 'nullable|string|in:published,unpublished,completed,pending',
            'filters.teaching_mode' => 'nullable|string|in:online,offline,hybrid',
            'filters.is_periodic' => 'nullable|boolean',
        ]);

        $searchParams = array_merge($validated, ['types' => ['club_course_infos']]);

        try {
            $results = $this->searchService->search($searchParams);
            
            return response()->json([
                'success' => true,
                'data' => $results['club_course_infos'] ?? [],
                'query' => $validated['query'] ?? '',
                'filters' => $validated['filters'] ?? [],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Course search failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}