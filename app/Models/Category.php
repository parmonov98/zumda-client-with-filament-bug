<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

class Category extends Model
{
    use HasFactory;
    protected $fillable = [
        'restaurant_id',
        'user_id',
        'parent_id',
        'common_category_id',
        'description',
        'status'
    ];

    public function translation()
    {
        return $this->hasOne(CategoryTranslation::class, 'category_id')->where('lang', App::getLocale());
    }

    public function translation_uz(){
        return $this->hasOne(CategoryTranslation::class, 'category_id')->where('lang', 'uz');
    }
    public function translation_ru(){
        return $this->hasOne(CategoryTranslation::class, 'category_id')->where('lang', 'ru');
    }

    public function translations()
    {
        return $this->hasMany(CategoryTranslation::class, 'category_id');
    }


    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id', 'id');
    }
    public function common_category()
    {
        return $this->belongsTo(CommonCategory::class);
    }
    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    public function restaurant(){
        return $this->belongsTo(Restaurant::class);
    }
    public function user(){
        return $this->belongsTo(User::class);
    }

}
