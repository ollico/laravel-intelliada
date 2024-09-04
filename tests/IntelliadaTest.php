<?php

namespace Ollico\LaravelIntelliada\Tests;

use Illuminate\Support\Str;
use Ollico\Intelliada\Event;
use Ollico\Intelliada\Http\Exceptions\HttpClientException;
use Ollico\LaravelIntelliada\Facades\Intelliada;
use Ollico\LaravelIntelliada\Tests\Concerns\TestHelpers;
use Ollico\LaravelIntelliada\Tests\Mocks\FakeClient;

class IntelliadaTest extends TestCase
{
    use TestHelpers;

    protected FakeClient $client;

    protected Intelliada $intelliada;

    protected string $tenantId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = new FakeClient();

        $this->tenantId = Str::uuid()->toString();
    }

    public function test_it_can_send_an_event()
    {
        $this->setConfigVars($this->tenantId);

        $spaceId = Str::uuid()->toString();

        $event = Event::make()
            ->setAction('swap foo bar')
            ->setData(['foo' => 'bar'])
            ->setOldData(['bar' => 'foo']);

        $intelliada = Intelliada::setClient($this->client);

        $intelliada->sendEvent($spaceId, $event);
        $intelliada->sendEvent($spaceId, $event);

        $this->assertEquals(2, $this->client->requestCount());
    }

    public function test_it_sends_the_correct_data()
    {
        $this->setConfigVars($this->tenantId);

        $spaceId = Str::uuid()->toString();

        $event = Event::make()
            ->setAction('swap foo bar')
            ->setData(['foo' => 'bar'])
            ->setOldData(['bar' => 'foo']);

        $intelliada = Intelliada::setClient($this->client);

        $intelliada->sendEvent($spaceId, $event);

        $request = $this->client->request(0);

        $this->assertEquals('swap foo bar', $request['arguments']['action']);
        $this->assertEquals('bar', $request['arguments']['data']['foo']);
        $this->assertEquals('foo', $request['arguments']['old_data']['bar']);
        $this->assertEquals('X-Space-Id: '.$spaceId, $request['headers'][1]);
        $this->assertEquals('X-Tenant-Id: '.$this->tenantId, $request['headers'][2]);
    }

    public function test_it_can_throw_an_exception_for_invalid_data()
    {
        $this->setConfigVars($this->tenantId);

        $this->expectException(HttpClientException::class);

        $spaceId = Str::uuid()->toString();

        $event = Event::make();

        Intelliada::setClient(new FakeClient(statusCode: 422))
            ->sendEvent($spaceId, $event);
    }

    public function test_it_can_throw_an_exception_for_not_found()
    {
        $this->setConfigVars($this->tenantId);

        $this->expectException(HttpClientException::class);

        $spaceId = Str::uuid()->toString();

        $event = Event::make();

        Intelliada::setClient(new FakeClient(statusCode: 404))
            ->sendEvent($spaceId, $event);
    }

    public function test_it_can_throw_an_exception_for_server_errors()
    {
        $this->setConfigVars($this->tenantId);

        $this->expectException(HttpClientException::class);

        $spaceId = Str::uuid()->toString();

        $event = Event::make();

        Intelliada::setClient(new FakeClient(statusCode: 500))
            ->sendEvent($spaceId, $event);
    }
}
