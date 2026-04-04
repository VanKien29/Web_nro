<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Hashing\Hasher;

class PlainTextUserProvider extends EloquentUserProvider
{
    public function __construct(string $model)
    {
        // Pass a dummy hasher — we override validateCredentials entirely.
        parent::__construct(app('hash'), $model);
    }

    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        $plain = $credentials['password'] ?? '';

        return $plain !== '' && trim($plain) === trim($user->getAuthPassword());
    }
}