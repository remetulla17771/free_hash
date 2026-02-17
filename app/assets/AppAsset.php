<?php
namespace app\assets;



use app\helpers\AssetBundle;

class AppAsset extends AssetBundle
{
    public $basePath = '/assets';
    public $baseUrl = '/assets';

    public $css = [
        'css/site.css',
    ];

    public $js = [
        'js/site.js',
    ];

    public $depends = [
        'app\assets\BootstrapAsset',
        'app\assets\FontAwesomeAsset'
    ];
}
