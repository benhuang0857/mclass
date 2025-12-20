<?php

namespace App\Http\Controllers;

use App\Models\SlideshowType;
use Illuminate\Http\Request;

class SlideshowTypeController extends Controller
{
    /**
     * @OA\Get(
     *     path="/slideshow-types",
     *     summary="Get all slideshow types",
     *     description="Retrieve a list of all slideshow types",
     *     operationId="getSlideshowTypes",
     *     tags={"Types - Slideshow Types"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Hero Banner"),
     *                 @OA\Property(property="slug", type="string", example="hero-banner"),
     *                 @OA\Property(property="note", type="string", example="Main homepage hero banners"),
     *                 @OA\Property(property="sort", type="integer", example=1),
     *                 @OA\Property(property="status", type="boolean", example=true)
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $slideshowTypes = SlideshowType::all();
        return response()->json($slideshowTypes);
    }

    /**
     * @OA\Post(
     *     path="/slideshow-types",
     *     summary="Create a new slideshow type",
     *     description="Create a new slideshow type",
     *     operationId="createSlideshowType",
     *     tags={"Types - Slideshow Types"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","slug"},
     *             @OA\Property(property="name", type="string", maxLength=255, example="Promotional Banner"),
     *             @OA\Property(property="slug", type="string", maxLength=255, example="promotional-banner"),
     *             @OA\Property(property="note", type="string", example="Promotional and marketing banners"),
     *             @OA\Property(property="sort", type="integer", example=2),
     *             @OA\Property(property="status", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Slideshow type created successfully"
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
            'slug' => 'required|string|max:255|unique:slideshow_types,slug',
            'note' => 'nullable|string',
            'sort' => 'integer',
            'status' => 'boolean',
        ]);

        $slideshowType = SlideshowType::create($validated);
        return response()->json($slideshowType, 201);
    }

    /**
     * @OA\Get(
     *     path="/slideshow-types/{id}",
     *     summary="Get specific slideshow type",
     *     description="Retrieve detailed information about a specific slideshow type",
     *     operationId="getSlideshowType",
     *     tags={"Types - Slideshow Types"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Slideshow type ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Slideshow type not found"
     *     )
     * )
     */
    public function show($id)
    {
        $slideshowType = SlideshowType::findOrFail($id);
        return response()->json($slideshowType);
    }

    /**
     * @OA\Put(
     *     path="/slideshow-types/{id}",
     *     summary="Update slideshow type",
     *     description="Update an existing slideshow type",
     *     operationId="updateSlideshowType",
     *     tags={"Types - Slideshow Types"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Slideshow type ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","slug"},
     *             @OA\Property(property="name", type="string", maxLength=255, example="Event Banner"),
     *             @OA\Property(property="slug", type="string", maxLength=255, example="event-banner"),
     *             @OA\Property(property="note", type="string", example="Special event banners"),
     *             @OA\Property(property="sort", type="integer", example=1),
     *             @OA\Property(property="status", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Slideshow type updated successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Slideshow type not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $slideshowType = SlideshowType::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:slideshow_types,slug,' . $id,
            'note' => 'nullable|string',
            'sort' => 'integer',
            'status' => 'boolean',
        ]);

        $slideshowType->update($validated);
        return response()->json($slideshowType);
    }

    /**
     * @OA\Delete(
     *     path="/slideshow-types/{id}",
     *     summary="Delete slideshow type",
     *     description="Delete a slideshow type",
     *     operationId="deleteSlideshowType",
     *     tags={"Types - Slideshow Types"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Slideshow type ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Slideshow type deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Slideshow Type deleted successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Slideshow type not found"
     *     )
     * )
     */
    public function destroy($id)
    {
        $slideshowType = SlideshowType::findOrFail($id);
        $slideshowType->delete();
        return response()->json(['message' => 'Slideshow Type deleted successfully.']);
    }
}
