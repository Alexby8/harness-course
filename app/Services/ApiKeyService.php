<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;

final class ApiKeyService
{
    private const PREFIX = 'bg_live_';

    public function generate(): string
    {
        return self::PREFIX . bin2hex(random_bytes(16));
    }

    public function hash(string $apiKey): string
    {
        return hash('sha256', $apiKey);
    }

    public function preview(string $apiKey): string
    {
        return self::PREFIX . '****' . substr($apiKey, -4);
    }

    public function issueForUser(User $user): string
    {
        $apiKey = $this->generate();

        $user->update([
            'api_key_hash' => $this->hash($apiKey),
            'api_key_preview' => $this->preview($apiKey),
        ]);

        return $apiKey;
    }

    public function findUserByKey(string $apiKey): ?User
    {
        $hash = $this->hash($apiKey);

        return User::where('api_key_hash', $hash)->first();
    }
}
