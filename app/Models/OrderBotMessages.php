<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderBotMessages extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'sender_id',
        'message_id',
        'chat_id',
        'is_deleted'
    ];


    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
