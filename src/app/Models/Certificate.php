<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    use HasFactory;

    protected $table = 'certificates';

    protected $fillable = [
        'certificate_type_id',
        'member_id',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    /**
     * 關聯到證照類型
     */
    public function certificateType()
    {
        return $this->belongsTo(CertificateType::class, 'certificate_type_id');
    }

    /**
     * 關聯到會員
     */
    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
