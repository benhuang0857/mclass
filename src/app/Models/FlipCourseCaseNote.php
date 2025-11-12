<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlipCourseCaseNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'flip_course_case_id',
        'member_id',
        'note_type',
        'content',
        'attachments',
    ];

    protected $casts = [
        'attachments' => 'array',
        'note_type' => 'string',
    ];

    /**
     * 所屬案例
     */
    public function flipCourseCase(): BelongsTo
    {
        return $this->belongsTo(FlipCourseCase::class);
    }

    /**
     * 記錄者
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
