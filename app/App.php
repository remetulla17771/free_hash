<?php

namespace app;

use app\controllers\PrivacyController;
use app\helpers\I18n;
use app\ErrorHandler;

class App
{
    /**
     * @var mixed|null
     */
    public static $language;
    public Request $request;
    public Router $router;

    public $title = "My App";
    private $configFile;

    public function __construct()
    {
        $this->request = new Request();
        $this->router = new Router($this->request);

        $this->configFile = require "config/web.php";

        foreach ($this->configFile['components'] as $key => $value) {

            $this->$key = new $value['class']();

        }

//
//        $this->controller = $this->request->getSegments()[0];
//        $this->action = $this->request->getSegments()[1];


    }

    public function t($category, $message, $params = [])
    {
        return I18n::t($category, $message, $params);
    }

    public function dd($arr, $die = 1)
    {


        if ($die == 1) {
            echo "<pre>";
            print_r($arr);
            echo "</pre>";
            die;
        } else {
            echo "<pre>";
            print_r($arr);
            echo "</pre>";
        }

    }

    public function config($keyName)
    {
        return $this->configFile[$keyName];
    }

    public static function powered()
    {
        return '<a href="https://vk.com/deepn9x">Yii2 MVC Clone App</a>';
    }

    public function run()
    {
        try {

            return $this->router->resolve();
        } catch (\Throwable $e) {

            $code = $e->getCode();

            $controller = new \app\controllers\ErrorController();


            // ✅ РЕГИСТРИРУЕМ ОБРАБОТЧИК ОШИБОК
            ErrorHandler::log($e, $code);

            return $controller->actionIndex(
                $code,
                $e->getMessage(),
            );
        }
    }


    // ------------------------
    // Методы для alias
    // ------------------------
    public function getAlias($key)
    {
        return $this->config('alias')[$key] ?? null;
    }


}
