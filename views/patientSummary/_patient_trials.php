<?php
/* @var $this PatientController */
?>
<section class="box patient-info js-toggle-container">
  <h3 class="box-title">Trials:</h3>
  <a href="#" class="toggle-trigger toggle-hide js-toggle">
		<span class="icon-showhide">
			Show/hide this section
		</span>
  </a>
  <a href="#" class="toggle-trigger toggle-hide js-toggle">
      <span class="icon-showhide">
        Show/hide this section
      </span>
  </a>
  </header>

  <div class="js-toggle-body">

    <table class="plain patient-data">
      <thead>
      <tr>
        <th>Trial</th>
        <th>Principle Investigator</th>
        <th>Control Status</th>
        <th>Trial Status</th>
        <th>Trial Type</th>
        <th>Date Started</th>
        <th>Date Ended</th>
      </tr>
      </thead>
      <tbody>
      <?php
      /* @var TrialPatient $trialPatient */
      foreach ($this->patient->trials as $trialPatient):
          ?>
        <tr>
          <td><?php if (Yii::app()->user->checkAccess('TaskViewTrial')): ?>
                  <?php echo CHtml::link(CHtml::encode($trialPatient->trial->name),
                      Yii::app()->controller->createUrl('/OETrial/trial/permissions',
                          array('id' => $trialPatient->trial_id))); ?>
              <?php else: ?>
                  <?php echo CHtml::encode($trialPatient->trial->name); ?>
              <?php endif; ?>
          </td>
          <td>
              <?php
              $PI = $trialPatient->trial->ownerUser;
              echo CHtml::encode($PI->last_name . ', ' . $PI->first_name);
              ?>
          </td>
          <td><?php echo $trialPatient->getTreatmentTypeForDisplay(); ?></td>
          <td><?php echo $trialPatient->getStatusForDisplay(); ?></td>
          <td><?php echo $trialPatient->trial->getTypeString(); ?></td>
          <td><?php echo $trialPatient->trial->getStartedDateForDisplay(); ?></td>
          <td><?php echo $trialPatient->trial->getClosedDateForDisplay(); ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
