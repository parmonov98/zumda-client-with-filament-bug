<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Partner extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'username',
        'telegram_id',
        'activation_code',
        'activation_code_used',
        'temp_client_id',
        'status'
    ];

    public function restaurant(){
        return $this->hasOne(Restaurant::class);
    }
}
