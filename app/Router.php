<?php

declare(strict_types=1);

namespace app;

use ReflectionMethod;
use RuntimeException;

class Router
{
    protected Request $request;
    protected Container $container;

    public function __construct(Request $request, Container $container)
    {
        $this->request = $request;
        $this->container = $container;
    }

    private function toStudly(string $name): string
    {
        $name = str_replace(['-', '_'], ' ', $name);
        return str_replace(' ', '', ucwords($name));
    }

    public function resolve(): mixed
    {
        $segments = $this->request->getSegments();
        $controllerClass = $this->resolveControllerClass($segments);
        $actionName = $this->resolveActionName($segments);

        if (!class_exists($controllerClass)) {
            throw new RuntimeException('Controller not found: ' . $controllerClass, 404);
        }

        $controller = $this->container->get($controllerClass);
        $actionMethod = 'action' . $this->toStudly($actionName);

        if (!method_exists($controller, $actionMethod)) {
            throw new RuntimeException('Action not found: ' . $actionMethod, 404);
        }

        $reflection = new ReflectionMethod($controller, $actionMethod);
        $args = [];
        $missing = [];

        foreach ($reflection->getParameters() as $parameter) {
            $name = $parameter->getName();
            $value = $this->request->get($name);

            if ($value !== null) {
                $args[] = $value;
            } elseif ($parameter->isDefaultValueAvailable()) {
                $args[] = $parameter->getDefaultValue();
            } else {
                $missing[] = $name;
            }
        }

        if ($missing !== []) {
            throw new RuntimeException(
                'Отсутствуют обязательные параметры: ' . implode(', ', $missing),
                400
            );
        }

        $result = $reflection->invokeArgs($controller, $args);

        if ($result instanceof Response) {
            $result->send();
        }

        return $result;
    }

    private function resolveControllerClass(array $segments): string
    {
        $moduleId = $segments[0] ?? null;

        if ($this->isModule($moduleId)) {
            $controller = $segments[1] ?? 'default';
            return 'modules\\' . $moduleId . '\\controllers\\'
                . $this->toStudly($controller) . 'Controller';
        }

        $controller = $segments[0] ?? 'site';
        return 'app\\controllers\\' . $this->toStudly($controller) . 'Controller';
    }

    private function resolveActionName(array $segments): string
    {
        if ($this->isModule($segments[0] ?? null)) {
            return $segments[2] ?? 'index';
        }

        return $segments[1] ?? 'index';
    }

    private function isModule(?string $moduleId): bool
    {
        if ($moduleId === null || !preg_match('/^[A-Za-z0-9_]+$/', $moduleId)) {
            return false;
        }

        $modules = $this->container->get(App::class)->config('modules') ?? [];
        return isset($modules[$moduleId]);
    }
}
