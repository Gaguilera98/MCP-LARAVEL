<?php

namespace App\Http\Middleware;

use Laravel\Passport\Contracts\ScopeAuthorizable;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Adapts a Sanctum PAT so Passport's withAccessToken() / tokenCan() can store it.
 */
final class SanctumTokenAdapter implements ScopeAuthorizable
{
    public function __construct(public PersonalAccessToken $token) {}

    public function can(string $scope): bool
    {
        return $this->token->can($scope);
    }

    public function cant(string $scope): bool
    {
        return ! $this->can($scope);
    }
}
