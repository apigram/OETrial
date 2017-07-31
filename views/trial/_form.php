<?php
/* @var $this TrialController */
/* @var $model Trial */
/* @var $form CActiveForm */
?>

<div class="form">

    <?php $form = $this->beginWidget('CActiveForm', array(
        'id' => 'trial-form',
        // Please note: When you enable ajax validation, make sure the corresponding
        // controller action is handling ajax validation correctly.
        // There is a call to performAjaxValidation() commented in generated controller code.
        // See class documentation of CActiveForm for details on this.
        'enableAjaxValidation' => false,
    )); ?>

  <p class="note text-right">Fields with <span class="required">*</span> are required.</p>

    <?php echo $form->errorSummary($model); ?>

  <div class="row field-row">
    <div class="large-6 column">
      <div class="row field-row">
          <?php echo $form->labelEx($model, 'name'); ?>
          <?php echo $form->textField($model, 'name', array('size' => 64, 'maxlength' => 64)); ?>
          <?php echo $form->error($model, 'name'); ?>
      </div>
    </div>
  </div>
  <div class="row field-row">
    <div class="large-6 column">
      <div class="row field-row">
          <?php echo $form->labelEx($model, 'external_reference'); ?>
          <?php echo $form->textField($model, 'external_reference', array('size' => 100, 'maxlength' => 100)); ?>
          <?php echo $form->error($model, 'external_reference'); ?>
      </div>
    </div>
  </div>
  <div class="row field-row">
      <?php echo $form->labelEx($model, 'description'); ?>
      <?php echo $form->textArea($model, 'description'); ?>
      <?php echo $form->error($model, 'description'); ?>
  </div>

  <div class="row field-row">
    <div class="large-6 column">
      <div class="row field-row">
        <div class="large-3 column">
            <?php echo $form->labelEx($model, 'trial_type'); ?>
        </div>
        <div class="large-6 column end">
            <?php foreach (Trial::model()->getTrialTypeOptions() as $trial_type => $type_label): ?>
              <div class="row field-row text">
                <label>
                    <?php echo $form->radioButton($model, 'trial_type',
                        array('value' => $trial_type, 'uncheckValue' => null)); ?>
                    <?php echo $type_label; ?>
                </label>
              </div>
            <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="row buttons text-right">
      <?php echo CHtml::submitButton($model->isNewRecord ? 'Create' : 'Save'); ?>
  </div>

    <?php $this->endWidget(); ?>

</div><!-- form -->