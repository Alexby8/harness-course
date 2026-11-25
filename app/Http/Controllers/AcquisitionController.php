<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\AcquisitionTracker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

final class AcquisitionController extends Controller
{
    public function store(Request $request, AcquisitionTracker $tracker): JsonResponse
    {
        $request->validate([
            'analytics_allowed' => ['required', 'accepted'],
            'parameters' => ['sometimes', 'array:'.implode(',', array_keys(Config::array('acquisition.parameters')))],
            'parameters.*' => ['nullable', 'string', 'max:256', 'not_regex:/[\x00-\x1F\x7F]/'],
            'landing_path' => ['required_with:parameters', 'string', 'max:160', 'regex:~\A/(?!/)[^?#\x00-\x20]*\z~u'],
            'referrer_host' => ['nullable', 'string', 'max:120', 'regex:/\A[a-z0-9.-]+\z/i'],
            'client_id' => ['nullable', 'string', 'regex:/\A[0-9]{1,64}\z/D'],
        ]);
        $response = response()->json(['stored' => $tracker->allowed($request)])
            ->header('Cache-Control', 'no-store, private');
        $tracker->capture($request, $response);

        return $response;
    }

    public function destroy(Request $request, AcquisitionTracker $tracker): JsonResponse
    {
        $response = response()->json(['cleared' => true])->header('Cache-Control', 'no-store, private');
        if ($request->boolean('withdrawal')) {
            $tracker->clear($request, $response);
        } else {
            $tracker->forgetCookies($request, $response);
        }

        return $response;
    }
}
