<?php

namespace App\Http\Controllers;

use App\Models\ZoomCredential;
use App\Services\ZoomService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class ZoomCredentialController extends Controller
{
    protected $zoomService;

    public function __construct(ZoomService $zoomService)
    {
        $this->zoomService = $zoomService;
    }

    /**
     * @OA\Get(
     *     path="/zoom/credentials",
     *     tags={"Zoom Credentials"},
     *     summary="Get all Zoom credentials",
     *     description="Retrieve list of all Zoom API credentials",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to retrieve credentials"
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        try {
            $credentials = ZoomCredential::select([
                'id', 'name', 'account_id', 'client_id', 'email', 
                'is_active', 'max_concurrent_meetings', 'current_meetings', 
                'last_used_at', 'created_at', 'updated_at'
            ])->get();

            $credentials->each(function ($credential) {
                $credential->usage_rate = $credential->usage_rate;
            });

            return response()->json([
                'success' => true,
                'data' => $credentials
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '獲取憑證列表失敗',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/zoom/credentials",
     *     tags={"Zoom Credentials"},
     *     summary="Create new Zoom credential",
     *     description="Create a new Zoom API credential",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "account_id", "client_id", "client_secret"},
     *             @OA\Property(property="name", type="string", maxLength=255, description="Credential name"),
     *             @OA\Property(property="account_id", type="string", description="Zoom account ID"),
     *             @OA\Property(property="client_id", type="string", description="Zoom client ID"),
     *             @OA\Property(property="client_secret", type="string", description="Zoom client secret"),
     *             @OA\Property(property="email", type="string", format="email", maxLength=255, description="Account email"),
     *             @OA\Property(property="is_active", type="boolean", description="Whether credential is active"),
     *             @OA\Property(property="max_concurrent_meetings", type="integer", minimum=1, maximum=10, description="Max concurrent meetings"),
     *             @OA\Property(property="settings", type="object", description="Additional settings")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Credential created successfully"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to create credential"
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'account_id' => 'required|string|unique:zoom_credentials,account_id',
            'client_id' => 'required|string',
            'client_secret' => 'required|string',
            'email' => 'nullable|email|max:255',
            'is_active' => 'boolean',
            'max_concurrent_meetings' => 'integer|min:1|max:10',
            'settings' => 'nullable|array',
        ]);

        try {
            $credential = ZoomCredential::create($validated);

            return response()->json([
                'success' => true,
                'data' => $credential->makeHidden(['client_secret']),
                'message' => 'Zoom 憑證創建成功'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '創建憑證失敗',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/zoom/credentials/{zoomCredential}",
     *     tags={"Zoom Credentials"},
     *     summary="Get a specific Zoom credential",
     *     description="Retrieve details of a specific Zoom credential",
     *     @OA\Parameter(
     *         name="zoomCredential",
     *         in="path",
     *         description="Zoom Credential ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Credential not found"
     *     )
     * )
     */
    public function show(ZoomCredential $zoomCredential): JsonResponse
    {
        try {
            $credential = $zoomCredential->load('zoomMeetDetails.clubCourse.courseInfo');
            $credential->usage_rate = $credential->usage_rate;

            return response()->json([
                'success' => true,
                'data' => $credential->makeHidden(['client_secret'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '獲取憑證詳情失敗',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/zoom/credentials/{zoomCredential}",
     *     tags={"Zoom Credentials"},
     *     summary="Update a Zoom credential",
     *     description="Update an existing Zoom credential",
     *     @OA\Parameter(
     *         name="zoomCredential",
     *         in="path",
     *         description="Zoom Credential ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", maxLength=255),
     *             @OA\Property(property="account_id", type="string"),
     *             @OA\Property(property="client_id", type="string"),
     *             @OA\Property(property="client_secret", type="string"),
     *             @OA\Property(property="email", type="string", format="email", maxLength=255),
     *             @OA\Property(property="is_active", type="boolean"),
     *             @OA\Property(property="max_concurrent_meetings", type="integer", minimum=1, maximum=10),
     *             @OA\Property(property="settings", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Credential updated successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Credential not found"
     *     )
     * )
     */
    public function update(Request $request, ZoomCredential $zoomCredential): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'account_id' => [
                'sometimes',
                'required',
                'string',
                Rule::unique('zoom_credentials', 'account_id')->ignore($zoomCredential->id)
            ],
            'client_id' => 'sometimes|required|string',
            'client_secret' => 'sometimes|required|string',
            'email' => 'nullable|email|max:255',
            'is_active' => 'boolean',
            'max_concurrent_meetings' => 'integer|min:1|max:10',
            'settings' => 'nullable|array',
        ]);

        try {
            $zoomCredential->update($validated);

            return response()->json([
                'success' => true,
                'data' => $zoomCredential->makeHidden(['client_secret']),
                'message' => 'Zoom 憑證更新成功'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '更新憑證失敗',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/zoom/credentials/{zoomCredential}",
     *     tags={"Zoom Credentials"},
     *     summary="Delete a Zoom credential",
     *     description="Delete a Zoom credential (only if no active meetings)",
     *     @OA\Parameter(
     *         name="zoomCredential",
     *         in="path",
     *         description="Zoom Credential ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Credential deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Cannot delete - active meetings exist"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Credential not found"
     *     )
     * )
     */
    public function destroy(ZoomCredential $zoomCredential): JsonResponse
    {
        try {
            // 檢查是否有進行中的會議
            if ($zoomCredential->current_meetings > 0) {
                return response()->json([
                    'success' => false,
                    'message' => '無法刪除：此憑證仍有進行中的會議'
                ], 400);
            }

            $zoomCredential->delete();

            return response()->json([
                'success' => true,
                'message' => 'Zoom 憑證刪除成功'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '刪除憑證失敗',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/zoom/credentials/{zoomCredential}/test",
     *     tags={"Zoom Credentials"},
     *     summary="Test Zoom credential connection",
     *     description="Test if the Zoom credential can connect to Zoom API",
     *     @OA\Parameter(
     *         name="zoomCredential",
     *         in="path",
     *         description="Zoom Credential ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Connection test result"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Connection test failed"
     *     )
     * )
     */
    public function testConnection(ZoomCredential $zoomCredential): JsonResponse
    {
        try {
            // 暫時創建一個 ZoomService 實例來測試連接
            $result = $this->testCredentialConnection($zoomCredential);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '測試連接失敗',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 測試憑證連接
     */
    private function testCredentialConnection(ZoomCredential $credential): array
    {
        try {
            $response = \Illuminate\Support\Facades\Http::withBasicAuth($credential->client_id, $credential->client_secret)
                ->asForm()
                ->post('https://zoom.us/oauth/token', [
                    'grant_type' => 'account_credentials',
                    'account_id' => $credential->account_id,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $accessToken = $data['access_token'];

                // 測試 API 調用
                $userResponse = \Illuminate\Support\Facades\Http::withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                ])->get(config('zoom.base_url') . '/users/me');

                if ($userResponse->successful()) {
                    $userInfo = $userResponse->json();
                    
                    return [
                        'success' => true,
                        'message' => 'Zoom API 連接成功',
                        'data' => [
                            'user_info' => [
                                'email' => $userInfo['email'] ?? null,
                                'first_name' => $userInfo['first_name'] ?? null,
                                'last_name' => $userInfo['last_name'] ?? null,
                                'account_id' => $userInfo['account_id'] ?? null,
                            ]
                        ]
                    ];
                }
            }

            return [
                'success' => false,
                'message' => 'Zoom API 連接失敗',
                'error' => $response->body()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '連接測試失敗',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * @OA\Post(
     *     path="/zoom/credentials/{zoomCredential}/reset-count",
     *     tags={"Zoom Credentials"},
     *     summary="Reset meeting count",
     *     description="Reset the current meeting count for a credential",
     *     @OA\Parameter(
     *         name="zoomCredential",
     *         in="path",
     *         description="Zoom Credential ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Meeting count reset successfully"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to reset count"
     *     )
     * )
     */
    public function resetMeetingCount(ZoomCredential $zoomCredential): JsonResponse
    {
        try {
            $zoomCredential->update(['current_meetings' => 0]);

            return response()->json([
                'success' => true,
                'message' => '會議計數已重置',
                'data' => $zoomCredential->makeHidden(['client_secret'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '重置失敗',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}