<?php

declare(strict_types=1);

namespace app;

use ReflectionMethod;
use RuntimeException;

final class Router
{
    public function __construct(
        private Request $request,
        private ModuleManager $modules,
    ) {
    }

    public function match(): Route
    {
        $segments = $this->request->getSegments();
        $first = $segments[0] ?? null;

        if ($first !== null && $this->modules->has($first)) {
            $module = $this->modules->get($first);
            $controllerName = $segments[1] ?? 'default';
            $actionName = $segments[2] ?? 'index';
            $controllerClass = $module->getControllerNamespace() . '\\' . $this->toStudly($controllerName) . 'Controller';
            $parameterSegments = array_slice($segments, 3);
        } else {
            $controllerName = $first ?? 'site';
            $actionName = $segments[1] ?? 'index';
            $controllerClass = 'app\\controllers\\' . $this->toStudly($controllerName) . 'Controller';
            $parameterSegments = array_slice($segments, 2);
        }

        $method = 'action' . $this->toStudly($actionName);
        $parameters = $this->mapPathParameters($controllerClass, $method, $parameterSegments);

        // Query parameters are defaults. A path parameter always wins.
        foreach ($this->request->query() as $key => $value) {
            if (!array_key_exists($key, $parameters)) {
                $parameters[$key] = $value;
            }
        }

        return new Route($controllerClass, $method, $parameters);
    }

    private function mapPathParameters(string $controllerClass, string $method, array $segments): array
    {
        if (!class_exists($controllerClass)) {
            throw new RuntimeException('Controller not found: ' . $controllerClass, 404);
        }

        if (!method_exists($controllerClass, $method)) {
            throw new RuntimeException('Action not found: ' . $method, 404);
        }

        $names = [];
        foreach ((new ReflectionMethod($controllerClass, $method))->getParameters() as $parameter) {
            $names[] = $parameter->getName();
        }

        $parameters = [];
        foreach ($segments as $index => $value) {
            if (isset($names[$index])) {
                $parameters[$names[$index]] = $value;
            }
        }

        return $parameters;
    }

    private function toStudly(string $name): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $name)));
    }
}
