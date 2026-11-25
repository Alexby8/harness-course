<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\WebhookIngress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class TelegramWebhookController extends Controller
{
    public function receive(Request $request, string $botPublicId, string $secret, WebhookIngress $ingress): JsonResponse
    {
        try {
            $accepted = $ingress->accept($botPublicId, $secret, $request->all());
        } catch (\Throwable) {
            // SQL/queue exception messages may contain the update or credentials.
            Log::warning('Webhook ingress transaction failed', [
                'bot_public_id' => preg_match('/^bot_[A-Za-z0-9_-]{1,60}$/D', $botPublicId) === 1 ? $botPublicId : '-',
            ]);

            return response()->json(['ok' => false], 503);
        }

        return response()->json(['ok' => $accepted], $accepted ? 200 : 403);
    }
}
