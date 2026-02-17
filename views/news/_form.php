<?php
use app\helpers\ActiveForm;

$button = isset($button) ? $button : 'Save';
?>

<?php ActiveForm::begin('post'); ?>

<?= ActiveForm::field($model, 'user_id') ?>
<?= ActiveForm::field($model, 'title') ?>
<?= ActiveForm::field($model, 'content') ?>

<?= ActiveForm::submitButton($button, ['class' => 'btn btn-primary mt-3']) ?>

<?php ActiveForm::end(); ?>
