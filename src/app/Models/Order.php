<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';

    protected $fillable = [
        'member_id',
        'code',
        'total',
        'currency',
        'shipping_address',
        'billing_address',
        'note',
        'status',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderIteam::class, 'order_id');
    }
}
