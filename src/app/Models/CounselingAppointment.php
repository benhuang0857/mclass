<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Commentable;

class CounselingAppointment extends Model
{
    use HasFactory, Commentable;

    protected $table = 'counseling_appointments';

    protected $fillable = [
        'order_item_id',
        'flip_course_case_id',
        'counseling_info_id',
        'student_id',
        'counselor_id',
        'title',
        'description',
        'status',
        'type',
        'preferred_datetime',
        'confirmed_datetime',
        'duration',
        'method',
        'location',
        'meeting_url',
        'counselor_notes',
        'student_feedback',
        'rating',
        'is_urgent',
    ];

    protected $casts = [
        'preferred_datetime' => 'datetime',
        'confirmed_datetime' => 'datetime',
        'is_urgent' => 'boolean',
        'rating' => 'integer',
        'duration' => 'integer',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }

    public function counselingInfo()
    {
        return $this->belongsTo(CounselingInfo::class, 'counseling_info_id');
    }

    public function student()
    {
        return $this->belongsTo(Member::class, 'student_id');
    }

    public function counselor()
    {
        return $this->belongsTo(Member::class, 'counselor_id');
    }

    /**
     * 關聯的翻轉課程案例（如果是翻轉課程諮商）
     */
    public function flipCourseCase()
    {
        return $this->belongsTo(FlipCourseCase::class);
    }

    /**
     * 關聯的處方簽（翻轉課程中，諮商會議可能對應處方簽）
     */
    public function prescription()
    {
        return $this->hasOne(Prescription::class);
    }

    /**
     * 檢查是否為翻轉課程諮商
     */
    public function isFlipCourseCounseling(): bool
    {
        return !is_null($this->flip_course_case_id);
    }

    /**
     * 檢查是否為一般諮商
     */
    public function isRegularCounseling(): bool
    {
        return !is_null($this->order_item_id);
    }

    /**
     * 範圍查詢：翻轉課程諮商
     */
    public function scopeFlipCourse($query)
    {
        return $query->whereNotNull('flip_course_case_id');
    }

    /**
     * 範圍查詢：一般諮商
     */
    public function scopeRegular($query)
    {
        return $query->whereNotNull('order_item_id');
    }
}
