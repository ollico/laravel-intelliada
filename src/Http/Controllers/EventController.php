<?php

namespace Intelliada\Laravel\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Intelliada\Laravel\ScriptTag;

/**
 * Forwards browser events to Intelliada with the project token added
 * server-side, passing along the visitor's IP and user agent so they are
 * counted as themselves rather than as this server.
 */
class EventController
{
    private const MAX_PAYLOAD_BYTES = 16384;

    public function __invoke(Request $request, ScriptTag $scriptTag): JsonResponse
    {
        if (! $scriptTag->isEnabled()) {
            return new JsonResponse(['status' => 'disabled'], 202);
        }

        if (strlen($request->getContent()) > self::MAX_PAYLOAD_BYTES) {
            return new JsonResponse(['status' => 'too_large'], 413);
        }

        $payload = $request->json()->all();

        if ($payload === []) {
            return new JsonResponse(['status' => 'invalid'], 422);
        }

        try {
            $response = Http::acceptJson()
                ->timeout((float) config('intelliada.timeout', 2))
                ->withHeaders([
                    'X-Intelliada-Token' => (string) config('intelliada.token'),
                    'X-Intelliada-Client-Ip' => (string) $request->ip(),
                    'X-Intelliada-Client-User-Agent' => (string) $request->userAgent(),
                ])
                ->post((string) config('intelliada.endpoint'), $payload);
        } catch (ConnectionException) {
            return new JsonResponse(['status' => 'unavailable'], 202);
        }

        $body = $response->json();

        return new JsonResponse(is_array($body) ? $body : [], $response->status());
    }
}
