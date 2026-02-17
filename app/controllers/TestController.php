<?php
namespace app\controllers;

use app\Controller;

class TestController extends Controller
{
    public function actionIndex()
    {
        return $this->render('index');
    }
}
