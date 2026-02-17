<?php
namespace app\controllers;
use app\App;
use app\AuthService;
use app\Controller;
use app\helpers\Alert;
use app\helpers\MetaTagManager;
use app\helpers\Pagination;
use app\models\Shezhire;
use app\models\User;
use app\Response;
use app\vendor\privacy\Privacy;


class SiteController extends Controller {

//    public string $layout = 'new';
    public function actionIndex() {
        
        MetaTagManager::register(['asdf' => 'cofg']);

        $page = (int)$this->request->get('page');
        $page = max(1, $page);

        $query = User::find()->orderBy(['id' => 'DESC']);

        $total = $query->count();
        $pagination = new Pagination($total, 3, $page);

        $models = $query
            ->limit($pagination->pageSize)
            ->offset($pagination->getOffset())
            ->all();

        return $this->render('index', [
            'models' => $models,
            'pagination' => $pagination,
        ]);
    }

    public function actionView($id)
    {
        $model = User::find()->where(['id' => $id])->one();

        return $this->render('view', [
            'model' => $model
        ]);
    }

    public function actionAdd()
    {

        if($this->user->isGuest()){
            Alert::add('danger', 'Please login');
            return $this->redirect('/');
        }
        $model = new User();

        if($this->request->isPost() && $model->load($this->request->post())){
            $model->save();
            return $this->redirect('/');

        }

        return $this->render('add', [
            'model' => $model
        ]);

    }

    public function actionDelete($id)
    {

        $user = User::find()->where(['id' => $id])->one();

        if(!$user){
            Alert::add("danger", "Cant find row");
            return $this->redirect(['site/index']);
        }

        $user->delete();

        Alert::add("warning", "Успешно удалено");

        $this->redirect('/');

    }

    public function actionLogin()
    {

        $model = new User();

        if($this->request->isPost()){
            $ok = $this->user::login($_POST['login'], $_POST['password']);
            if ($ok) {
                Alert::add('success', 'Logged');
                return Response::redirect(['site/index']);
            }
            else{
                Alert::add('danger', 'Wrong');
                return Response::redirect(['site/login']);
            }
        }

        return $this->render('login', [
            'model' => $model
        ]);
    }

    public function actionUpdate($id)
    {
        $model = User::findOne($id);

        if(!$model){
            throw new \Exception("Не удалось найти", 256);
        }

        if($this->request->isPost() && $model->load($this->request->post())){
            $model->save();

            return $this->redirect('/');

        }

        return $this->render('update', [
            'model' => $model
        ]);
    }


    public function actionLogout()
    {
        $this->user->logout();
        Alert::add('warning', 'Logged out');
        return $this->redirect('/');
    }

    public function actionModal()
    {
        return $this->renderPartial('modal');
    }


}
