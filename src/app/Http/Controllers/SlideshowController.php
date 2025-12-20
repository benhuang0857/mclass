<?php

namespace App\Http\Controllers;

use App\Models\Slideshow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SlideshowController extends Controller
{
    /**
     * @OA\Get(
     *     path="/slideshows",
     *     summary="Get all slideshows",
     *     description="Retrieve a list of all slideshows with optional filtering",
     *     operationId="getSlideshowsList",
     *     tags={"Slideshows"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"published", "unpublished", "draft"})
     *     ),
     *     @OA\Parameter(
     *         name="device",
     *         in="query",
     *         description="Filter by device",
     *         required=false,
     *         @OA\Schema(type="string", enum={"all", "mobile", "desktop", "tablet"})
     *     ),
     *     @OA\Parameter(
     *         name="active_only",
     *         in="query",
     *         description="Show only active slideshows",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="slideshow_type_id",
     *         in="query",
     *         description="Filter by slideshow type",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="Welcome Banner"),
     *                 @OA\Property(property="description", type="string", example="Welcome to our platform"),
     *                 @OA\Property(property="image_url", type="string", example="https://example.com/banner.jpg"),
     *                 @OA\Property(property="link_url", type="string", example="https://example.com/welcome"),
     *                 @OA\Property(property="slideshow_type_id", type="integer", example=1),
     *                 @OA\Property(property="start_date", type="string", format="date-time", example="2025-01-01T00:00:00Z"),
     *                 @OA\Property(property="end_date", type="string", format="date-time", example="2025-12-31T23:59:59Z"),
     *                 @OA\Property(property="device", type="string", example="all"),
     *                 @OA\Property(property="display_order", type="integer", example=1),
     *                 @OA\Property(property="status", type="string", example="published"),
     *                 @OA\Property(property="created_by", type="integer", example=1)
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = Slideshow::with(['slideshowType', 'creator']);

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('device')) {
            $query->forDevice($request->device);
        }

        if ($request->boolean('active_only')) {
            $query->active();
        }

        if ($request->has('slideshow_type_id')) {
            $query->where('slideshow_type_id', $request->slideshow_type_id);
        }

        $slideshows = $query->ordered()->get();

        return response()->json($slideshows);
    }

    /**
     * @OA\Get(
     *     path="/slideshows/active",
     *     summary="Get active slideshows",
     *     description="Retrieve currently active slideshows for display on homepage",
     *     operationId="getActiveSlideshows",
     *     tags={"Slideshows"},
     *     @OA\Parameter(
     *         name="device",
     *         in="query",
     *         description="Filter by device type",
     *         required=false,
     *         @OA\Schema(type="string", enum={"mobile", "desktop", "tablet"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="Welcome Banner"),
     *                 @OA\Property(property="description", type="string", example="Welcome to our platform"),
     *                 @OA\Property(property="image_url", type="string", example="https://example.com/banner.jpg"),
     *                 @OA\Property(property="link_url", type="string", example="https://example.com/welcome"),
     *                 @OA\Property(property="device", type="string", example="all"),
     *                 @OA\Property(property="display_order", type="integer", example=1)
     *             )
     *         )
     *     )
     * )
     */
    public function getActive(Request $request)
    {
        $query = Slideshow::active()->with('slideshowType');

        if ($request->has('device')) {
            $query->forDevice($request->device);
        }

        $slideshows = $query->ordered()->get();

        return response()->json($slideshows);
    }

    /**
     * @OA\Post(
     *     path="/slideshows",
     *     summary="Create a new slideshow",
     *     description="Create a new slideshow banner",
     *     operationId="createSlideshow",
     *     tags={"Slideshows"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Slideshow data",
     *         @OA\JsonContent(
     *             required={"title", "image_url", "device", "status"},
     *             @OA\Property(property="title", type="string", maxLength=255, example="Welcome Banner"),
     *             @OA\Property(property="description", type="string", example="Welcome to our platform"),
     *             @OA\Property(property="image_url", type="string", maxLength=500, example="https://example.com/banner.jpg"),
     *             @OA\Property(property="link_url", type="string", maxLength=500, example="https://example.com/welcome"),
     *             @OA\Property(property="slideshow_type_id", type="integer", example=1),
     *             @OA\Property(property="start_date", type="string", format="date-time", example="2025-01-01T00:00:00Z"),
     *             @OA\Property(property="end_date", type="string", format="date-time", example="2025-12-31T23:59:59Z"),
     *             @OA\Property(property="device", type="string", enum={"all", "mobile", "desktop", "tablet"}, example="all"),
     *             @OA\Property(property="display_order", type="integer", example=1),
     *             @OA\Property(property="status", type="string", enum={"published", "unpublished", "draft"}, example="draft"),
     *             @OA\Property(property="created_by", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Slideshow created successfully"
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
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image_url' => 'required|string|max:500',
            'link_url' => 'nullable|string|max:500',
            'slideshow_type_id' => 'nullable|exists:slideshow_types,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'device' => 'required|in:all,mobile,desktop,tablet',
            'display_order' => 'integer|min:0',
            'status' => 'required|in:published,unpublished,draft',
            'created_by' => 'nullable|exists:members,id',
        ]);

        DB::beginTransaction();
        try {
            $slideshow = Slideshow::create($validated);
            $slideshow->load(['slideshowType', 'creator']);
            DB::commit();
            return response()->json($slideshow, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/slideshows/{id}",
     *     summary="Get a specific slideshow",
     *     description="Retrieve detailed information about a specific slideshow",
     *     operationId="getSlideshowById",
     *     tags={"Slideshows"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Slideshow ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Slideshow not found"
     *     )
     * )
     */
    public function show($id)
    {
        $slideshow = Slideshow::with(['slideshowType', 'creator'])->findOrFail($id);
        return response()->json($slideshow);
    }

    /**
     * @OA\Put(
     *     path="/slideshows/{id}",
     *     summary="Update a slideshow",
     *     description="Update an existing slideshow's information",
     *     operationId="updateSlideshow",
     *     tags={"Slideshows"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Slideshow ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         description="Slideshow data to update",
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string", maxLength=255, example="Updated Banner"),
     *             @OA\Property(property="description", type="string", example="Updated description"),
     *             @OA\Property(property="image_url", type="string", maxLength=500, example="https://example.com/new-banner.jpg"),
     *             @OA\Property(property="link_url", type="string", maxLength=500, example="https://example.com/new-link"),
     *             @OA\Property(property="slideshow_type_id", type="integer", example=2),
     *             @OA\Property(property="start_date", type="string", format="date-time", example="2025-02-01T00:00:00Z"),
     *             @OA\Property(property="end_date", type="string", format="date-time", example="2025-11-30T23:59:59Z"),
     *             @OA\Property(property="device", type="string", enum={"all", "mobile", "desktop", "tablet"}, example="mobile"),
     *             @OA\Property(property="display_order", type="integer", example=2),
     *             @OA\Property(property="status", type="string", enum={"published", "unpublished", "draft"}, example="published"),
     *             @OA\Property(property="created_by", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Slideshow updated successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Slideshow not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $slideshow = Slideshow::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'image_url' => 'sometimes|string|max:500',
            'link_url' => 'nullable|string|max:500',
            'slideshow_type_id' => 'nullable|exists:slideshow_types,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'device' => 'sometimes|in:all,mobile,desktop,tablet',
            'display_order' => 'integer|min:0',
            'status' => 'sometimes|in:published,unpublished,draft',
            'created_by' => 'nullable|exists:members,id',
        ]);

        DB::beginTransaction();
        try {
            $slideshow->update($validated);
            $slideshow->load(['slideshowType', 'creator']);
            DB::commit();
            return response()->json($slideshow);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/slideshows/{id}",
     *     summary="Delete a slideshow",
     *     description="Delete a specific slideshow from the system",
     *     operationId="deleteSlideshow",
     *     tags={"Slideshows"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Slideshow ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Slideshow deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Slideshow deleted successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Slideshow not found"
     *     )
     * )
     */
    public function destroy($id)
    {
        $slideshow = Slideshow::findOrFail($id);
        $slideshow->delete();
        return response()->json(['message' => 'Slideshow deleted successfully.']);
    }
}
