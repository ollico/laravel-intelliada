<?php

declare(strict_types=1);

namespace Ollico\LaravelIntelliada\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Ollico\Intelliada\Event;
use Ollico\Intelliada\Http\Client;

class SendEventJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected Client $client;

    protected string $tenantId;

    protected string $spaceId;

    protected Event $event;

    public function __construct(Client $client, string $tenantId, string $spaceId, Event $event)
    {
        $this->client = $client;
        $this->tenantId = $tenantId;
        $this->spaceId = $spaceId;
        $this->event = $event;
    }

    public function handle()
    {
        return $this->client->post(
            'event',
            $this->tenantId,
            $this->spaceId,
            $this->event->payload()
        );
    }

    public function failed(\Throwable $exception)
    {
        logger($exception);
    }
}
