<?php
/** @var $model app\models\News */

use app\helpers\Html;
use app\helpers\DetailView;

$this->title = 'View';
?>

<h1><?= Html::encode($this->title) ?></h1>

<p>
    <?= Html::a('Back', ['/news/index'], ['class' => 'btn btn-secondary']) ?>
    <?= Html::a('Update', ['/news/update', 'id' => $model->id], ['class' => 'btn btn-warning']) ?>
    <?= Html::a('Delete', ['/news/delete', 'id' => $model->id], ['class' => 'btn btn-danger', 'data-confirm' => "Are you sure you want to delete this?"]) ?>
</p>

<?= DetailView::widget([
    'model' => $model,
    'attributes' => [
        'id',
        'user_id',
        'title',
        'content',

    ],
]); ?>
