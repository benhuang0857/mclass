<?php

namespace App\Http\Controllers;

use App\Services\SearchService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="Search",
 *     description="Search operations for courses and discussions"
 * )
 */
class SearchController extends Controller
{
    protected $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * Universal search API
     *
     * @OA\Get(
     *     path="/search",
     *     summary="Search courses and discussions",
     *     description="Simple search across club courses and discussions. Returns results with tag indicating source (e.g., '日文俱樂部', '日文討論區')",
     *     operationId="search",
     *     tags={"Search"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="query",
     *         in="query",
     *         description="Search query string",
     *         required=false,
     *         @OA\Schema(type="string", maxLength=255, example="日文")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Search results with tags",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="courses",
     *                     type="object",
     *                     @OA\Property(property="data", type="array", @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="name", type="string"),
     *                         @OA\Property(property="type", type="string", example="courses"),
     *                         @OA\Property(property="tag", type="string", example="日文俱樂部")
     *                     )),
     *                     @OA\Property(property="pagination", type="object")
     *                 ),
     *                 @OA\Property(
     *                     property="discussions",
     *                     type="object",
     *                     @OA\Property(property="data", type="array", @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="title", type="string"),
     *                         @OA\Property(property="type", type="string", example="discussions"),
     *                         @OA\Property(property="tag", type="string", example="日文討論區")
     *                     )),
     *                     @OA\Property(property="pagination", type="object")
     *                 )
     *             ),
     *             @OA\Property(property="query", type="string", example="日文"),
     *             @OA\Property(
     *                 property="types",
     *                 type="array",
     *                 @OA\Items(type="string", example="courses")
     *             ),
     *             @OA\Property(property="filters", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=500, description="Search failed")
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => 'nullable|string|max:255',
            'types' => 'nullable|array',
            'types.*' => 'string|in:courses,discussions',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'filters' => 'nullable|array',
            'filters.status' => 'nullable|string',
            'filters.date_from' => 'nullable|date',
            'filters.date_to' => 'nullable|date|after_or_equal:filters.date_from',
            'filters.teaching_mode' => 'nullable|string|in:online,offline,hybrid',
            'filters.is_periodic' => 'nullable|boolean',
            'filters.language_id' => 'nullable|integer|exists:lang_types,id',
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
     * Global quick search
     *
     * @OA\Get(
     *     path="/search/global",
     *     summary="Global quick search across all entities",
     *     description="Fast global search returning limited results from each entity type",
     *     operationId="globalSearch",
     *     tags={"Search"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="query",
     *         in="query",
     *         description="Search query string",
     *         required=true,
     *         @OA\Schema(type="string", maxLength=255, example="Programming")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Maximum results per entity type",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=50, default=10, example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Global search results",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="members", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="products", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="courses", type="array", @OA\Items(type="object"))
     *             ),
     *             @OA\Property(property="query", type="string", example="Programming"),
     *             @OA\Property(property="total_types", type="integer", example=3)
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Global search failed")
     * )
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
     * Search suggestions
     *
     * @OA\Get(
     *     path="/search/suggestions",
     *     summary="Get search suggestions based on query",
     *     description="Returns autocomplete suggestions for search queries",
     *     operationId="searchSuggestions",
     *     tags={"Search"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="query",
     *         in="query",
     *         description="Partial search query",
     *         required=true,
     *         @OA\Schema(type="string", maxLength=255, example="Prog")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Maximum number of suggestions",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=20, default=5, example=5)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Search suggestions",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(type="string", example="Programming Basics")
     *             ),
     *             @OA\Property(property="query", type="string", example="Prog")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Failed to get suggestions")
     * )
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
     * Get available filters
     *
     * @OA\Get(
     *     path="/search/filters",
     *     summary="Get all available search filters",
     *     description="Returns all available filter options for search functionality",
     *     operationId="getAvailableFilters",
     *     tags={"Search"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Available filters",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="teaching_modes",
     *                     type="array",
     *                     @OA\Items(type="string", example="online")
     *                 ),
     *                 @OA\Property(
     *                     property="statuses",
     *                     type="array",
     *                     @OA\Items(type="string", example="active")
     *                 ),
     *                 @OA\Property(property="languages", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="levels", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=500, description="Failed to get available filters")
     * )
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
     * Search members
     *
     * @OA\Get(
     *     path="/search/members",
     *     summary="Search members only",
     *     description="Search specifically for members with member-specific filters",
     *     operationId="searchMembers",
     *     tags={"Search"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="query",
     *         in="query",
     *         description="Search query",
     *         required=false,
     *         @OA\Schema(type="string", maxLength=255, example="John")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=100, example=20)
     *     ),
     *     @OA\Parameter(
     *         name="filters[status]",
     *         in="query",
     *         description="Member status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"active", "inactive"})
     *     ),
     *     @OA\Parameter(
     *         name="filters[gender]",
     *         in="query",
     *         description="Member gender",
     *         required=false,
     *         @OA\Schema(type="string", enum={"male", "female", "other"})
     *     ),
     *     @OA\Parameter(
     *         name="filters[city]",
     *         in="query",
     *         description="City",
     *         required=false,
     *         @OA\Schema(type="string", maxLength=255)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Member search results",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(type="object")
     *             ),
     *             @OA\Property(property="query", type="string", example="John"),
     *             @OA\Property(property="filters", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=500, description="Member search failed")
     * )
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
     * Search products
     *
     * @OA\Get(
     *     path="/search/products",
     *     summary="Search products only",
     *     description="Search specifically for products with product-specific filters",
     *     operationId="searchProducts",
     *     tags={"Search"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="query",
     *         in="query",
     *         description="Search query",
     *         required=false,
     *         @OA\Schema(type="string", maxLength=255, example="Course")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=100, example=20)
     *     ),
     *     @OA\Parameter(
     *         name="filters[status]",
     *         in="query",
     *         description="Product status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"published", "unpublished", "sold-out"})
     *     ),
     *     @OA\Parameter(
     *         name="filters[price_min]",
     *         in="query",
     *         description="Minimum price",
     *         required=false,
     *         @OA\Schema(type="number", minimum=0, example=100)
     *     ),
     *     @OA\Parameter(
     *         name="filters[price_max]",
     *         in="query",
     *         description="Maximum price",
     *         required=false,
     *         @OA\Schema(type="number", minimum=0, example=5000)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product search results",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(type="object")
     *             ),
     *             @OA\Property(property="query", type="string", example="Course"),
     *             @OA\Property(property="filters", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=500, description="Product search failed")
     * )
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
     * Search courses
     *
     * @OA\Get(
     *     path="/search/courses",
     *     summary="Search courses only",
     *     description="Search specifically for club course infos with course-specific filters",
     *     operationId="searchCourses",
     *     tags={"Search"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="query",
     *         in="query",
     *         description="Search query",
     *         required=false,
     *         @OA\Schema(type="string", maxLength=255, example="Programming")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=100, example=20)
     *     ),
     *     @OA\Parameter(
     *         name="filters[status]",
     *         in="query",
     *         description="Course status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"published", "unpublished", "completed", "pending"})
     *     ),
     *     @OA\Parameter(
     *         name="filters[teaching_mode]",
     *         in="query",
     *         description="Teaching mode",
     *         required=false,
     *         @OA\Schema(type="string", enum={"online", "offline", "hybrid"})
     *     ),
     *     @OA\Parameter(
     *         name="filters[is_periodic]",
     *         in="query",
     *         description="Is periodic course",
     *         required=false,
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Course search results",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(type="object")
     *             ),
     *             @OA\Property(property="query", type="string", example="Programming"),
     *             @OA\Property(property="filters", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=500, description="Course search failed")
     * )
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