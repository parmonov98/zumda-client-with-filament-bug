<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

class Restaurant extends Model
{
    use HasFactory;

    public $fillable = [
        'name',
        'address',
        'latitude',
        'longitude',
        'partner_id',
        'operator_id',
        'phone_number',
        'payment_card',
        'expiration_date',
    ];

    public function translation()
    {
        return $this->hasOne(RestaurantTranslation::class, 'restaurant_id' )->where('lang', App::getLocale());
    }

    public function employee(){
        return $this->belongsTo(User::class, 'partner_user_id');
    }
    public function operators(){
        return $this->hasManyThrough(User::class, PartnerOperator::class);
    }
    public function partner_operators(){
        return $this->hasMany(PartnerOperator::class);
    }
    public function owner(){
        return $this->belongsTo(Partner::class, 'partner_id', 'id');
    }
    public function categories(){
        return $this->hasMany(Category::class, 'restaurant_id');
    }

    public function dishes(){
        return $this->hasMany(RestaurantDish::class, 'restaurant_id', 'id');
    }
}
