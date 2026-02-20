<?php
use app\helpers\ActiveForm;

$button = isset($button) ? $button : 'Save';
?>

<?php ActiveForm::begin('post'); ?>

<?= ActiveForm::dropdown($model, 'user_id', \app\helpers\ArrayHelper::map(\app\models\User::find()->all(), 'id', 'login')) ?>
<?= ActiveForm::field($model, 'title') ?>
<?= ActiveForm::field($model, 'content') ?>

<?= ActiveForm::submitButton($button, ['class' => 'btn btn-primary mt-3']) ?>

<?php ActiveForm::end(); ?>
