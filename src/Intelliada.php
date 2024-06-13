<?php

declare(strict_types=1);

namespace Ollico\LaravelIntelliada;

use Ollico\Intelliada\Event;
use Ollico\Intelliada\Intelliada as BaseClass;
use Ollico\LaravelIntelliada\Jobs\SendEventJob;

class Intelliada extends BaseClass
{
    public function sendEvent(string $spaceId, Event $event): bool
    {
        SendEventJob::dispatch(
            $this->client,
            $this->tenantId,
            $spaceId,
            $event
        )
            ->onQueue(config('intelliada.job_queue'));

        return true;
    }
}
