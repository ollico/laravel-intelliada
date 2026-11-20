<?php

namespace Intelliada\Laravel\Tests\Feature;

use Illuminate\Foundation\Application;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Http;
use Intelliada\Laravel\Tests\TestCase;

class AiAgentPageviewTest extends TestCase
{
    private const CHATGPT = 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko); compatible; ChatGPT-User/1.0; +https://openai.com/bot';

    private const CHROME = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36';

    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('k', 32)));
    }

    /**
     * @param  Router  $router
     */
    protected function defineRoutes($router): void
    {
        $router->middleware('web')->group(function (Router $router): void {
            $router->get('/fostering', fn () => '<html><body>Fostering</body></html>');
            $router->get('/api-like', fn () => ['ok' => true]);
            $router->post('/enquiry', fn () => '<html></html>');
        });

        $router->get('/outside-web', fn () => '<html></html>');
    }

    public function test_a_pageview_is_forwarded_after_an_ai_agent_loads_a_page(): void
    {
        Http::fake(['*' => Http::response(['status' => 'accepted'], 202)]);

        $this->get('/fostering?utm_source=chatgpt.com', [
            'User-Agent' => self::CHATGPT,
            'Referer' => 'https://chatgpt.com/',
            'REMOTE_ADDR' => '203.0.113.7',
        ])->assertOk()->assertSee('Fostering');

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://intelliada.test/api/v1/ingest'
            && $request->header('X-Intelliada-Token') === ['project-token']
            && $request->header('X-Intelliada-Client-Ip') === ['203.0.113.7']
            && $request->header('X-Intelliada-Client-User-Agent') === [self::CHATGPT]
            && $request['event_type'] === 'pageview'
            && $request['url'] === 'http://localhost/fostering?utm_source=chatgpt.com'
            && $request['pathname'] === '/fostering'
            && $request['referrer'] === 'https://chatgpt.com/'
            && $request['domain'] === 'localhost');
    }

    public function test_people_are_left_to_the_tracking_script(): void
    {
        Http::fake();

        $this->get('/fostering', ['User-Agent' => self::CHROME])->assertOk();

        Http::assertNothingSent();
    }

    public function test_only_successful_html_page_loads_are_recorded(): void
    {
        Http::fake();

        $this->get('/api-like', ['User-Agent' => self::CHATGPT])->assertOk();
        $this->get('/missing', ['User-Agent' => self::CHATGPT])->assertNotFound();
        $this->post('/enquiry', [], ['User-Agent' => self::CHATGPT]);
        $this->get('/fostering', ['User-Agent' => self::CHATGPT, 'X-Inertia' => 'true']);
        $this->get('/outside-web', ['User-Agent' => self::CHATGPT])->assertOk();

        Http::assertNothingSent();
    }

    public function test_nothing_is_recorded_when_switched_off_or_tracking_is_disabled(): void
    {
        Http::fake();

        config(['intelliada.ai_agents' => false]);
        $this->get('/fostering', ['User-Agent' => self::CHATGPT])->assertOk();

        config(['intelliada.ai_agents' => true, 'intelliada.token' => null]);
        $this->get('/fostering', ['User-Agent' => self::CHATGPT])->assertOk();

        Http::assertNothingSent();
    }

    public function test_unreachable_intelliada_does_not_affect_the_page(): void
    {
        Http::fake(fn () => throw new ConnectionException('Timed out'));

        $this->get('/fostering', ['User-Agent' => self::CHATGPT])
            ->assertOk()
            ->assertSee('Fostering');
    }
}
