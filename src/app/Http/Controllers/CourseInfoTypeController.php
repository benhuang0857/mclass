<?php

namespace App\Http\Controllers;

use App\Models\CourseInfoType;
use Illuminate\Http\Request;

class CourseInfoTypeController extends Controller
{
    /**
     * @OA\Get(
     *     path="/course-info-types",
     *     summary="Get all course info types",
     *     description="Retrieve a list of all course information types",
     *     operationId="getCourseInfoTypes",
     *     tags={"Types - Course Info Types"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Technical"),
     *                 @OA\Property(property="slug", type="string", example="technical"),
     *                 @OA\Property(property="note", type="string", example="Technical courses"),
     *                 @OA\Property(property="sort", type="integer", example=1),
     *                 @OA\Property(property="status", type="boolean", example=true)
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $courseInfoTypes = CourseInfoType::all();
        return response()->json($courseInfoTypes);
    }

    /**
     * @OA\Post(
     *     path="/course-info-types",
     *     summary="Create a new course info type",
     *     description="Create a new course information type",
     *     operationId="createCourseInfoType",
     *     tags={"Types - Course Info Types"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","slug"},
     *             @OA\Property(property="name", type="string", maxLength=255, example="Business"),
     *             @OA\Property(property="slug", type="string", maxLength=255, example="business"),
     *             @OA\Property(property="note", type="string", example="Business courses"),
     *             @OA\Property(property="sort", type="integer", example=2),
     *             @OA\Property(property="status", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Course info type created successfully"
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

        $courseInfoType = CourseInfoType::create($validated);
        return response()->json($courseInfoType, 201);
    }

    /**
     * @OA\Get(
     *     path="/course-info-types/{id}",
     *     summary="Get specific course info type",
     *     description="Retrieve detailed information about a specific course info type",
     *     operationId="getCourseInfoType",
     *     tags={"Types - Course Info Types"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Course info type ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Course info type not found"
     *     )
     * )
     */
    public function show($id)
    {
        $courseInfoType = CourseInfoType::findOrFail($id);
        return response()->json($courseInfoType);
    }

    /**
     * @OA\Put(
     *     path="/course-info-types/{id}",
     *     summary="Update course info type",
     *     description="Update an existing course info type",
     *     operationId="updateCourseInfoType",
     *     tags={"Types - Course Info Types"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Course info type ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","slug"},
     *             @OA\Property(property="name", type="string", maxLength=255, example="Creative"),
     *             @OA\Property(property="slug", type="string", maxLength=255, example="creative"),
     *             @OA\Property(property="note", type="string", example="Creative arts courses"),
     *             @OA\Property(property="sort", type="integer", example=3),
     *             @OA\Property(property="status", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Course info type updated successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Course info type not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $courseInfoType = CourseInfoType::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:roles,slug',
            'note' => 'nullable|string',
            'sort' => 'integer',
            'status' => 'boolean',
        ]);

        $courseInfoType->update($validated);
        return response()->json($courseInfoType);
    }

    /**
     * @OA\Delete(
     *     path="/course-info-types/{id}",
     *     summary="Delete course info type",
     *     description="Delete a course info type",
     *     operationId="deleteCourseInfoType",
     *     tags={"Types - Course Info Types"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Course info type ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Course info type deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="CourseInfoType deleted successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Course info type not found"
     *     )
     * )
     */
    public function destroy($id)
    {
        $courseInfoType = CourseInfoType::findOrFail($id);
        $courseInfoType->delete();
        return response()->json(['message' => 'CourseInfoType deleted successfully.']);
    }
}
