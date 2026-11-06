<?php

namespace Intelliada\Laravel\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Intelliada\Laravel\ScriptTag;
use Intelliada\Laravel\Tests\TestCase;

class ScriptTest extends TestCase
{
    public function test_script_is_served_from_the_first_party_path(): void
    {
        $response = $this->get('/k3x9q2/main.js')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/javascript; charset=utf-8')
            ->assertHeader('ETag', '"'.ScriptTag::version().'"');

        $this->assertStringContainsString('immutable', (string) $response->headers->get('Cache-Control'));
        $this->assertSame(file_get_contents(ScriptTag::scriptFile()), $response->getContent());
        $this->assertNull($response->headers->get('Set-Cookie'));
    }

    public function test_script_returns_not_modified_for_a_matching_etag(): void
    {
        $this->get('/k3x9q2/main.js', ['If-None-Match' => '"'.ScriptTag::version().'"'])
            ->assertStatus(304);
    }

    public function test_directive_renders_a_first_party_script_tag(): void
    {
        $html = Blade::render('@intelliada');

        $this->assertSame(
            '<script defer src="/k3x9q2/main.js?v='.ScriptTag::version().'" data-endpoint="/k3x9q2/e"></script>',
            $html,
        );
        $this->assertStringNotContainsString('project-token', $html);
        $this->assertStringNotContainsString('intelliada', $html);
    }

    public function test_directive_renders_nothing_without_a_token(): void
    {
        config(['intelliada.token' => null]);

        $this->assertSame('', Blade::render('@intelliada'));
    }

    public function test_directive_renders_nothing_when_disabled(): void
    {
        config(['intelliada.enabled' => false]);

        $this->assertSame('', Blade::render('@intelliada'));
    }
}
