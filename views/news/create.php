<?php
/** @var $model app\models\News */
use app\helpers\Html; 
$this->title = 'Create News';
?>

<h1><?= Html::encode($this->title) ?></h1>

<?= $this->renderPartial('_form', ['model' => $model, 'button' => 'Create']); ?>
