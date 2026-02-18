<?php
/** @var $models app\models\News[] */

/** @var $pagination \app\helpers\Pagination */ 
use app\helpers\GridView;
use app\helpers\Html;

use app\helpers\LinkPager;

$this->title = 'News list';
?>

<h1><?= Html::encode($this->title) ?></h1>

<p>
    <?= Html::a('Create News', ['/news/create'], ['class' => 'btn btn-primary']) ?>
</p>

<?= GridView::widget([
    'dataProvider' => $models,
    'pagination' => $pagination,
    'columns' => [
        'id',
        'user_id',
        'title',
        [
            'label' => 'Action',
            'value' => function ($data) {
                $id = $data->id;
                return Html::a('View', ['/news/view', 'id' => $id], ['class' => 'btn btn-success btn-sm'])
                    . ' ' . Html::a('Update', ['/news/update', 'id' => $id], ['class' => 'btn btn-warning btn-sm'])
                    . ' ' . Html::a('Delete', ['/news/delete', 'id' => $id], ['class' => 'btn btn-danger btn-sm', 'data-confirm' => "Are you sure you want to delete this?"]);
            }
        ],
    ]
]); ?>
