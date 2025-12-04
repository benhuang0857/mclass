<?php

namespace App\Http\Controllers;

use App\Models\CourseStatusType;
use Illuminate\Http\Request;

class CourseStatusTypeController extends Controller
{
    /**
     * @OA\Get(
     *     path="/course-status-types",
     *     summary="Get all course status types",
     *     description="Retrieve a list of all course status types",
     *     operationId="getCourseStatusTypes",
     *     tags={"Types - Course Status Types"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Active"),
     *                 @OA\Property(property="slug", type="string", example="active"),
     *                 @OA\Property(property="note", type="string", example="Currently active courses"),
     *                 @OA\Property(property="sort", type="integer", example=1),
     *                 @OA\Property(property="status", type="boolean", example=true)
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $courseStatusTypes = CourseStatusType::all();
        return response()->json($courseStatusTypes);
    }

    /**
     * @OA\Post(
     *     path="/course-status-types",
     *     summary="Create a new course status type",
     *     description="Create a new course status type",
     *     operationId="createCourseStatusType",
     *     tags={"Types - Course Status Types"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","slug"},
     *             @OA\Property(property="name", type="string", maxLength=255, example="Completed"),
     *             @OA\Property(property="slug", type="string", maxLength=255, example="completed"),
     *             @OA\Property(property="note", type="string", example="Finished courses"),
     *             @OA\Property(property="sort", type="integer", example=2),
     *             @OA\Property(property="status", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Course status type created successfully"
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

        $courseStatusType = CourseStatusType::create($validated);
        return response()->json($courseStatusType, 201);
    }

    /**
     * @OA\Get(
     *     path="/course-status-types/{id}",
     *     summary="Get specific course status type",
     *     description="Retrieve detailed information about a specific course status type",
     *     operationId="getCourseStatusType",
     *     tags={"Types - Course Status Types"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Course status type ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Course status type not found"
     *     )
     * )
     */
    public function show($id)
    {
        $courseStatusType = CourseStatusType::findOrFail($id);
        return response()->json($courseStatusType);
    }

    /**
     * @OA\Put(
     *     path="/course-status-types/{id}",
     *     summary="Update course status type",
     *     description="Update an existing course status type",
     *     operationId="updateCourseStatusType",
     *     tags={"Types - Course Status Types"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Course status type ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","slug"},
     *             @OA\Property(property="name", type="string", maxLength=255, example="Cancelled"),
     *             @OA\Property(property="slug", type="string", maxLength=255, example="cancelled"),
     *             @OA\Property(property="note", type="string", example="Cancelled courses"),
     *             @OA\Property(property="sort", type="integer", example=3),
     *             @OA\Property(property="status", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Course status type updated successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Course status type not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $courseStatusType = CourseStatusType::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:roles,slug',
            'note' => 'nullable|string',
            'sort' => 'integer',
            'status' => 'boolean',
        ]);

        $courseStatusType->update($validated);
        return response()->json($courseStatusType);
    }

    /**
     * @OA\Delete(
     *     path="/course-status-types/{id}",
     *     summary="Delete course status type",
     *     description="Delete a course status type",
     *     operationId="deleteCourseStatusType",
     *     tags={"Types - Course Status Types"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Course status type ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Course status type deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="CourseStatusType deleted successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Course status type not found"
     *     )
     * )
     */
    public function destroy($id)
    {
        $courseStatusType = CourseStatusType::findOrFail($id);
        $courseStatusType->delete();
        return response()->json(['message' => 'CourseStatusType deleted successfully.']);
    }
}
