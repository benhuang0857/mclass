<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Commentable;

class ClubCourse extends Model
{
    use HasFactory, Commentable;

    protected $table = 'club_courses';

    protected $fillable = [
        'course_id',
        'start_time',
        'end_time',
        'link',
        'location',
        'trial',
        'sort',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'trial' => 'boolean',
    ];

    public function courseInfo()
    {
        return $this->belongsTo(ClubCourseInfo::class, 'course_id');
    }

    public function courseStatusTypes()
    {
        return $this->belongsToMany(CourseStatusType::class, 'course_status_type_club_course', 'club_course_id', 'course_status_type_id');
    }
}
