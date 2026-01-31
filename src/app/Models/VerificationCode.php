<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class VerificationCode extends Model
{
    use HasFactory;

    protected $table = 'verification_codes';

    protected $fillable = [
        'member_id',
        'type',
        'target',
        'code',
        'expires_at',
        'verified_at',
        'attempts',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
        'attempts' => 'integer',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    const MAX_ATTEMPTS = 5;
    const EXPIRE_MINUTES = 10;
    const RESEND_COOLDOWN_SECONDS = 60;

    /**
     * Relation to Member (optional)
     */
    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Scope: valid (not expired and not verified)
     */
    public function scopeValid($query)
    {
        return $query->where('expires_at', '>', Carbon::now())
                     ->whereNull('verified_at')
                     ->where('attempts', '<', self::MAX_ATTEMPTS);
    }

    /**
     * Scope: for specific target (email or mobile)
     */
    public function scopeForTarget($query, string $target)
    {
        return $query->where('target', $target);
    }

    /**
     * Scope: for specific type
     */
    public function scopeForType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: pending (not verified)
     */
    public function scopePending($query)
    {
        return $query->whereNull('verified_at');
    }

    /**
     * Scope: recent (created within cooldown period)
     */
    public function scopeRecent($query)
    {
        return $query->where('created_at', '>', Carbon::now()->subSeconds(self::RESEND_COOLDOWN_SECONDS));
    }

    /**
     * Check if the code is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if the code has been verified
     */
    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    /**
     * Check if max attempts exceeded
     */
    public function isMaxAttemptsExceeded(): bool
    {
        return $this->attempts >= self::MAX_ATTEMPTS;
    }

    /**
     * Mark the code as verified
     */
    public function markAsVerified(): bool
    {
        $this->verified_at = Carbon::now();
        return $this->save();
    }

    /**
     * Increment the attempts counter
     */
    public function incrementAttempts(): bool
    {
        $this->attempts++;
        return $this->save();
    }

    /**
     * Check if code is still usable
     */
    public function isUsable(): bool
    {
        return !$this->isExpired() && !$this->isVerified() && !$this->isMaxAttemptsExceeded();
    }
}
