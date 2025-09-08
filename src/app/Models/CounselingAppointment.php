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
        return $this->belongsTo(OrderIteam::class, 'order_item_id');
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
}
