<?php
namespace app\controllers;

use app\Controller;
use app\helpers\Alert;
use app\models\News;

class NewsController extends Controller
{
    public function actionIndex()
    {
        $models = News::find()->all();
        return $this->render('index', ['models' => $models]);
    }

    public function actionView($id)
    {
        $model = News::find()->where(['id' => (int)$id])->one();
        if (!$model) throw new \Exception('Not found', 404);
        return $this->render('view', ['model' => $model]);
    }

    public function actionCreate()
    {
        $model = new News();
        if ($this->request->isPost() && $model->load($this->request->post())) {
            $model->save();
            Alert::add('success', 'Created');
            return $this->redirect(['news/index']);
        }
        return $this->render('create', ['model' => $model]);
    }

    public function actionUpdate($id)
    {
        $model = News::find()->where(['id' => (int)$id])->one();
        if (!$model) throw new \Exception('Not found', 404);
        if ($this->request->isPost() && $model->load($this->request->post())) {
            $model->save();
            Alert::add('success', 'Updated');
            return $this->redirect(['news/view', 'id' => $model->id]);
        }
        return $this->render('update', ['model' => $model]);
    }

    public function actionDelete($id)
    {
        $model = News::find()->where(['id' => (int)$id])->one();
        if ($model) {
            $model->delete();
            Alert::add('warning', 'Deleted');
        } else {
            Alert::add('danger', 'Not found');
        }
        return $this->redirect(['news/index']);
    }
}
