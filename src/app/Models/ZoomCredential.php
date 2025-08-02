<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class ZoomCredential extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'account_id',
        'client_id',
        'client_secret',
        'email',
        'is_active',
        'max_concurrent_meetings',
        'current_meetings',
        'last_used_at',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'max_concurrent_meetings' => 'integer',
        'current_meetings' => 'integer',
        'last_used_at' => 'datetime',
        'settings' => 'array',
    ];

    protected $hidden = [
        'client_secret',
    ];

    /**
     * 自動加密 client_secret
     */
    public function setClientSecretAttribute($value)
    {
        $this->attributes['client_secret'] = Crypt::encryptString($value);
    }

    /**
     * 自動解密 client_secret
     */
    public function getClientSecretAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    /**
     * 檢查是否可以創建新會議
     */
    public function canCreateMeeting(): bool
    {
        return $this->is_active && $this->current_meetings < $this->max_concurrent_meetings;
    }

    /**
     * 增加會議計數
     */
    public function incrementMeetings(): void
    {
        $this->increment('current_meetings');
        $this->update(['last_used_at' => now()]);
    }

    /**
     * 減少會議計數
     */
    public function decrementMeetings(): void
    {
        $this->decrement('current_meetings');
    }

    /**
     * 查詢可用的帳號
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_active', true)
                    ->whereRaw('current_meetings < max_concurrent_meetings');
    }

    /**
     * 查詢啟用的帳號
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * 獲取使用率
     */
    public function getUsageRateAttribute(): float
    {
        return $this->max_concurrent_meetings > 0 
            ? ($this->current_meetings / $this->max_concurrent_meetings) * 100 
            : 0;
    }

    /**
     * 關聯到 Zoom 會議詳情
     */
    public function zoomMeetDetails()
    {
        return $this->hasMany(ZoomMeetDetail::class, 'zoom_credential_id');
    }
}
