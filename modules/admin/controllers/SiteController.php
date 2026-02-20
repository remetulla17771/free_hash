<?php

namespace modules\admin\controllers;

use app\Controller;

class SiteController extends Controller
{
    public function actionIndex()
    {
        return $this->render('index');
    }
}
