<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FollowerClubCourseInfo extends Model
{
    protected $table = 'follower_club_course_info';

    protected $fillable = [
        'member_id',
        'product_id',
    ];
}
