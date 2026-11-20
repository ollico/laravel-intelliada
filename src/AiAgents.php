<?php

namespace Intelliada\Laravel;

/**
 * AI assistants that fetch or index pages to answer someone's question.
 * They don't run JavaScript, so the tracking script never sees them.
 */
class AiAgents
{
    /**
     * @var list<string>
     */
    private const USER_AGENTS = [
        'chatgpt-user',
        'oai-searchbot',
        'claude-user',
        'claude-searchbot',
        'perplexity-user',
        'perplexitybot',
        'mistralai-user',
        'meta-externalfetcher',
        'duckassistbot',
        'amzn-user',
    ];

    public function matches(?string $userAgent): bool
    {
        $userAgent = strtolower((string) $userAgent);

        if ($userAgent === '') {
            return false;
        }

        foreach (self::USER_AGENTS as $fragment) {
            if (str_contains($userAgent, $fragment)) {
                return true;
            }
        }

        return false;
    }
}
