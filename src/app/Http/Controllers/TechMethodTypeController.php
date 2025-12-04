<?php

namespace App\Http\Controllers;

use App\Models\TeachMethodType;
use Illuminate\Http\Request;

class TechMethodTypeController extends Controller
{
    /**
     * @OA\Get(
     *     path="/tech-method-types",
     *     summary="Get all teaching method types",
     *     description="Retrieve a list of all teaching method types",
     *     operationId="getTechMethodTypes",
     *     tags={"Types - Teaching Method Types"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Lecture"),
     *                 @OA\Property(property="slug", type="string", example="lecture"),
     *                 @OA\Property(property="note", type="string", example="Traditional lecture method"),
     *                 @OA\Property(property="sort", type="integer", example=1),
     *                 @OA\Property(property="status", type="boolean", example=true)
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $teachMethodTypes = TeachMethodType::all();
        return response()->json($teachMethodTypes);
    }

    /**
     * @OA\Post(
     *     path="/tech-method-types",
     *     summary="Create a new teaching method type",
     *     description="Create a new teaching method type",
     *     operationId="createTechMethodType",
     *     tags={"Types - Teaching Method Types"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","slug"},
     *             @OA\Property(property="name", type="string", maxLength=255, example="Workshop"),
     *             @OA\Property(property="slug", type="string", maxLength=255, example="workshop"),
     *             @OA\Property(property="note", type="string", example="Interactive workshop"),
     *             @OA\Property(property="sort", type="integer", example=2),
     *             @OA\Property(property="status", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Teaching method type created successfully"
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
            'slug' => 'required|string|max:255|unique:roles,slug',
            'note' => 'nullable|string',
            'sort' => 'integer',
            'status' => 'boolean',
        ]);

        $teachMethodType = TeachMethodType::create($validated);
        return response()->json($teachMethodType, 201);
    }

    /**
     * @OA\Get(
     *     path="/tech-method-types/{id}",
     *     summary="Get specific teaching method type",
     *     description="Retrieve detailed information about a specific teaching method type",
     *     operationId="getTechMethodType",
     *     tags={"Types - Teaching Method Types"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Teaching method type ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Teaching method type not found"
     *     )
     * )
     */
    public function show($id)
    {
        $teachMethodType = TeachMethodType::findOrFail($id);
        return response()->json($teachMethodType);
    }

    /**
     * @OA\Put(
     *     path="/tech-method-types/{id}",
     *     summary="Update teaching method type",
     *     description="Update an existing teaching method type",
     *     operationId="updateTechMethodType",
     *     tags={"Types - Teaching Method Types"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Teaching method type ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","slug"},
     *             @OA\Property(property="name", type="string", maxLength=255, example="Seminar"),
     *             @OA\Property(property="slug", type="string", maxLength=255, example="seminar"),
     *             @OA\Property(property="note", type="string", example="Group discussion seminar"),
     *             @OA\Property(property="sort", type="integer", example=3),
     *             @OA\Property(property="status", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Teaching method type updated successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Teaching method type not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $teachMethodType = TeachMethodType::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:roles,slug',
            'note' => 'nullable|string',
            'sort' => 'integer',
            'status' => 'boolean',
        ]);

        $teachMethodType->update($validated);
        return response()->json($teachMethodType);
    }

    /**
     * @OA\Delete(
     *     path="/tech-method-types/{id}",
     *     summary="Delete teaching method type",
     *     description="Delete a teaching method type",
     *     operationId="deleteTechMethodType",
     *     tags={"Types - Teaching Method Types"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Teaching method type ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Teaching method type deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="TeachMethodType deleted successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Teaching method type not found"
     *     )
     * )
     */
    public function destroy($id)
    {
        $teachMethodType = TeachMethodType::findOrFail($id);
        $teachMethodType->delete();
        return response()->json(['message' => 'TeachMethodType deleted successfully.']);
    }
}
