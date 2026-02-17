<?php
/** @var $model app\models\News */

use app\helpers\Html;

$this->title = 'View';
?>

<h1><?= Html::encode($this->title) ?></h1>

<p>
    <?= Html::a('Back', ['/news/index'], ['class' => 'btn btn-secondary']) ?>
    <?= Html::a('Update', ['/news/update', 'id' => $model->id], ['class' => 'btn btn-warning']) ?>
    <?= Html::a('Delete', ['/news/delete', 'id' => $model->id], ['class' => 'btn btn-danger', 'onclick' => "return confirm('Delete?');"]) ?>
</p>

<table class="table table-bordered">
<tr><th>id</th><td><?= Html::encode($model->id) ?></td></tr>
<tr><th>user_id</th><td><?= Html::encode($model->user_id) ?></td></tr>
<tr><th>title</th><td><?= Html::encode($model->title) ?></td></tr>
<tr><th>content</th><td><?= Html::encode($model->content) ?></td></tr>
</table>
