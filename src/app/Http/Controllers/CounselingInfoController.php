<?php

namespace App\Http\Controllers;

use App\Models\CounselingInfo;
use App\Services\NotificationService;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Counseling Info",
 *     description="Counseling service information management"
 * )
 */
class CounselingInfoController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get list of counseling infos
     *
     * @OA\Get(
     *     path="/counseling-infos",
     *     summary="Get list of counseling service infos",
     *     description="Retrieve all counseling service information with relationships",
     *     operationId="getCounselingInfos",
     *     tags={"Counseling Info"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Counseling infos retrieved successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="product_id", type="integer", example=5),
     *                 @OA\Property(property="name", type="string", example="Career Counseling"),
     *                 @OA\Property(property="code", type="string", example="COUNSEL-001"),
     *                 @OA\Property(property="counseling_mode", type="string", enum={"online", "offline", "both"}, example="online"),
     *                 @OA\Property(property="status", type="string", enum={"active", "inactive", "suspended"}, example="active")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function index()
    {
        $counselingInfos = CounselingInfo::with(['product', 'counselors'])->get();
        return response()->json($counselingInfos);
    }

    /**
     * Create new counseling info
     *
     * @OA\Post(
     *     path="/counseling-infos",
     *     summary="Create a new counseling service info",
     *     description="Create a new counseling service with specified details",
     *     operationId="createCounselingInfo",
     *     tags={"Counseling Info"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"product_id", "name", "code", "counseling_mode", "status"},
     *             @OA\Property(property="product_id", type="integer", example=5),
     *             @OA\Property(property="name", type="string", maxLength=255, example="Career Counseling"),
     *             @OA\Property(property="code", type="string", maxLength=255, example="COUNSEL-001"),
     *             @OA\Property(property="description", type="string", example="Professional career guidance"),
     *             @OA\Property(property="details", type="string", example="Detailed information about the service"),
     *             @OA\Property(property="feature_img", type="string", example="https://example.com/image.jpg"),
     *             @OA\Property(property="counseling_mode", type="string", enum={"online", "offline", "both"}, example="online"),
     *             @OA\Property(property="session_duration", type="integer", minimum=15, maximum=480, example=60),
     *             @OA\Property(property="total_sessions", type="integer", minimum=1, example=5),
     *             @OA\Property(property="allow_reschedule", type="boolean", example=true),
     *             @OA\Property(property="status", type="string", enum={"active", "inactive", "suspended"}, example="active")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Counseling info created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Career Counseling"),
     *             @OA\Property(property="code", type="string", example="COUNSEL-001")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:counseling_infos,code',
            'description' => 'nullable|string',
            'details' => 'nullable|string',
            'feature_img' => 'nullable|string',
            'counseling_mode' => 'required|in:online,offline,both',
            'session_duration' => 'integer|min:15|max:480',
            'total_sessions' => 'integer|min:1',
            'allow_reschedule' => 'boolean',
            'status' => 'required|in:active,inactive,suspended',
        ]);

        $counselingInfo = CounselingInfo::create($validated);
        return response()->json($counselingInfo, 201);
    }

    /**
     * Get specific counseling info
     *
     * @OA\Get(
     *     path="/counseling-infos/{id}",
     *     summary="Get specific counseling service info",
     *     description="Retrieve detailed information about a specific counseling service",
     *     operationId="getCounselingInfo",
     *     tags={"Counseling Info"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Counseling info ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Counseling info retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Career Counseling"),
     *             @OA\Property(property="product", type="object"),
     *             @OA\Property(property="counselors", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="appointments", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Counseling info not found"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function show($id)
    {
        $counselingInfo = CounselingInfo::with(['product', 'counselors', 'appointments'])
            ->findOrFail($id);
        return response()->json($counselingInfo);
    }

    /**
     * Update counseling info
     *
     * @OA\Put(
     *     path="/counseling-infos/{id}",
     *     summary="Update counseling service info",
     *     description="Update an existing counseling service information",
     *     operationId="updateCounselingInfo",
     *     tags={"Counseling Info"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Counseling info ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"product_id", "name", "code", "counseling_mode", "status"},
     *             @OA\Property(property="product_id", type="integer", example=5),
     *             @OA\Property(property="name", type="string", maxLength=255, example="Updated Career Counseling"),
     *             @OA\Property(property="code", type="string", maxLength=255, example="COUNSEL-001"),
     *             @OA\Property(property="description", type="string", example="Updated description"),
     *             @OA\Property(property="counseling_mode", type="string", enum={"online", "offline", "both"}, example="both"),
     *             @OA\Property(property="status", type="string", enum={"active", "inactive", "suspended"}, example="active")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Counseling info updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Updated Career Counseling")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Counseling info not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function update(Request $request, $id)
    {
        $counselingInfo = CounselingInfo::findOrFail($id);

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:counseling_infos,code,' . $id,
            'description' => 'nullable|string',
            'details' => 'nullable|string',
            'feature_img' => 'nullable|string',
            'counseling_mode' => 'required|in:online,offline,both',
            'session_duration' => 'integer|min:15|max:480',
            'total_sessions' => 'integer|min:1',
            'allow_reschedule' => 'boolean',
            'status' => 'required|in:active,inactive,suspended',
        ]);

        $counselingInfo->update($validated);
        return response()->json($counselingInfo);
    }

    /**
     * Delete counseling info
     *
     * @OA\Delete(
     *     path="/counseling-infos/{id}",
     *     summary="Delete counseling service info",
     *     description="Remove a counseling service from the system",
     *     operationId="deleteCounselingInfo",
     *     tags={"Counseling Info"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Counseling info ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Counseling info deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Counseling info deleted successfully.")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Counseling info not found"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function destroy($id)
    {
        $counselingInfo = CounselingInfo::findOrFail($id);
        $counselingInfo->delete();
        return response()->json(['message' => 'Counseling info deleted successfully.']);
    }

    /**
     * Assign counselor to counseling service
     *
     * @OA\Post(
     *     path="/counseling-infos/{id}/counselors",
     *     summary="Assign a counselor to counseling service",
     *     description="Add a counselor to a counseling service and notify previous students",
     *     operationId="assignCounselor",
     *     tags={"Counseling Info"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Counseling info ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"counselor_id"},
     *             @OA\Property(property="counselor_id", type="integer", example=3),
     *             @OA\Property(property="is_primary", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Counselor assigned successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Counselor assigned successfully.")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Counseling info not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function assignCounselor(Request $request, $id)
    {
        $counselingInfo = CounselingInfo::findOrFail($id);

        $validated = $request->validate([
            'counselor_id' => 'required|exists:members,id',
            'is_primary' => 'boolean'
        ]);

        // 檢查是否已經指派過該諮商師
        $alreadyAssigned = $counselingInfo->counselors()
            ->where('counselor_id', $validated['counselor_id'])
            ->exists();

        $counselingInfo->counselors()->attach($validated['counselor_id'], [
            'is_primary' => $validated['is_primary'] ?? false
        ]);

        // 如果是新指派的諮商師，發送新服務通知給曾經預約該諮商師的用戶
        if (!$alreadyAssigned) {
            $this->notificationService->createCounselorNewServiceNotifications(
                $validated['counselor_id'],
                $id
            );
        }

        return response()->json(['message' => 'Counselor assigned successfully.']);
    }

    /**
     * Remove counselor from counseling service
     *
     * @OA\Delete(
     *     path="/counseling-infos/{id}/counselors",
     *     summary="Remove counselor from counseling service",
     *     description="Remove a counselor assignment from a counseling service",
     *     operationId="removeCounselor",
     *     tags={"Counseling Info"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Counseling info ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"counselor_id"},
     *             @OA\Property(property="counselor_id", type="integer", example=3)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Counselor removed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Counselor removed successfully.")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Counseling info not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function removeCounselor(Request $request, $id)
    {
        $counselingInfo = CounselingInfo::findOrFail($id);

        $validated = $request->validate([
            'counselor_id' => 'required|exists:members,id'
        ]);

        $counselingInfo->counselors()->detach($validated['counselor_id']);

        return response()->json(['message' => 'Counselor removed successfully.']);
    }
}
