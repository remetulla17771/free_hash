<?php

declare(strict_types=1);

namespace app;

use app\helpers\I18n;
use Throwable;

final class App
{
    public Request $request;
    public Response $response;
    public Router $router;
    public Container $container;
    public Dispatcher $dispatcher;
    public MiddlewareDispatcher $middleware;
    public ModuleManager $modules;
    public Db $db;

    public string $title = 'My App';
    private array $configFile;

    public function __construct(?Container $container = null, ?Request $request = null)
    {
        $this->container = $container ?? new Container();
        $this->configFile = require __DIR__ . '/config/web.php';

        $this->container->singleton(Container::class, $this->container);
        $this->container->singleton(self::class, $this);

        $this->request = $request ?? $this->container->get(HttpRequestFactory::class)->create();
        $this->response = new Response();

        $this->container->singleton(Request::class, $this->request);
        $this->container->singleton(Response::class, $this->response);
        $this->container->alias('request', Request::class);
        $this->container->alias('response', Response::class);

        $this->db = Db::fromConfig($this->configFile['database'] ?? []);
        $this->container->singleton(Db::class, $this->db);
        $this->container->singleton(ModelFactory::class, new ModelFactory($this->container));

        $this->registerComponents($this->configFile['components'] ?? []);
        $this->registerServices($this->configFile['services'] ?? []);

        $this->modules = new ModuleManager($this->container, $this->configFile['modules'] ?? []);
        $this->container->singleton(ModuleManager::class, $this->modules);

        $this->container->singleton(ViewRenderer::class, new ViewRenderer(__DIR__ . '/../views'));
        $this->router = new Router($this->request, $this->modules);
        $this->container->singleton(Router::class, $this->router);
        $this->dispatcher = new Dispatcher($this->container);
        $this->container->singleton(Dispatcher::class, $this->dispatcher);

        $middleware = $this->configFile['middleware'] ?? [];
        $this->middleware = new MiddlewareDispatcher($this->container, $middleware);
        $this->container->singleton(MiddlewareDispatcher::class, $this->middleware);
    }

    private function registerComponents(array $components): void
    {
        foreach ($components as $id => $config) {
            $className = $config['class'] ?? null;
            if (!is_string($className) || !class_exists($className)) {
                throw new \RuntimeException("Component class '{$className}' not found.");
            }

            $instance = $this->createConfiguredService($className, $config['options'] ?? []);
            $this->container->singleton($className, $instance);
            $this->container->singleton((string) $id, $instance);

            if (property_exists($this, (string) $id)) {
                $this->{$id} = $instance;
            }
        }
    }

    private function registerServices(array $services): void
    {
        foreach ($services as $id => $config) {
            $className = $config['class'] ?? null;
            if (!is_string($className) || !class_exists($className)) {
                throw new \RuntimeException("Service class '{$className}' not found.");
            }

            $this->container->singleton($className, $this->createConfiguredService($className, $config['options'] ?? []));
            $this->container->alias((string) $id, $className);
        }
    }

    private function createConfiguredService(string $className, array $options): object
    {
        $instance = $this->container->build($className);

        foreach ($options as $property => $value) {
            if (!property_exists($instance, $property)) {
                throw new \RuntimeException("Property '{$property}' does not exist in component class '{$className}'.");
            }
            $instance->{$property} = $value;
        }

        return $instance;
    }

    public function t(string $category, string $message, array $params = []): string
    {
        return I18n::t($category, $message, $params);
    }

    public function dd(mixed $value, bool $die = true): void
    {
        echo '<pre>';
        print_r($value);
        echo '</pre>';
        if ($die) die;
    }

    public function config(?string $keyName = null): mixed
    {
        return $keyName === null ? $this->configFile : ($this->configFile[$keyName] ?? null);
    }

    public static function powered(): string
    {
        return '<a href="https://vk.com/deepn9x">deepn9x</a>';
    }

    public function run(): Response|string
    {
        try {
            return $this->middleware->handle($this->request, function (): Response {
                $route = $this->router->match();
                $result = $this->dispatcher->dispatch($route);
                return $result instanceof Response ? $result : Response::html((string) $result);
            });
        } catch (Throwable $e) {
            $code = (int) $e->getCode();
            if ($code < 400 || $code > 599) $code = 500;
            ErrorHandler::log($e, $code);
            return Response::error($code, $e->getMessage());
        }
    }
}
