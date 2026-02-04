<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PersonalAccessToken extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'token',
        'abilities',
        'expires_at',
        'last_used_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'abilities' => 'array',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'token',
    ];

    /**
     * Get the tokenable model that the access token belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function tokenable()
    {
        return $this->morphTo('tokenable');
    }

    /**
     * Find the token instance matching the given token.
     *
     * @param  string  $token
     * @return static|null
     */
    public static function findToken($token)
    {
        $hashedToken = hash('sha256', $token);
        
        return static::where('token', $hashedToken)->first();
    }

    /**
     * Determine if the token has a specific ability.
     *
     * @param  string  $ability
     * @return bool
     */
    public function can($ability)
    {
        return in_array('*', $this->abilities ?? []) ||
               in_array($ability, $this->abilities ?? []);
    }

    /**
     * Determine if the token is expired.
     *
     * @return bool
     */
    public function isExpired()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
