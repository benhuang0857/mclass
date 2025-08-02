<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Zoom API Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for Zoom API integration.
    | You need to create a Zoom App and get the credentials from:
    | https://marketplace.zoom.us/develop/create
    |
    */

    'base_url' => env('ZOOM_BASE_URL', 'https://api.zoom.us/v2'),
    
    // 注意：認證資訊現在儲存在 zoom_credentials 資料表中
    // 以下設定已棄用，保留僅供向後相容
    
    // 預設會議設定
    'default_settings' => [
        'host_video' => true,
        'participant_video' => true,
        'join_before_host' => false,
        'mute_upon_entry' => true,
        'watermark' => false,
        'use_pmi' => false,
        'approval_type' => 0, // 0=自動同意, 1=手動同意, 2=無需註冊
        'audio' => 'voip', // both, telephony, voip
        'auto_recording' => 'none', // local, cloud, none
        'enforce_login' => false,
        'waiting_room' => true,
        'allow_multiple_devices' => true,
    ],
    
    // 會議類型
    'meeting_types' => [
        'instant' => 1,      // 即時會議
        'scheduled' => 2,    // 預定會議
        'recurring_no_time' => 3,  // 無固定時間的定期會議
        'recurring_fixed_time' => 8, // 固定時間的定期會議
    ],
    
    // 時區設定
    'timezone' => env('ZOOM_TIMEZONE', 'Asia/Taipei'),
    
    // 會議密碼設定
    'password' => [
        'length' => 6,
        'require_password' => true,
        'include_numbers' => true,
        'include_letters' => true,
    ],
];