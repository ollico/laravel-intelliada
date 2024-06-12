<?php

declare(strict_types=1);

namespace Ollico\LaravelIntelliada\Tests\Mocks;

use Ollico\Intelliada\Http\Client;
use Ollico\Intelliada\Http\HttpMethod;
use Ollico\Intelliada\Http\Response;

class FakeClient extends Client
{
    protected $requests = [];

    protected $statusCode = 200;

    public function __construct($token = null, int $statusCode = 200)
    {
        parent::__construct($token ?? uniqid());

        $this->statusCode = $statusCode;
    }

    public function sendRequest(
        HttpMethod $method,
        string $fullUrl,
        array $headers = [],
        array $arguments = []
    ): Response {
        $this->requests[] = [
            'method' => $method,
            'fullUrl' => $fullUrl,
            'headers' => $headers,
            'arguments' => $arguments,
        ];

        return new Response('Ok', ['http_code' => $this->statusCode]);
    }

    public function requestCount(): int
    {
        return count($this->requests);
    }

    public function request(int $key = 0): array
    {
        return array_key_exists($key, $this->requests)
            ? $this->requests[$key]
            : [];
    }
}
