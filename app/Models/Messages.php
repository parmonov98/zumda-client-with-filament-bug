<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Messages extends Model
{

    protected $fillable = [
        'text',
        'entities',
        'type',
        'content'
    ];
    use HasFactory;
}