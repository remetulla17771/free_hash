<?php
namespace app;
class Router {
    protected Request $request;

    public function __construct(Request $request = null) {
        $this->request = $request;
    }

    private function toStudly(string $name): string
    {
        // "test-test" / "test_test" -> "TestTest"
        $name = str_replace(['-', '_'], ' ', $name);
        $name = ucwords($name);
        return str_replace(' ', '', $name);
    }

    public function resolve()
    {
//        $segments = UrlManager::parseRequest($_SERVER['REQUEST_URI']);
        $segments = $this->request->getSegments();


        $lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'ru';

        // 2️⃣ сохранить язык
        $_SESSION['lang'] = $lang;


        $controllerName = $segments[0] ?? (new UrlManager)->controller;
        $actionName     = $segments[1] ?? (new UrlManager)->action;


        $controllerClass =
            'app\\controllers\\' . $this->toStudly($controllerName) . 'Controller';

        $actionMethod = 'action' . $this->toStudly($actionName);


        if (!class_exists($controllerClass)) {
            throw new \Exception('Controller not found', 404);
        }

        $controller = new $controllerClass();

        if (!method_exists($controller, $actionMethod)) {
            throw new \Exception('Action not found', 404);
        }

        // Reflection + параметры (как у тебя сейчас)
        $reflection = new \ReflectionMethod($controller, $actionMethod);
        $args = [];

        $missing = [];

        foreach ($reflection->getParameters() as $param) {
            $name = $param->getName();

            if (isset($_GET[$name])) {
                $args[] = $_GET[$name];
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } else {
                $missing[] = $name;
            }
        }

        if (!empty($missing)) {
            throw new \Exception(
                'Отсутствуют обязательные параметры: ' . implode(', ', $missing),
                400
            );
        }

        $result = $reflection->invokeArgs($controller, $args);

        if ($result instanceof \app\Response) {
            $result->send();
        }

        return $result;

    }



}
