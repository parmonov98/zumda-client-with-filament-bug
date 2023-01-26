<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoryTranslation extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'lang',
        'name',
        'description'
    ];

    public function category(){
        return $this->belongsTo(Category::class);
    }
}
