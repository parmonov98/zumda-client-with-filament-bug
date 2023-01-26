<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Operator extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'username',
        'phone_number',
        'telegram_id',
        'activation_code',
        'activation_code_used',
        'temp_client_id',
        'status'
    ];

    public function user(){
        return $this->hasOne(User::class);
    }
}
