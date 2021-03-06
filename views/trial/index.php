<?php
/* @var $this TrialController */
/* @var $dataProvider CActiveDataProvider */

?>
<h1 class="badge">Trials</h1>

<div class="row">
  <div class="large-9 column">

      <?php
      $this->renderPartial('_trialList', array(
          'dataProvider' => $interventionTrialDataProvider,
          'title' => 'Intervention Trials',
          'sort_by' => $sort_by,
          'sort_dir' => $sort_dir,
      ));
      ?>


      <?php
      $this->renderPartial('_trialList', array(
          'dataProvider' => $nonInterventionTrialDataProvider,
          'title' => 'Non-Intervention Trials',
          'sort_by' => $sort_by,
          'sort_dir' => $sort_dir,
      ));
      ?>

  </div><!-- /.large-9.column -->
    <?php if (Yii::app()->user->checkAccess('TaskCreateTrial')): ?>
      <div class="large-3 column">
        <div class="box generic">
          <p><span class="highlight"><?php echo CHtml::link('Create a New Trial', array('create')) ?></span></p>
        </div>
      </div>
    <?php endif; ?>
</div>


<script type="text/javascript">
  $('#patient-grid tr.clickable').click(function () {
    window.location.href = '<?php echo Yii::app()->createUrl('/OETrial/trial/view')?>/' + $(this).attr('id').match(/[0-9]+/);
    return false;
  });
</script>