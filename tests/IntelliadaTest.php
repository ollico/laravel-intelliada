<?php

namespace Ollico\LaravelIntelliada\Tests;

use Illuminate\Support\Str;
use Ollico\Intelliada\Event;
use Ollico\Intelliada\Http\Exceptions\HttpClientException;
use Ollico\LaravelIntelliada\Facades\Intelliada;
use Ollico\LaravelIntelliada\Tests\Mocks\FakeClient;
use Ollico\LaravelIntelliada\Tests\TestCase;

class IntelliadaTest extends TestCase
{
    protected FakeClient $client;

    protected Intelliada $intelliada;

    protected string $tenantId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = new FakeClient();

        $this->tenantId = Str::uuid()->toString();

        $this->intelliada = new Intelliada();

        putenv('INTELLIADA_TOKEN=fd0730a-01fd-4a57-8909-379695758e62');
        putenv('INTELLIADA_KEY=d7052a47-c4df-4575-932b-b09fefa749bc');
        putenv('INTELLIADA_URL=https://www.intelliada.com/api');
    }

    public function test_it_can_send_an_event()
    {
        $spaceId = Str::uuid()->toString();

        $event = Event::make()
            ->setAction('swap foo bar')
            ->setData(['foo' => 'bar'])
            ->setOldData(['bar' => 'foo']);

        $this->intelliada->setClient($this->client);

        $this->intelliada->sendEvent($spaceId, $event);
        $this->intelliada->sendEvent($spaceId, $event);

        $this->assertEquals(2, $this->client->requestCount());
    }

    public function test_it_sends_the_correct_data()
    {
        $spaceId = Str::uuid()->toString();

        $event = Event::make()
            ->setAction('swap foo bar')
            ->setData(['foo' => 'bar'])
            ->setOldData(['bar' => 'foo']);

        $logger = new Intelliada();

        $logger->sendEvent($spaceId, $event);

        $request = $this->client->request(0);

        $this->assertEquals('swap foo bar', $request['arguments']['action']);
        $this->assertEquals('bar', $request['arguments']['data']['foo']);
        $this->assertEquals('foo', $request['arguments']['old_data']['bar']);
        $this->assertEquals('X-Space-Id: '.$spaceId, $request['headers'][1]);
        $this->assertEquals('X-Tenant-Id: '.$this->tenantId, $request['headers'][2]);
    }

    public function test_it_can_throw_an_exception_for_invalid_data()
    {
        $this->expectException(HttpClientException::class);

        $spaceId = Str::uuid()->toString();

        $event = Event::make();

        Intelliada::setClient(new FakeClient(statusCode: 422))
            ->sendEvent($spaceId, $event);
    }

    public function test_it_can_throw_an_exception_for_not_found()
    {
        $this->expectException(HttpClientException::class);

        $spaceId = Str::uuid()->toString();

        $event = Event::make();

        Intelliada::setClient(new FakeClient(statusCode: 404))
            ->sendEvent($spaceId, $event);
    }

    public function test_it_can_throw_an_exception_for_server_errors()
    {
        $this->expectException(HttpClientException::class);

        $spaceId = Str::uuid()->toString();

        $event = Event::make();

        Intelliada::setClient(new FakeClient(statusCode: 500))
            ->sendEvent($spaceId, $event);
    }
}
