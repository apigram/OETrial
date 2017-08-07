<?php
/* @var TrialController $this */
/* @var Trial $trial */
?>

<?php
$hasViewPermissions = Trial::checkTrialAccess(Yii::app()->user, $trial->id, UserTrialPermission::PERMISSION_VIEW);
$hasEditPermissions = Trial::checkTrialAccess(Yii::app()->user, $trial->id, UserTrialPermission::PERMISSION_EDIT);
$hasManagePermissions = Trial::checkTrialAccess(Yii::app()->user, $trial->id, UserTrialPermission::PERMISSION_MANAGE);
?>

<div class="large-3 column">
  <div class="box generic">

      <?php if ($hasViewPermissions): ?>
        <p>
            <?php if ($this->action->id === 'view'): ?>
              Trial Details
            <?php else: ?>
              <span class="highlight">
              <?php echo CHtml::link('Trial Details',
                  $this->createUrl('view', array('id' => $trial->id))); ?>
            </span>
            <?php endif; ?>
        </p>
      <?php endif; ?>

    <p>
        <?php if ($this->action->id === 'permissions'): ?>
          Trial Permissions
        <?php else: ?>
          <span class="highlight">
            <?php echo CHtml::link('Trial Permissions',
                $this->createUrl('permissions', array('id' => $trial->id))); ?>
          </span>
        <?php endif; ?>
    </p>

      <?php if ((int)$trial->status !== Trial::STATUS_CANCELLED && $hasEditPermissions): ?>
        <p>
          <span class="highlight">
            <?php echo CHtml::link('Add patients',
                $this->createUrl('/OECaseSearch/caseSearch', array('trial_id' => $trial->id))); ?>
          </span>
        </p>
      <?php endif; ?>

      <?php if (Yii::app()->user->checkAccess('OprnGenerateReport')): ?>
          <?php echo CHtml::beginForm($this->createUrl('report/downloadReport')); ?>
        <p>
          <span class="highlight">
              <?php echo CHtml::hiddenField('report-name', 'Cohort'); ?>
              <?php echo CHtml::hiddenField('trialID', $trial->id); ?>

              <?php echo CHtml::linkButton('Download Report'); ?>

          </span>
        </p>
          <?php echo CHtml::endForm(); ?>
      <?php endif; ?>
  </div>
</div>
