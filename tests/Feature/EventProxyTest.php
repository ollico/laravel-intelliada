<?php

namespace Intelliada\Laravel\Tests\Feature;

use Illuminate\Foundation\Application;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Intelliada\Laravel\Tests\TestCase;
use Orchestra\Testbench\Attributes\DefineEnvironment;

class EventProxyTest extends TestCase
{
    private const CHROME = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36';

    public function test_events_are_forwarded_with_the_token_and_visitor_details(): void
    {
        Http::fake([
            'intelliada.test/*' => Http::response(['status' => 'accepted', 'visitor_hash' => str_repeat('a', 64)], 202),
        ]);

        $this->postJson('/k3x9q2/e', ['event_type' => 'pageview', 'pathname' => '/pricing'], [
            'User-Agent' => self::CHROME,
            'REMOTE_ADDR' => '203.0.113.7',
        ])
            ->assertStatus(202)
            ->assertExactJson(['status' => 'accepted', 'visitor_hash' => str_repeat('a', 64)]);

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://intelliada.test/api/v1/ingest'
            && $request->header('X-Intelliada-Token') === ['project-token']
            && $request->header('X-Intelliada-Client-Ip') === ['203.0.113.7']
            && $request->header('X-Intelliada-Client-User-Agent') === [self::CHROME]
            && $request['event_type'] === 'pageview'
            && $request['pathname'] === '/pricing');
    }

    public function test_the_visitor_country_from_the_cdn_is_forwarded(): void
    {
        Http::fake(['*' => Http::response(['status' => 'accepted'], 202)]);

        $this->postJson('/k3x9q2/e', ['event_type' => 'pageview'], ['CF-IPCountry' => 'GB'])->assertStatus(202);

        Http::assertSent(fn (Request $request): bool => $request->header('X-Intelliada-Client-Country') === ['GB']);
    }

    public function test_no_country_is_forwarded_without_a_cdn_header(): void
    {
        Http::fake(['*' => Http::response(['status' => 'accepted'], 202)]);

        $this->postJson('/k3x9q2/e', ['event_type' => 'pageview'])->assertStatus(202);

        Http::assertSent(fn (Request $request): bool => ! $request->hasHeader('X-Intelliada-Client-Country'));
    }

    public function test_events_do_not_need_a_csrf_token_or_session(): void
    {
        Http::fake(['*' => Http::response(['status' => 'accepted'], 202)]);

        $response = $this->postJson('/k3x9q2/e', ['event_type' => 'pageview'])->assertStatus(202);

        $this->assertNull($response->headers->get('Set-Cookie'));
    }

    public function test_upstream_errors_are_passed_back(): void
    {
        Http::fake(['*' => Http::response(['message' => 'Invalid project token.'], 401)]);

        $this->postJson('/k3x9q2/e', ['event_type' => 'pageview'])
            ->assertUnauthorized()
            ->assertJson(['message' => 'Invalid project token.']);
    }

    public function test_unreachable_intelliada_does_not_error_for_the_visitor(): void
    {
        Http::fake(fn () => throw new ConnectionException('Timed out'));

        $this->postJson('/k3x9q2/e', ['event_type' => 'pageview'])
            ->assertStatus(202)
            ->assertExactJson(['status' => 'unavailable']);
    }

    public function test_nothing_is_forwarded_when_disabled(): void
    {
        Http::fake();
        config(['intelliada.enabled' => false]);

        $this->postJson('/k3x9q2/e', ['event_type' => 'pageview'])
            ->assertStatus(202)
            ->assertExactJson(['status' => 'disabled']);

        Http::assertNothingSent();
    }

    public function test_empty_and_oversized_payloads_are_rejected(): void
    {
        Http::fake();

        $this->postJson('/k3x9q2/e', [])->assertStatus(422);
        $this->postJson('/k3x9q2/e', ['event_type' => 'pageview', 'metadata' => ['blob' => str_repeat('x', 20000)]])
            ->assertStatus(413);

        Http::assertNothingSent();
    }

    #[DefineEnvironment('useTightRateLimit')]
    public function test_events_are_rate_limited_per_visitor(): void
    {
        Http::fake(['*' => Http::response(['status' => 'accepted'], 202)]);

        $this->postJson('/k3x9q2/e', ['event_type' => 'pageview'])->assertStatus(202);
        $this->postJson('/k3x9q2/e', ['event_type' => 'pageview'])->assertStatus(202);
        $this->postJson('/k3x9q2/e', ['event_type' => 'pageview'])->assertTooManyRequests();
    }

    /**
     * @param  Application  $app
     */
    protected function useTightRateLimit($app): void
    {
        $app['config']->set('intelliada.middleware', ['throttle:2,1']);
    }
}
