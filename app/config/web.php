<?php

declare(strict_types=1);

return [
    'appName' => 'TinyMVC',

    'components' => [
        'user' => [
            'class' => '\app\AuthService',
        ],
        'urlManager' => [
            'class' => '\app\UrlManager',
        ],
        'arrayHelper' => [
            'class' => '\app\helpers\ArrayHelper',
        ],
        'language' => [
            'class' => '\app\helpers\I18n',
        ],
        'session' => [
            'class' => '\app\helpers\Session',
        ],
        'stringer' => [
            'class' => '\app\Request',
        ],
    ],

    'services' => [
        'userRepository' => [
            'class' => '\app\repositories\UserRepository',
        ],
    ],

    'database' => require __DIR__ . '/db.php',

    'middleware' => [],

    'modules' => [
        'admin' => [
            'class' => 'modules\\admin\\Module',
            'layout' => 'admin',
        ],
    ],

    'alias' => [
        '@web' => '/',
        '@uploads' => '/uploads/',
    ],
];
