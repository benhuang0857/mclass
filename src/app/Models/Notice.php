<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Commentable;

class Notice extends Model
{
    use HasFactory, Commentable;

    protected $table = 'notices';

    protected $fillable = [
        'title',
        'feature_img',
        'notice_type_id',
        'body',
        'status',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function noticeType()
    {
        return $this->belongsTo(NoticeType::class);
    }
}
