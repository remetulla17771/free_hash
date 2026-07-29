<?php

/**
 * @var $models app\models\User
 * @var $pagination app\helpers\Pagination
 * @var $this app\
 */

use app\assets\FontAwesomeAsset;
use app\helpers\GridView;
use app\helpers\Html;
use app\helpers\Modal;

?>

<div>
    Language: <?= $this->language->get() ?>
</div>

<div>

    <?= \app\helpers\Tab::widget([
        'items' => [
            [
                'title' => 'Tab 1',
                'content' => 'Tab 1 Content',
            ],
            [
                'title' => 'Tab 2',
                'content' => 'Tab 2 Content',
            ],
            [
                'title' => 'Tab 3',
                'content' => 'Tab 3 Content',
            ]

        ]
    ]) ?>

</div>

<?= GridView::widget([
    'dataProvider' => $models,
    'pagination' => $pagination,
    'columns' => [
        [
            'label' => 'Select',
            'value' => function ($data) {
                return Html::tag('input', $data->id, [
                    'class' => 'form-checkbox',
                    'type' => 'checkbox',
                    'value' => $data->id
                ]);
            }
        ],
        'id',
        'login',
        'password',
        [
            'label' => 'News',
            'value' => function ($data) {
                $news = $data->news;

                if ($news) {
                    $str = '';
                    foreach ($news as $n) {

                        $str .= $n->id . ") " . $n->title . '<br>';

                    }

                    return $str;
                }

            }
        ],
        [
            'attribute' => 'token',
            'value' => function ($data) {
                return "Token: " . ($data->token + 1);
            },
        ],
        [
            'label' => 'Action',
            'value' => function ($data) {
                return Html::a(FontAwesomeAsset::icon('fas fa-pencil'), ['/site/update', 'id' => $data->id], ['class' => 'btn btn-warning'])
                    . " " . Html::a(FontAwesomeAsset::icon('fas fa-eye'), ['/site/view', 'id' => $data->id], ['class' => 'btn btn-success'])
                    . " " . Html::a(FontAwesomeAsset::icon('fas fa-trash'), ['/site/delete', 'id' => $data->id], ['class' => 'btn btn-danger', 'data-confirm' => 'Are you sure you want to delete this?']);
//                return '<a href="/site/user?id=' . $data->id . '">Update</a> | <a href="/user/delete?id=' . $data->id . '">Delete</a>';
            }
        ]
    ]
]);
?>

<?= \app\helpers\ElevenLabsAgent::widget("agent_7001kp5fqdq6fktb7vc1wz9c54ze") ?>

<div>
    <button class="btn btn-primary" modal-id="modal_1">Open modal with ajax</button>
    <button class="btn btn-primary" modal-id="modal_2">Open modal without ajax</button>

</div>

<?php Modal::begin([
    'id' => 'modal_1',
    'title' => 'Modal 1',
    'ajax' => '/site/modal'
]); ?>
Loading...
<?php Modal::end(); ?>


<?php Modal::begin([
    'id' => 'modal_2',
    'title' => 'Modal 2',
]); ?>
Modal window 2
<?php Modal::end(); ?>

<script>
    document.querySelectorAll('.form-checkbox').forEach(ch => {
        ch.addEventListener('change', () => {
            const selected = selectedAll('id');
            console.log(selected);
        });
    });

    function selectedAll() {
        return [...document.querySelectorAll('.form-checkbox:checked')].map(c => parseInt(c.value));
    }

</script>

<script>

    class User {
        // Необязательно, но можно объявить свойство (Public Field)
        name;

        // Исправлено: constructor пишется именно так
        constructor(name) {
            this.name = name;
        }

        setName(name) {
            this.name = name;
        }
    }

    class BankAccount {
        amount

        // Конструктор для инициализации пользователя в аккаунте
        constructor(user) {
            this.user = user;
        }

        setAmount(amount) {
            this.amount = amount
        }
    }

</script>