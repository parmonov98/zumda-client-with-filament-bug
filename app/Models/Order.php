<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'order_id',
        'driver_id',
        'client_id',
        'restaurant_id',
        'summary',
        'distance',
        'per_km_price',
        'shipping_price',
        'status',
        'payment_type',
        'phone_number',
        'address',
        'longitude',
        'latitude',
        'customer_note',
        'is_sent_to_drivers',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function bot_messsages()
    {
        return $this->hasMany(OrderBotMessages::class);
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function operator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function restaurant_operator()
    {
        return $this->belongsTo(User::class, 'restaurant_operator_id');
    }
    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class, 'restaurant_id');
    }
}
