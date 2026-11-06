<?php

namespace Intelliada\Laravel\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Intelliada\Laravel\ScriptTag;

class ScriptController
{
    public function __invoke(Request $request): Response
    {
        $etag = '"'.ScriptTag::version().'"';

        $headers = [
            'Content-Type' => 'application/javascript; charset=utf-8',
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'ETag' => $etag,
            'X-Content-Type-Options' => 'nosniff',
        ];

        if ($request->header('If-None-Match') === $etag) {
            return new Response('', 304, $headers);
        }

        return new Response((string) file_get_contents(ScriptTag::scriptFile()), 200, $headers);
    }
}
