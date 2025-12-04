<?php

namespace App\Http\Controllers;

use App\Models\LangType;
use Illuminate\Http\Request;

class LangTypeController extends Controller
{
    /**
     * @OA\Get(
     *     path="/lang-types",
     *     summary="Get all language types",
     *     description="Retrieve a list of all language types",
     *     operationId="getLangTypes",
     *     tags={"Types - Language Types"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="English"),
     *                 @OA\Property(property="slug", type="string", example="english"),
     *                 @OA\Property(property="note", type="string", example="English language"),
     *                 @OA\Property(property="sort", type="integer", example=1),
     *                 @OA\Property(property="status", type="boolean", example=true)
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $langTypes = LangType::all();
        return response()->json($langTypes);
    }

    /**
     * @OA\Post(
     *     path="/lang-types",
     *     summary="Create a new language type",
     *     description="Create a new language type",
     *     operationId="createLangType",
     *     tags={"Types - Language Types"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","slug"},
     *             @OA\Property(property="name", type="string", maxLength=255, example="Chinese"),
     *             @OA\Property(property="slug", type="string", maxLength=255, example="chinese"),
     *             @OA\Property(property="note", type="string", example="Mandarin Chinese"),
     *             @OA\Property(property="sort", type="integer", example=2),
     *             @OA\Property(property="status", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Language type created successfully"
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
            'slug' => 'required|string|max:255|unique:lang_types,slug',
            'note' => 'nullable|string',
            'sort' => 'integer',
            'status' => 'boolean',
        ]);

        $langType = LangType::create($validated);
        return response()->json($langType, 201);
    }

    /**
     * @OA\Get(
     *     path="/lang-types/{id}",
     *     summary="Get specific language type",
     *     description="Retrieve detailed information about a specific language type",
     *     operationId="getLangType",
     *     tags={"Types - Language Types"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Language type ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Language type not found"
     *     )
     * )
     */
    public function show($id)
    {
        $langType = LangType::findOrFail($id);
        return response()->json($langType);
    }

    /**
     * @OA\Put(
     *     path="/lang-types/{id}",
     *     summary="Update language type",
     *     description="Update an existing language type",
     *     operationId="updateLangType",
     *     tags={"Types - Language Types"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Language type ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", maxLength=255, example="Spanish"),
     *             @OA\Property(property="slug", type="string", maxLength=255, example="spanish"),
     *             @OA\Property(property="note", type="string", example="Spanish language"),
     *             @OA\Property(property="sort", type="integer", example=3),
     *             @OA\Property(property="status", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Language type updated successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Language type not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $langType = LangType::findOrFail($id);

        $validated = $request->validate([
            'name' => 'string|max:255',
            'slug' => 'string|max:255|unique:lang_types,slug,' . $id,
            'note' => 'nullable|string',
            'sort' => 'integer',
            'status' => 'boolean',
        ]);

        $langType->update($validated);
        return response()->json($langType);
    }

    /**
     * @OA\Delete(
     *     path="/lang-types/{id}",
     *     summary="Delete language type",
     *     description="Delete a language type",
     *     operationId="deleteLangType",
     *     tags={"Types - Language Types"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Language type ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Language type deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Lang Type deleted successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Language type not found"
     *     )
     * )
     */
    public function destroy($id)
    {
        $langType = LangType::findOrFail($id);
        $langType->delete();
        return response()->json(['message' => 'Lang Type deleted successfully.']);
    }
}
