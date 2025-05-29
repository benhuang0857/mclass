<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisiblerClubCourseInfo extends Model
{
    protected $table = 'visibler_club_course_info';

    protected $fillable = [
        'member_id',
        'product_id',
    ];
}
