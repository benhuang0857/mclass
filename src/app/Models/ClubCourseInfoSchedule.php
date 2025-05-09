<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClubCourseInfoSchedule extends Model
{
    use HasFactory;

    protected $table = 'club_course_info_schedule';

    protected $fillable = [
        'course_id',
        'start_date',
        'end_date',
        'day_of_week',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'day_of_week' => 'string',
    ];

    public function course()
    {
        return $this->belongsTo(ClubCourseInfo::class, 'course_id');
    }
}
