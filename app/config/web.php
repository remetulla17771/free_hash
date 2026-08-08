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
