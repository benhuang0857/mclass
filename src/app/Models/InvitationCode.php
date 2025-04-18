<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvitationCode extends Model
{
    use HasFactory;

    protected $table = 'invitation_codes';

    protected $fillable = [
        'code',
        'from_member_id',
        'to_member_id',
        'email',
        'expired',
        'status',
    ];

    protected $casts = [
        'expired' => 'datetime',
        'status' => 'boolean',
    ];

    public function fromMember()
    {
        return $this->belongsTo(Member::class, 'from_member_id');
    }

    public function toMember()
    {
        return $this->belongsTo(Member::class, 'to_member_id');
    }
}
