<?php

namespace Intelliada\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Intelliada\Laravel\AiAgents;
use Intelliada\Laravel\Forwarder;
use Intelliada\Laravel\ScriptTag;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records a pageview when an AI assistant fetches a page. The pageview is
 * forwarded once the response has been sent, so the page is never held up.
 */
class RecordAiAgentPageviews
{
    public function __construct(
        private ScriptTag $scriptTag,
        private AiAgents $aiAgents,
        private Forwarder $forwarder,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        if (! $response instanceof Response || ! $this->shouldRecord($request, $response)) {
            return $response;
        }

        $headers = $this->forwarder->headers($request);
        $payload = array_filter([
            'event_type' => 'pageview',
            'url' => $request->fullUrl(),
            'pathname' => $request->getPathInfo(),
            'referrer' => $request->headers->get('referer'),
            'domain' => $request->getHost(),
        ]);

        app()->terminating(function () use ($headers, $payload): void {
            $this->forwarder->send($headers, $payload);
        });

        return $response;
    }

    private function shouldRecord(Request $request, Response $response): bool
    {
        return (bool) config('intelliada.ai_agents', true)
            && $this->scriptTag->isEnabled()
            && $request->isMethod('GET')
            && ! $request->hasHeader('X-Inertia')
            && $response->isSuccessful()
            && str_contains((string) $response->headers->get('Content-Type'), 'text/html')
            && $this->aiAgents->matches($request->userAgent());
    }
}
