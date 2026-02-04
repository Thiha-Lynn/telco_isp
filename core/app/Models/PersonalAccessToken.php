<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonalAccessToken extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'token',
        'abilities',
        'expires_at',
        'last_used_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'abilities' => 'array',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Get the tokenable model that the access token belongs to.
     */
    public function tokenable(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo('tokenable');
    }

    /**
     * Find the token instance matching the given token.
     */
    public static function findToken(string $token): ?static
    {
        $hashedToken = hash('sha256', $token);

        return static::where('token', $hashedToken)->first();
    }

    /**
     * Determine if the token has the given ability.
     */
    public function can(string $ability): bool
    {
        return in_array('*', $this->abilities ?? []) ||
               in_array($ability, $this->abilities ?? []);
    }

    /**
     * Determine if the token is missing the given ability.
     */
    public function cant(string $ability): bool
    {
        return ! $this->can($ability);
    }
}
