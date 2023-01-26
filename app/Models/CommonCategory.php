<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Znck\Eloquent\Traits\BelongsToThrough;

class CommonCategory extends Model
{
    use HasFactory;
    use BelongsToThrough;

    protected $fillable = [
        'name',
    ];


    public function translations()
    {
        return $this->hasMany(CommonCategoryTranslation::class, );
    }

    public function translation()
    {
        return $this->hasOne(CommonCategoryTranslation::class, 'common_category_id')->where('lang', App::getLocale());
    }

    public function translation_uz(){
        return $this->hasOne(CommonCategoryTranslation::class, 'common_category_id')->where('lang', 'uz');
    }

    public function translation_ru(){
        return $this->hasOne(CommonCategoryTranslation::class, 'common_category_id')->where('lang', 'ru');
    }

    public function restaurants(){
        return $this->hasManyThrough(Restaurant::class, Category::class, 'common_category_id', 'id',  'id');
    }
}
