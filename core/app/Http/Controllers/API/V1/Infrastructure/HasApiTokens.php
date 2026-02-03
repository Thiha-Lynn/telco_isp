<?php

namespace App\Traits;

use App\PersonalAccessToken;
use Illuminate\Support\Str;

trait HasApiTokens
{
    /**
     * The access token the user is using for the current request.
     *
     * @var \App\PersonalAccessToken|null
     */
    protected $accessToken;

    /**
     * Get the access tokens that belong to the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function tokens()
    {
        return $this->morphMany(PersonalAccessToken::class, 'tokenable');
    }

    /**
     * Create a new personal access token for the user.
     *
     * @param  string  $name
     * @param  array  $abilities
     * @param  \DateTimeInterface|null  $expiresAt
     * @return array
     */
    public function createToken(string $name, array $abilities = ['*'], $expiresAt = null)
    {
        $plainTextToken = Str::random(64);
        
        $token = $this->tokens()->create([
            'name' => $name,
            'token' => hash('sha256', $plainTextToken),
            'abilities' => $abilities,
            'expires_at' => $expiresAt,
        ]);

        return [
            'accessToken' => $token,
            'plainTextToken' => $plainTextToken,
        ];
    }

    /**
     * Get the current access token being used.
     *
     * @return \App\PersonalAccessToken|null
     */
    public function currentAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * Set the current access token for the user.
     *
     * @param  \App\PersonalAccessToken  $accessToken
     * @return $this
     */
    public function withAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
        
        return $this;
    }

    /**
     * Revoke all tokens for the user.
     *
     * @return void
     */
    public function revokeAllTokens()
    {
        $this->tokens()->delete();
    }

    /**
     * Revoke the current token.
     *
     * @return void
     */
    public function revokeCurrentToken()
    {
        if ($this->accessToken) {
            $this->accessToken->delete();
        }
    }
}
