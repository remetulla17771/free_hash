<?php

return [
    'appName' => 'Free Hash',
    'components' => [
        'user' => [
            'class' => '\app\AuthService'
        ],
        'urlManager' => [
            'class' => '\app\UrlManager'
        ],
        'response' => [
            'class' => '\app\Response'
        ],
        'arrayHelper' => [
            'class' => '\app\helpers\ArrayHelper'
        ],
        'lang' => [
            'class' => '\app\helpers\I18n'
        ],
        'session' => [
            'class' => '\app\helpers\Session'
        ]
    ],
    'alias' => [
        '@web' => '/',
        '@uploads' => '/uploads/'
    ]
];