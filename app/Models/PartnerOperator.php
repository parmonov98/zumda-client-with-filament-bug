<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;

class PartnerOperator extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'username',
        'phone_number',
        'restaurant_id',
        'telegram_id',
        'activation_code',
        'activation_code_used',
        'temp_client_id',
        'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'partner_operator_id', 'id');
    }

    public function restaurant(){
        return $this->belongsTo(Restaurant::class);
    }
}
