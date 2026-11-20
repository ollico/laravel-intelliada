<?php

namespace Intelliada\Laravel\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Intelliada\Laravel\ScriptTag;

/**
 * Forwards browser events to Intelliada with the project token added
 * server-side, passing along the visitor's IP and user agent so they are
 * counted as themselves rather than as this server.
 *
 * Only a visitor's first event waits for Intelliada, because the browser
 * needs the visitor hash it returns. Later events already carry that hash,
 * so they are answered straight away and forwarded once the response has
 * been sent, keeping a slow network from holding up the host app.
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

        $headers = $this->headers($request);

        if ($this->hasVisitorHash($payload)) {
            app()->terminating(function () use ($headers, $payload): void {
                $this->forward($headers, $payload);
            });

            return new JsonResponse(['status' => 'accepted', 'visitor_hash' => $payload['visitor_hash']], 202);
        }

        $response = $this->forward($headers, $payload);

        if (! $response instanceof Response) {
            return new JsonResponse(['status' => 'unavailable'], 202);
        }

        $body = $response->json();

        return new JsonResponse(is_array($body) ? $body : [], $response->status());
    }

    /**
     * @param  array<string, string>  $headers
     * @param  array<array-key, mixed>  $payload
     */
    private function forward(array $headers, array $payload): ?Response
    {
        try {
            return Http::acceptJson()
                ->timeout((float) config('intelliada.timeout', 2))
                ->withHeaders($headers)
                ->post((string) config('intelliada.endpoint'), $payload);
        } catch (ConnectionException) {
            return null;
        }
    }

    /**
     * @return array<string, string>
     */
    private function headers(Request $request): array
    {
        return array_filter([
            'X-Intelliada-Token' => (string) config('intelliada.token'),
            'X-Intelliada-Client-Ip' => (string) $request->ip(),
            'X-Intelliada-Client-User-Agent' => (string) $request->userAgent(),
            'X-Intelliada-Client-Country' => (string) $this->country($request),
        ]);
    }

    /**
     * @param  array<array-key, mixed>  $payload
     */
    private function hasVisitorHash(array $payload): bool
    {
        $hash = $payload['visitor_hash'] ?? null;

        return is_string($hash) && preg_match('/^[a-f0-9]{64}$/', $hash) === 1;
    }

    private function country(Request $request): ?string
    {
        foreach ((array) config('intelliada.country_headers', []) as $header) {
            $value = trim((string) $request->header((string) $header));

            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }
}
