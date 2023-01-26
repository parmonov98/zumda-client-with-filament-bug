<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'status',
        'price',
        'photo_id',
        'profit_in_percentage',
        'has_required_dish',
        'preparation_time',
    ];
    protected $casts = [
        'dishes' => 'array',
    ];
    public function translation()
    {
        return $this->hasOne(ProductTranslation::class, 'product_id', 'id')->where('lang', App::getLocale());
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

    public function options()
    {
        return $this->hasMany(ProductOption::class, 'product_id');
    }

    public function dishes()
    {
        return $this->hasMany(ProductDish::class, 'product_id', 'id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function restaurant()
    {
        return $this->hasOneThrough(Restaurant::class, Category::class, 'id', 'id', 'category_id', 'restaurant_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
