<?php

namespace Intelliada\Laravel\Http\Controllers;

use Illuminate\Http\Client\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Intelliada\Laravel\Forwarder;
use Intelliada\Laravel\ScriptTag;

/**
 * Forwards browser events to Intelliada.
 *
 * Only a visitor's first event waits for Intelliada, because the browser
 * needs the visitor hash it returns. Later events already carry that hash,
 * so they are answered straight away and forwarded once the response has
 * been sent, keeping a slow network from holding up the host app.
 */
class EventController
{
    private const MAX_PAYLOAD_BYTES = 16384;

    public function __invoke(Request $request, ScriptTag $scriptTag, Forwarder $forwarder): JsonResponse
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

        $headers = $forwarder->headers($request);

        if ($this->hasVisitorHash($payload)) {
            app()->terminating(function () use ($forwarder, $headers, $payload): void {
                $forwarder->send($headers, $payload);
            });

            return new JsonResponse(['status' => 'accepted', 'visitor_hash' => $payload['visitor_hash']], 202);
        }

        $response = $forwarder->send($headers, $payload);

        if (! $response instanceof Response) {
            return new JsonResponse(['status' => 'unavailable'], 202);
        }

        $body = $response->json();

        return new JsonResponse(is_array($body) ? $body : [], $response->status());
    }

    /**
     * @param  array<array-key, mixed>  $payload
     */
    private function hasVisitorHash(array $payload): bool
    {
        $hash = $payload['visitor_hash'] ?? null;

        return is_string($hash) && preg_match('/^[a-f0-9]{64}$/', $hash) === 1;
    }
}
