<?php

namespace App\Services;

use App\Models\VerificationCode;
use App\Models\Member;
use App\Models\Contact;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class VerificationService
{
    /**
     * Generate a 6-digit verification code
     */
    public function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Check if a new code can be sent (60 second cooldown)
     */
    public function canSendCode(string $target, string $type): array
    {
        $recentCode = VerificationCode::forTarget($target)
            ->forType($type)
            ->recent()
            ->first();

        if ($recentCode) {
            $waitSeconds = VerificationCode::RESEND_COOLDOWN_SECONDS -
                Carbon::now()->diffInSeconds($recentCode->created_at);

            return [
                'can_send' => false,
                'wait_seconds' => max(0, $waitSeconds),
                'message' => "請等待 {$waitSeconds} 秒後再重新發送",
            ];
        }

        return [
            'can_send' => true,
            'wait_seconds' => 0,
            'message' => null,
        ];
    }

    /**
     * Send email verification code
     */
    public function sendEmailCode(string $email, ?int $memberId = null): array
    {
        return $this->sendCode($email, 'email', $memberId);
    }

    /**
     * Send mobile verification code
     */
    public function sendMobileCode(string $mobile, ?int $memberId = null): array
    {
        return $this->sendCode($mobile, 'mobile', $memberId);
    }

    /**
     * Send verification code (internal method)
     */
    protected function sendCode(string $target, string $type, ?int $memberId = null): array
    {
        // Check cooldown
        $canSend = $this->canSendCode($target, $type);
        if (!$canSend['can_send']) {
            return [
                'success' => false,
                'message' => $canSend['message'],
                'wait_seconds' => $canSend['wait_seconds'],
            ];
        }

        // Generate new code
        $code = $this->generateCode();
        $expiresAt = Carbon::now()->addMinutes(VerificationCode::EXPIRE_MINUTES);

        // Create verification code record
        $verificationCode = VerificationCode::create([
            'member_id' => $memberId,
            'type' => $type,
            'target' => $target,
            'code' => $code,
            'expires_at' => $expiresAt,
            'attempts' => 0,
        ]);

        // Simulate sending (log the code in development)
        $this->simulateSend($target, $type, $code);

        return [
            'success' => true,
            'message' => '驗證碼已發送',
            'expires_in' => VerificationCode::EXPIRE_MINUTES * 60,
            'expires_at' => $expiresAt->toIso8601String(),
        ];
    }

    /**
     * Simulate sending verification code (log to file)
     */
    protected function simulateSend(string $target, string $type, string $code): void
    {
        $typeLabel = $type === 'email' ? 'Email' : '手機';

        Log::info("=== 驗證碼發送 (模擬模式) ===", [
            'type' => $typeLabel,
            'target' => $target,
            'code' => $code,
            'expires_in' => VerificationCode::EXPIRE_MINUTES . ' 分鐘',
        ]);
    }

    /**
     * Verify the code
     */
    public function verifyCode(string $target, string $type, string $code): array
    {
        // Find valid verification code
        $verificationCode = VerificationCode::forTarget($target)
            ->forType($type)
            ->pending()
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$verificationCode) {
            return [
                'success' => false,
                'message' => '找不到驗證碼，請重新發送',
                'verified' => false,
            ];
        }

        // Check if expired
        if ($verificationCode->isExpired()) {
            return [
                'success' => false,
                'message' => '驗證碼已過期，請重新發送',
                'verified' => false,
            ];
        }

        // Check max attempts
        if ($verificationCode->isMaxAttemptsExceeded()) {
            return [
                'success' => false,
                'message' => '嘗試次數過多，請重新發送驗證碼',
                'verified' => false,
            ];
        }

        // Verify code
        if ($verificationCode->code !== $code) {
            $verificationCode->incrementAttempts();
            $remainingAttempts = VerificationCode::MAX_ATTEMPTS - $verificationCode->attempts;

            return [
                'success' => false,
                'message' => "驗證碼錯誤，剩餘 {$remainingAttempts} 次嘗試機會",
                'verified' => false,
                'remaining_attempts' => $remainingAttempts,
            ];
        }

        // Mark as verified
        $verificationCode->markAsVerified();

        // Update related records
        $this->updateValidationStatus($target, $type);

        return [
            'success' => true,
            'message' => '驗證成功',
            'verified' => true,
        ];
    }

    /**
     * Update email_valid or mobile_valid status
     */
    protected function updateValidationStatus(string $target, string $type): void
    {
        if ($type === 'email') {
            // Update Member's email_valid
            Member::where('email', $target)->update(['email_valid' => true]);
        } elseif ($type === 'mobile') {
            // Update Contact's mobile_valid
            Contact::where('mobile', $target)->update(['mobile_valid' => true]);
        }
    }

    /**
     * Check if target (email/mobile) has been verified recently
     * Used to validate before registration
     */
    public function isTargetVerified(string $target, string $type, int $withinMinutes = 30): bool
    {
        return VerificationCode::forTarget($target)
            ->forType($type)
            ->whereNotNull('verified_at')
            ->where('verified_at', '>', Carbon::now()->subMinutes($withinMinutes))
            ->exists();
    }

    /**
     * Get current verification code for a target (debug mode only)
     */
    public function getCode(string $target, string $type): ?array
    {
        $verificationCode = VerificationCode::forTarget($target)
            ->forType($type)
            ->valid()
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$verificationCode) {
            return null;
        }

        return [
            'code' => $verificationCode->code,
            'expires_at' => $verificationCode->expires_at->toIso8601String(),
            'remaining_attempts' => VerificationCode::MAX_ATTEMPTS - $verificationCode->attempts,
        ];
    }
}
