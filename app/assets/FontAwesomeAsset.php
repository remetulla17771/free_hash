<?php
namespace app\assets;


use app\helpers\AssetBundle;
use app\helpers\Html;

class FontAwesomeAsset extends AssetBundle
{
    public $basePath = '/assets';
    public $baseUrl = '/assets/fontawesome';

    public $css = [
        'index.css', // Example of using a CDN
        'brands.css', // Example of using a CDN
        'svg.css', // Example of using a CDN

    ];

    public $js = [
        'index.js',
        'brands.js',
        'fw.js',
    ];

    public static function icon($icon)
    {
        return Html::tag('i', '', ['class' => $icon]);
    }

}
