<?php

declare(strict_types=1);

namespace app;

use ReflectionMethod;
use RuntimeException;

final class Dispatcher
{
    public function __construct(
        private ControllerResolver $controllers,
        private ParameterResolver $parameters,
    ) {
    }

    public function dispatch(Route $route): mixed
    {
        $controller = $this->controllers->resolve($route->controller);
        $method = $route->action;

        if (!method_exists($controller, $method)) {
            throw new RuntimeException("Action not found: {$route->controller}::{$method}().", 404);
        }

        $reflection = new ReflectionMethod($controller, $method);
        if (!$reflection->isPublic()) {
            throw new RuntimeException("Action is not public: {$route->controller}::{$method}().", 404);
        }

        $arguments = [];
        foreach ($reflection->getParameters() as $parameter) {
            $arguments[] = $this->parameters->resolve($parameter, $route->parameters);
        }

        return $reflection->invokeArgs($controller, $arguments);
    }
}
