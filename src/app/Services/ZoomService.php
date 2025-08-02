<?php

namespace App\Services;

use App\Models\ClubCourse;
use App\Models\ClubCourseInfo;
use App\Models\ZoomCredential;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ZoomService
{
    protected $baseUrl;
    protected $accessTokens = []; // 儲存多個帳號的 access token

    public function __construct()
    {
        $this->baseUrl = config('zoom.base_url');
    }

    /**
     * 獲取指定憑證的 Access Token
     */
    protected function getAccessToken(ZoomCredential $credential): string
    {
        $credentialId = $credential->id;
        
        if (isset($this->accessTokens[$credentialId])) {
            return $this->accessTokens[$credentialId];
        }

        try {
            $response = Http::withBasicAuth($credential->client_id, $credential->client_secret)
                ->asForm()
                ->post('https://zoom.us/oauth/token', [
                    'grant_type' => 'account_credentials',
                    'account_id' => $credential->account_id,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $this->accessTokens[$credentialId] = $data['access_token'];
                return $this->accessTokens[$credentialId];
            }

            throw new \Exception('Failed to get access token: ' . $response->body());

        } catch (\Exception $e) {
            throw new \Exception('Access token request failed: ' . $e->getMessage());
        }
    }

    /**
     * 選擇可用的 Zoom 憑證
     */
    protected function selectAvailableCredential(): ?ZoomCredential
    {
        // 優先選擇使用率最低的可用帳號
        return ZoomCredential::available()
            ->orderBy('current_meetings', 'asc')
            ->orderBy('last_used_at', 'asc')
            ->first();
    }

    /**
     * 為課程創建 Zoom 會議
     */
    public function createMeetingForCourse(ClubCourse $course, array $options = []): array
    {
        try {
            // 選擇可用的 Zoom 憑證
            $credential = $this->selectAvailableCredential();
            
            if (!$credential) {
                return [
                    'success' => false,
                    'message' => '目前沒有可用的 Zoom 帳號，請稍後再試'
                ];
            }

            $courseInfo = $course->courseInfo;
            
            // 準備會議資料
            $meetingData = $this->prepareMeetingData($course, $courseInfo, $options);
            
            // 調用 Zoom API 創建會議
            $response = $this->createZoomMeeting($meetingData, $credential);
            
            if ($response['success']) {
                // 自動更新課程連結
                $this->updateCourseLink($course, $response['data'], $credential);
                
                return [
                    'success' => true,
                    'data' => array_merge($response['data'], [
                        'credential_name' => $credential->name,
                        'credential_email' => $credential->email,
                    ]),
                    'message' => "會議創建成功並已自動更新課程連結（使用帳號：{$credential->name}）"
                ];
            }
            
            return $response;
            
        } catch (\Exception $e) {
            Log::error('Zoom 會議創建失敗', [
                'course_id' => $course->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => '會議創建失敗: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 批量為課程系列創建會議
     */
    public function createMeetingsForCourseInfo(ClubCourseInfo $courseInfo, array $options = []): array
    {
        try {
            $courses = $courseInfo->clubCourses;
            $results = [];
            
            foreach ($courses as $course) {
                $result = $this->createMeetingForCourse($course, $options);
                $results[] = [
                    'course_id' => $course->id,
                    'result' => $result
                ];
            }
            
            return [
                'success' => true,
                'data' => $results,
                'message' => '批量創建會議完成'
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '批量創建失敗: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 創建 Zoom 會議
     */
    protected function createZoomMeeting(array $data, ZoomCredential $credential): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->getAccessToken($credential),
                'Content-Type' => 'application/json'
            ])->post($this->baseUrl . '/users/me/meetings', $data);

            if ($response->successful()) {
                $meetingData = $response->json();
                
                return [
                    'success' => true,
                    'data' => [
                        'id' => $meetingData['id'],
                        'uuid' => $meetingData['uuid'],
                        'host_id' => $meetingData['host_id'],
                        'topic' => $meetingData['topic'],
                        'type' => $meetingData['type'],
                        'start_time' => $meetingData['start_time'],
                        'duration' => $meetingData['duration'],
                        'timezone' => $meetingData['timezone'],
                        'agenda' => $meetingData['agenda'],
                        'created_at' => $meetingData['created_at'],
                        'join_url' => $meetingData['join_url'],
                        'start_url' => $meetingData['start_url'],
                        'password' => $meetingData['password'] ?? null,
                        'h323_password' => $meetingData['h323_password'] ?? null,
                        'pstn_password' => $meetingData['pstn_password'] ?? null,
                        'encrypted_password' => $meetingData['encrypted_password'] ?? null,
                        'settings' => $meetingData['settings'] ?? null,
                    ]
                ];
            }

            return [
                'success' => false,
                'message' => 'Zoom API 錯誤: ' . $response->body(),
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'API 調用失敗: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 準備會議資料
     */
    protected function prepareMeetingData(ClubCourse $course, ClubCourseInfo $courseInfo, array $options = []): array
    {
        $startTime = Carbon::parse($course->start_time);
        $endTime = Carbon::parse($course->end_time);
        $duration = $startTime->diffInMinutes($endTime);
        
        // 生成會議密碼
        $password = $this->generateMeetingPassword();
        
        // 會議主題
        $topic = $options['topic'] ?? $this->generateMeetingTopic($course, $courseInfo);
        
        // 會議議程
        $agenda = $options['agenda'] ?? $this->generateMeetingAgenda($course, $courseInfo);
        
        return [
            'topic' => $topic,
            'type' => config('zoom.meeting_types.scheduled'), // 預定會議
            'start_time' => $startTime->format('Y-m-d\TH:i:s\Z'),
            'duration' => $duration,
            'timezone' => config('zoom.timezone'),
            'agenda' => $agenda,
            'password' => $password,
            'settings' => array_merge(config('zoom.default_settings'), $options['settings'] ?? [])
        ];
    }

    /**
     * 生成會議主題
     */
    protected function generateMeetingTopic(ClubCourse $course, ClubCourseInfo $courseInfo): string
    {
        $sessionNumber = $course->sort;
        $isTrialText = $course->trial ? '【試聽】' : '';
        
        return "{$isTrialText}{$courseInfo->name} - 第{$sessionNumber}堂課";
    }

    /**
     * 生成會議議程
     */
    protected function generateMeetingAgenda(ClubCourse $course, ClubCourseInfo $courseInfo): string
    {
        $startTime = Carbon::parse($course->start_time)->format('H:i');
        $endTime = Carbon::parse($course->end_time)->format('H:i');
        
        return "課程：{$courseInfo->name}\n" .
               "時間：{$startTime} - {$endTime}\n" .
               "第{$course->sort}堂課\n" .
               ($course->trial ? "※ 本堂課為試聽課程" : "");
    }

    /**
     * 生成會議密碼
     */
    protected function generateMeetingPassword(): string
    {
        $config = config('zoom.password');
        $length = $config['length'];
        $includeNumbers = $config['include_numbers'];
        $includeLetters = $config['include_letters'];
        
        $characters = '';
        if ($includeNumbers) {
            $characters .= '0123456789';
        }
        if ($includeLetters) {
            $characters .= 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        }
        
        return substr(str_shuffle($characters), 0, $length);
    }

    /**
     * 更新課程連結
     */
    protected function updateCourseLink(ClubCourse $course, array $meetingData, ZoomCredential $credential): void
    {
        // 更新課程表的 link 欄位（暫時保留）
        $course->update([
            'link' => $meetingData['join_url'],
        ]);

        // 創建或更新 Zoom 會議詳情
        $course->zoomMeetDetail()->updateOrCreate(
            ['club_course_id' => $course->id],
            [
                'zoom_credential_id' => $credential->id,
                'zoom_meeting_id' => $meetingData['id'],
                'zoom_meeting_uuid' => $meetingData['uuid'],
                'host_id' => $meetingData['host_id'],
                'topic' => $meetingData['topic'],
                'type' => $meetingData['type'],
                'start_time' => $meetingData['start_time'],
                'duration' => $meetingData['duration'],
                'timezone' => $meetingData['timezone'],
                'agenda' => $meetingData['agenda'],
                'password' => $meetingData['password'],
                'h323_password' => $meetingData['h323_password'] ?? null,
                'pstn_password' => $meetingData['pstn_password'] ?? null,
                'encrypted_password' => $meetingData['encrypted_password'] ?? null,
                'join_url' => $meetingData['join_url'],
                'start_url' => $meetingData['start_url'],
                'link' => $meetingData['join_url'], // 暫時保留
                'settings' => $meetingData['settings'] ?? null,
                'status' => 'active',
                'zoom_created_at' => $meetingData['created_at'],
            ]
        );

        // 增加憑證的會議計數
        $credential->incrementMeetings();
    }

    /**
     * 刪除 Zoom 會議
     */
    public function deleteMeeting(ClubCourse $course): array
    {
        try {
            $zoomDetail = $course->zoomMeetDetail;
            
            if (!$zoomDetail) {
                return [
                    'success' => false,
                    'message' => '此課程尚未創建 Zoom 會議'
                ];
            }

            $credential = $zoomDetail->zoomCredential;
            
            if (!$credential) {
                return [
                    'success' => false,
                    'message' => 'Zoom 憑證不存在'
                ];
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->getAccessToken($credential),
            ])->delete($this->baseUrl . '/meetings/' . $zoomDetail->zoom_meeting_id);

            if ($response->successful()) {
                // 減少憑證的會議計數
                $credential->decrementMeetings();
                
                // 刪除本地 Zoom 會議記錄
                $zoomDetail->delete();
                
                // 清除課程連結
                $course->update(['link' => null]);
                
                return [
                    'success' => true,
                    'message' => '會議刪除成功'
                ];
            }

            return [
                'success' => false,
                'message' => 'Zoom API 錯誤: ' . $response->body()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '刪除失敗: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 更新 Zoom 會議
     */
    public function updateMeeting(string $meetingId, array $data): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
                'Content-Type' => 'application/json'
            ])->patch($this->baseUrl . '/meetings/' . $meetingId, $data);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => '會議更新成功'
                ];
            }

            return [
                'success' => false,
                'message' => 'Zoom API 錯誤: ' . $response->body()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '更新失敗: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 獲取會議資訊
     */
    public function getMeetingInfo(string $meetingId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
            ])->get($this->baseUrl . '/meetings/' . $meetingId);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => 'Zoom API 錯誤: ' . $response->body()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '獲取失敗: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 獲取會議參與者列表
     */
    public function getMeetingParticipants(string $meetingId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
            ])->get($this->baseUrl . '/meetings/' . $meetingId . '/participants');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => 'Zoom API 錯誤: ' . $response->body()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '獲取失敗: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 檢查 Zoom API 連接狀態
     */
    public function checkConnection(): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
            ])->get($this->baseUrl . '/users/me');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Zoom API 連接正常',
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => 'Zoom API 連接失敗: ' . $response->body()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '連接檢查失敗: ' . $e->getMessage()
            ];
        }
    }
}