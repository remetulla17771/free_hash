<?php

declare(strict_types=1);

namespace app;

final class HttpRequestFactory
{
    public function create(): Request
    {
        return new Request(
            server: $_SERVER,
            query: $_GET,
            post: $_POST,
            rawBody: file_get_contents('php://input') ?: ''
        );
    }
}
