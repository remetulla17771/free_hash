<?php

declare(strict_types=1);

namespace app;

use RuntimeException;

class Router
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

        if (!class_exists($controllerClass)) {
            throw new RuntimeException('Controller not found: ' . $controllerClass, 404);
        }

        $method = 'action' . $this->toStudly($actionName);
        if (!method_exists($controllerClass, $method)) {
            throw new RuntimeException('Action not found: ' . $method, 404);
        }

        $parameterNames = $this->parameterNames($controllerClass, $method);
        $parameters = [];

        foreach ($parameterSegments as $index => $value) {
            if (isset($parameterNames[$index])) {
                $parameters[$parameterNames[$index]] = $value;
            }
        }

        foreach ($this->request->query() as $key => $value) {
            $parameters[$key] = $value;
        }

        return new Route($controllerClass, $method, $parameters);
    }

    private function parameterNames(string $controllerClass, string $method): array
    {
        $names = [];
        foreach ((new \ReflectionMethod($controllerClass, $method))->getParameters() as $parameter) {
            $names[] = $parameter->getName();
        }
        return $names;
    }

    private function toStudly(string $name): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $name)));
    }
}
