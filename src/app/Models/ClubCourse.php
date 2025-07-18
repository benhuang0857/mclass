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

    /**
     * 關聯到 Zoom 會議詳情
     */
    public function zoomMeetDetail()
    {
        return $this->hasOne(ZoomMeetDetail::class, 'club_course_id');
    }

    /**
     * 檢查是否有 Zoom 會議
     */
    public function hasZoomMeeting(): bool
    {
        return $this->zoomMeetDetail !== null;
    }

    /**
     * 獲取 Zoom 會議加入連結
     */
    public function getZoomJoinUrl(): ?string
    {
        return $this->zoomMeetDetail?->join_url ?? $this->link;
    }

    /**
     * 獲取 Zoom 會議開始連結（主持人用）
     */
    public function getZoomStartUrl(): ?string
    {
        return $this->zoomMeetDetail?->start_url;
    }

    /**
     * 檢查是否可以加入 Zoom 會議
     */
    public function canJoinZoomMeeting(): bool
    {
        return $this->zoomMeetDetail?->canJoinNow() ?? false;
    }
}
