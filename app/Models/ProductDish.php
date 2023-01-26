<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductDish extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'restaurant_dish_id',
        'quantity'
    ];

    function product(){
        return $this->belongsTo(Product::class);
    }

    function restaurant_dish(){
        return $this->belongsTo(RestaurantDish::class);
    }
}
