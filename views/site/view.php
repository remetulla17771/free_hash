<?php

use app\helpers\DetailView;
use app\helpers\Html;


?>
<div class="bet-view">

    <h1>View</h1>

    <p>
        <?= Html::a('Update', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Delete', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data-confirm' => 'Are you sure you want to delete this item?',
        ]) ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            [
                'attribute' => 'token'
            ],
            [
                'attribute' => 'login',
                'label' => 'lll',
                'value' => function ($data) {
                    return $data->login;
                }
            ]
        ],
    ]) ?>

</div>
