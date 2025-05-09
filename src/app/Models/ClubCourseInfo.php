<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClubCourseInfo extends Model
{
    use HasFactory;

    protected $table = 'club_course_infos';

    protected $fillable = [
        'name',
        'code',
        'description',
        'details',
        'feature_img',
        'teaching_mode',
        'schedule_display',
        'is_periodic',
        'elective',
        'max_enrollment',
        'total_sessions',
        'regular_price',
        'discount_price',
        'allow_replay',
        'is_series',
        'status',
        'is_visible_to_specific_students',
    ];

    protected $casts = [
        'is_periodic' => 'boolean',
        'elective' => 'boolean',
        'allow_replay' => 'boolean',
        'is_series' => 'boolean',
        'is_visible_to_specific_students' => 'boolean',
        'teaching_mode' => 'string',
        'status' => 'string',
    ];

    public function schedules()
    {
        return $this->hasMany(ClubCourseInfoSchedule::class, 'course_id');
    }

    public function languages()
    {
        return $this->belongsToMany(LangType::class, 'lang_type_club_course_info', 'club_course_info_id', 'lang_type_id');
    }

    public function levels()
    {
        return $this->belongsToMany(LevelType::class, 'level_type_club_course_info', 'club_course_info_id', 'level_type_id');
    }

    public function courseInfoTypes()
    {
        return $this->belongsToMany(CourseInfoType::class, 'course_info_type_club_course_info', 'club_course_info_id', 'course_info_type_id');
    }

    public function teachMethods()
    {
        return $this->belongsToMany(TeachMethodType::class, 'teach_method_type_club_course_info', 'club_course_info_id', 'teach_method_type_id');
    }

    public function sysmans()
    {
        return $this->belongsToMany(User::class, 'sysman_club_course_info', 'club_course_info_id', 'user_id');
    }

    public function teachers()
    {
        return $this->belongsToMany(Member::class, 'teacher_club_course_info', 'club_course_info_id', 'member_id');
    }

    public function assistants()
    {
        return $this->belongsToMany(Member::class, 'assistant_club_course_info', 'club_course_info_id', 'member_id');
    }

    // 課程實例
    public function clubCourses()
    {
        return $this->hasMany(ClubCourse::class, 'course_id');
    }
}
