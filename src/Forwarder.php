<?php

namespace Intelliada\Laravel;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Sends events to Intelliada with the project token added server-side,
 * passing along the visitor's IP and user agent so they are counted as
 * themselves rather than as this server.
 */
class Forwarder
{
    /**
     * @param  array<string, string>  $headers
     * @param  array<array-key, mixed>  $payload
     */
    public function send(array $headers, array $payload): ?Response
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
    public function headers(Request $request): array
    {
        return array_filter([
            'X-Intelliada-Token' => (string) config('intelliada.token'),
            'X-Intelliada-Client-Ip' => (string) $request->ip(),
            'X-Intelliada-Client-User-Agent' => (string) $request->userAgent(),
            'X-Intelliada-Client-Country' => (string) $this->country($request),
        ]);
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
