<?php

declare(strict_types=1);

namespace app;

use RuntimeException;

final class MiddlewareDispatcher
{
    /** @param array<int, MiddlewareInterface|callable|string> $middleware */
    public function __construct(private Container $container, private array $middleware = [])
    {
    }

    public function handle(Request $request, callable $handler): Response
    {
        $pipeline = $handler;

        foreach (array_reverse($this->middleware) as $middleware) {
            $next = $pipeline;
            $pipeline = function (Request $request) use ($middleware, $next): Response {
                $instance = is_string($middleware)
                    ? $this->container->get($middleware)
                    : $middleware;

                if ($instance instanceof MiddlewareInterface) {
                    return $instance->process($request, $next);
                }

                if (is_callable($instance)) {
                    $response = $instance($request, $next);
                    if (!$response instanceof Response) {
                        throw new RuntimeException('Middleware must return a Response.');
                    }
                    return $response;
                }

                throw new RuntimeException('Invalid middleware definition.');
            };
        }

        $response = $pipeline($request);
        if (!$response instanceof Response) {
            throw new RuntimeException('HTTP handler must return a Response.');
        }

        return $response;
    }
}
