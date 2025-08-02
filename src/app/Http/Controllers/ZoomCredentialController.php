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
     * 顯示所有 Zoom 憑證
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
     * 創建新的 Zoom 憑證
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
     * 顯示指定的 Zoom 憑證
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
     * 更新指定的 Zoom 憑證
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
     * 刪除指定的 Zoom 憑證
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
     * 測試 Zoom 憑證連接
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
     * 重置會議計數
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