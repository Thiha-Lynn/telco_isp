<?php

namespace App\Traits;

use App\Models\PersonalAccessToken;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

trait HasApiTokens
{
    /**
     * The access token the user is using for the current request.
     */
    protected ?PersonalAccessToken $accessToken = null;

    /**
     * Get the access tokens that belong to the model.
     */
    public function tokens(): MorphMany
    {
        return $this->morphMany(PersonalAccessToken::class, 'tokenable');
    }

    /**
     * Create a new personal access token for the user.
     *
     * @param  array<int, string>  $abilities
     * @return array{accessToken: PersonalAccessToken, plainTextToken: string}
     */
    public function createToken(string $name, array $abilities = ['*'], ?DateTimeInterface $expiresAt = null): array
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
     */
    public function currentAccessToken(): ?PersonalAccessToken
    {
        return $this->accessToken;
    }

    /**
     * Set the current access token for the user.
     */
    public function withAccessToken(PersonalAccessToken $accessToken): static
    {
        $this->accessToken = $accessToken;
        
        return $this;
    }

    /**
     * Revoke all tokens for the user.
     */
    public function revokeAllTokens(): void
    {
        $this->tokens()->delete();
    }

    /**
     * Revoke the current token.
     */
    public function revokeCurrentToken(): void
    {
        $this->accessToken?->delete();
    }
}
