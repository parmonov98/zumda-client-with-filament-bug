<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommonCategoryTranslation extends Model
{
    use HasFactory;


    protected $fillable = [
        'lang',
        'name',
    ];

    public function common_category(){
        return $this->belongsTo(CommonCategory::class, 'common_category_id', 'id');
    }
}
