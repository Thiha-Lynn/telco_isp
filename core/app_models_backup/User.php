<?php

namespace App;

use App\Traits\HasApiTokens;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * NOTE: 'new_pass' stores password for legacy display purposes (masked in views)
     *
     * @var array
     */
    protected $fillable = [
        'name', 'uniq_id', 'role_id', 'email', 'device_type', 'device_id', 
        'sub_company', 'bind_user_id', 'phone', 'user_status', 'password', 
        'packageid', 'profile_image', 'new_pass'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function packageorders(): HasOne
    {
        return $this->hasOne(Packageorder::class, 'user_id');
    }
    public function billpaids(): HasOne
    {
        return $this->hasOne(Billpaid::class, 'user_id');
    }
    public function orders() : HasMany
    {
        return $this->hasMany('App\ProductOrder');
    }
}
