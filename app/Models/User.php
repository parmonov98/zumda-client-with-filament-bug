<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\HasTeams;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use HasTeams;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use SoftDeletes;

    static $LANGS =[
        'uz' => "O'zbek 🇺🇿",
        'ru' => "Русский 🇷🇺",
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'phone_number',
        'telegram_id',
        'role',
        'language',
        'last_step',
        'last_value',
        'last_message_id',
        'activation_code',
        'temp_client_id',
        'status',
        'self_status',
        'email',
        'password',
        'operator_id',
        'administrator_id',
        'driver_id',
        'partner_id',
        'partner_operator_id',
        'activation_code'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'profile_photo_url',
    ];


    public function cart()
    {
        return $this->hasOne(Cart::class, 'user_id');
    }
    public function orders()
    {
        return $this->hasMany(Order::class, 'user_id');
    }
    public function driver_orders()
    {
        return $this->hasMany(Order::class, 'driver_id');
    }
    public function reviews()
    {
        return $this->hasMany(Review::class, 'user_id');
    }
    public function partner_operator()
    {
        return $this->hasOne(PartnerOperator::class, 'id', 'partner_operator_id');
    }

    public function operator()
    {
        return $this->hasOne(Operator::class, 'id', 'operator_id');
    }

    public function client()
    {
        return $this->hasOne(Client::class, 'id', 'operator_id');
    }
    public function driver()
    {
        return $this->hasOne(Driver::class, 'id', 'driver_id');
    }
    public function partner()
    {
        return $this->hasOne(Partner::class, 'id', 'partner_id');
    }
    public function messages()
    {
        return $this->hasMany(Messages::class, 'user_id');
    }

//    public function restaurant(){
//        return $this->hasOne(Restaurant::class, 'partner_user_id', 'id');
//    }
    public function restaurant(){
        return $this->belongsTo(Restaurant::class, 'restaurant_id');
    }

    public function scopeExceptByRole($q, $except){
        if (gettype($except) == 'string'){
            $q->where('role', '!=', $except);
        }
        if (gettype($except) == 'array'){
            $roles = $except;
            foreach($roles as $role){
                $q->where('role', '!=', $role);
            }
        }
        return $q;
    }

//    public function getNameAttribute()
//    {
//        return $this->first_name . " " . $this->last_name;
//    }

    public function canAccessFilament(): bool
    {
//        return $this->hasVerifiedEmail();
        return true;
    }
}
