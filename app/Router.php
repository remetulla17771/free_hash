<?php

declare(strict_types=1);

namespace app;

use RuntimeException;

class Router
{
    public function __construct(private Request $request)
    {
    }

    public function match(): Route
    {
        $segments = $this->request->getSegments();

        $moduleId = $segments[0] ?? null;
        $isModule = $moduleId !== null && preg_match('/^[A-Za-z0-9_]+$/', $moduleId)
            && is_dir(__DIR__ . '/../modules/' . $moduleId);

        if ($isModule) {
            $controllerName = $segments[1] ?? 'default';
            $actionName = $segments[2] ?? 'index';
            $controllerClass = 'modules\\' . $moduleId . '\\controllers\\'
                . $this->toStudly($controllerName) . 'Controller';
            $parameters = array_slice($segments, 3);
        } else {
            $controllerName = $segments[0] ?? 'site';
            $actionName = $segments[1] ?? 'index';
            $controllerClass = 'app\\controllers\\'
                . $this->toStudly($controllerName) . 'Controller';
            $parameters = array_slice($segments, 2);
        }

        if (!class_exists($controllerClass)) {
            throw new RuntimeException('Controller not found: ' . $controllerClass, 404);
        }

        $query = $this->request->query();
        $parameterNames = $this->parameterNames($controllerClass, $actionName);
        $named = [];

        foreach ($parameters as $index => $value) {
            if (isset($parameterNames[$index])) {
                $named[$parameterNames[$index]] = $value;
            }
        }

        foreach ($query as $key => $value) {
            $named[$key] = $value;
        }

        return new Route($controllerClass, 'action' . $this->toStudly($actionName), $named);
    }

    private function parameterNames(string $controllerClass, string $actionName): array
    {
        $method = 'action' . $this->toStudly($actionName);
        if (!method_exists($controllerClass, $method)) {
            throw new RuntimeException('Action not found: ' . $method, 404);
        }

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
