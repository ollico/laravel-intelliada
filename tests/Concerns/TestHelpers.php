<?php

declare(strict_types=1);

namespace Ollico\LaravelIntelliada\Tests\Concerns;

trait TestHelpers
{
    public function setConfigVars(string $tenantId)
    {
        config()->set('intelliada.token', 'fd0730a-01fd-4a57-8909-379695758e62');
        config()->set('intelliada.key', $tenantId);
        config()->set('intelliada.url', 'https://www.intelliada.com/api');
    }
}
