<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\AcquisitionTracker;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ClearDeclinedAcquisition
{
    public function __construct(private readonly AcquisitionTracker $tracker) {}

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        if (! $this->tracker->allowed($request)) {
            $this->tracker->clear($request, $response);
        }

        return $response;
    }
}
