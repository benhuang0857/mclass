<?php

namespace App\Http\Controllers;

use App\Models\LevelType;
use Illuminate\Http\Request;

class LevelTypeController extends Controller
{
    /**
     * @OA\Get(
     *     path="/level-types",
     *     summary="Get all level types",
     *     description="Retrieve a list of all skill/difficulty level types",
     *     operationId="getLevelTypes",
     *     tags={"Types - Level Types"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Beginner"),
     *                 @OA\Property(property="slug", type="string", example="beginner"),
     *                 @OA\Property(property="note", type="string", example="Entry level"),
     *                 @OA\Property(property="sort", type="integer", example=1),
     *                 @OA\Property(property="status", type="boolean", example=true)
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $levelTypes = LevelType::all();
        return response()->json($levelTypes);
    }

    /**
     * @OA\Post(
     *     path="/level-types",
     *     summary="Create a new level type",
     *     description="Create a new skill/difficulty level type",
     *     operationId="createLevelType",
     *     tags={"Types - Level Types"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","slug"},
     *             @OA\Property(property="name", type="string", maxLength=255, example="Intermediate"),
     *             @OA\Property(property="slug", type="string", maxLength=255, example="intermediate"),
     *             @OA\Property(property="note", type="string", example="Mid-level proficiency"),
     *             @OA\Property(property="sort", type="integer", example=2),
     *             @OA\Property(property="status", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Level type created successfully"
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
            'slug' => 'required|string|max:255|unique:level_types,slug',
            'note' => 'nullable|string',
            'sort' => 'integer',
            'status' => 'boolean',
        ]);

        $levelType = LevelType::create($validated);
        return response()->json($levelType, 201);
    }

    /**
     * @OA\Get(
     *     path="/level-types/{id}",
     *     summary="Get specific level type",
     *     description="Retrieve detailed information about a specific level type",
     *     operationId="getLevelType",
     *     tags={"Types - Level Types"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Level type ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Level type not found"
     *     )
     * )
     */
    public function show($id)
    {
        $levelType = LevelType::findOrFail($id);
        return response()->json($levelType);
    }

    /**
     * @OA\Put(
     *     path="/level-types/{id}",
     *     summary="Update level type",
     *     description="Update an existing level type",
     *     operationId="updateLevelType",
     *     tags={"Types - Level Types"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Level type ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", maxLength=255, example="Advanced"),
     *             @OA\Property(property="slug", type="string", maxLength=255, example="advanced"),
     *             @OA\Property(property="note", type="string", example="Expert level"),
     *             @OA\Property(property="sort", type="integer", example=3),
     *             @OA\Property(property="status", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Level type updated successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Level type not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $levelType = LevelType::findOrFail($id);

        $validated = $request->validate([
            'name' => 'string|max:255',
            'slug' => 'string|max:255|unique:level_types,slug,' . $id,
            'note' => 'nullable|string',
            'sort' => 'integer',
            'status' => 'boolean',
        ]);

        $levelType->update($validated);
        return response()->json($levelType);
    }

    /**
     * @OA\Delete(
     *     path="/level-types/{id}",
     *     summary="Delete level type",
     *     description="Delete a level type",
     *     operationId="deleteLevelType",
     *     tags={"Types - Level Types"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Level type ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Level type deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Level Type deleted successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Level type not found"
     *     )
     * )
     */
    public function destroy($id)
    {
        $levelType = LevelType::findOrFail($id);
        $levelType->delete();
        return response()->json(['message' => 'Level Type deleted successfully.']);
    }
}
