<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Commentable;

class CounselingInfo extends Model
{
    use HasFactory, Commentable;

    protected $table = 'counseling_infos';

    protected $fillable = [
        'product_id',
        'name',
        'code',
        'description',
        'details',
        'feature_img',
        'counseling_mode',
        'session_duration',
        'total_sessions',
        'allow_reschedule',
        'status',
    ];

    protected $casts = [
        'allow_reschedule' => 'boolean',
        'session_duration' => 'integer',
        'total_sessions' => 'integer',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function counselors()
    {
        return $this->belongsToMany(Member::class, 'counseling_info_counselors', 'counseling_info_id', 'counselor_id')
                    ->withPivot('is_primary')
                    ->withTimestamps();
    }

    public function appointments()
    {
        return $this->hasMany(CounselingAppointment::class, 'counseling_info_id');
    }
}
