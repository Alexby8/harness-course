<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\ApiKeyService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class AuthenticateApiKey
{
    public function __construct(
        private readonly ApiKeyService $apiKeyService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->extractBearerToken($request);

        if ($token === null) {
            return $this->unauthorized('API ключ не передан');
        }

        $user = $this->apiKeyService->findUserByKey($token);

        if ($user === null) {
            return $this->unauthorized('Неверный API ключ');
        }

        if (!$user->isActive()) {
            return $this->unauthorized('Ваш аккаунт заблокирован');
        }

        auth()->setUser($user);

        return $next($request);
    }

    private function extractBearerToken(Request $request): ?string
    {
        $header = $request->header('Authorization', '');

        if (!str_starts_with($header, 'Bearer ')) {
            return null;
        }

        $token = substr($header, 7);

        return $token !== '' ? $token : null;
    }

    private function unauthorized(string $message): JsonResponse
    {
        return response()->json(['ok' => false, 'error' => $message], 401);
    }
}
