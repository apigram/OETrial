<?php
/* @var $this TrialPatientController */
/* @var $data TrialPatient */
/* @var $userPermission integer */

$isInAnotherInterventionTrial = $data->patient->isCurrentlyInInterventionTrial($data->trial_id);
$canEditPatient = Trial::checkTrialAccess(Yii::app()->user, $data->trial_id, UserTrialPermission::PERMISSION_EDIT);

$warnings = array();

if ($isInAnotherInterventionTrial) {
    $warnings = array_merge($warnings, array('<li>This patient is already in an Intervention trial</li>'));
}

if ($data->patient->hasDrugAllergy()) {
    $warnings = array_merge($warnings, array('<li>This patient has allergies</li>'));
}

?>
<tr>
  <td> <!-- Warnings -->
      <?php if (count($warnings) > 0): ?>
        <span class="warning">
          <span class="icon icon-alert icon-alert-warning"></span>
          <span class="quicklook warning">
            <ul>
              <?php echo implode($warnings); ?>
            </ul>
          </span>
        </span>
      <?php endif; ?>
  </td>
  <td> <!-- Name -->
      <?php echo CHtml::link(
          CHtml::encode($data->patient->last_name . ', ' . $data->patient->first_name . ($data->patient->is_deceased ? ' (Deceased)' : '')),
          array('/patient/view', 'id' => $data->patient->id),
          array('target' => '_blank')
      ); ?>
  </td>
  <td> <!-- Gender -->
      <?php echo $data->patient->getGenderString(); ?>
  </td>
  <td> <!-- Age -->
      <?php echo $data->patient->getAge(); ?>
  </td>
  <td> <!-- Ethnicity -->
      <?php echo CHtml::encode($data->patient->getEthnicGroupString()); ?>
  </td>
  <td> <!-- External Reference -->
    <span id="ext-trial-id-<?php echo $data->id; ?>">
              <?php echo CHtml::encode($data->external_trial_identifier); ?>
            </span>
      <?php if ($canEditPatient && $data->trial->status != Trial::STATUS_CANCELLED): ?>

        <input id="ext-trial-id-form-<?php echo $data->id; ?>" type="text"
               value="<?php echo CHtml::encode($data->external_trial_identifier); ?>" width="50"
               style="display:none;"/>
      <?php endif; ?>

      <?php if ($canEditPatient && $data->trial->status != Trial::STATUS_CANCELLED): ?>

        <a id="ext-trial-id-edit-<?php echo $data->id; ?>" href="javascript:void(0)"
           onclick="editExternalTrialIdentifier(<?php echo $data->id; ?>)">edit</a>
        <a id="ext-trial-id-save-<?php echo $data->id; ?>" href="javascript:void(0)"
           onclick="saveExternalTrialIdentifier(<?php echo $data->id; ?>)" style="display:none;">save</a>
        <img class="loader" src="<?php echo Yii::app()->assetManager->createUrl('img/ajax-loader.gif') ?>"
             alt="loading..." style="display: none;"/>
      <?php endif; ?>
  </td>
  <td> <!-- Treatment Type -->
      <?php if ($data->trial->status == Trial::STATUS_CLOSED): ?>

          <?php if ($canEditPatient): ?>
              <?php echo CHtml::dropDownList(
                  'treatment-type',
                  $data->treatment_type,
                  TrialPatient::getTreatmentTypeOptions(),
                  array(
                      'id' => "treatment-type-$data->id",
                      'data-trial-patient-id' => $data->id,
                      'onchange' => 'updateTreatmentType(this)',
                  )
              ); ?>
          <img id="treatment-type-loader-<?php echo $data->id; ?>"
               src="<?php echo Yii::app()->assetManager->createUrl('img/ajax-loader.gif') ?>" alt="Working..."
               class="hidden"/>
          <img id="treatment-type-success-<?php echo $data->id; ?>"
               src="<?php echo Yii::app()->assetManager->createUrl('img/_elements/icons/event-optional/element-added.png'); ?>"
               alt="Success" class="hidden"/>
          <?php else: /* can't edit */ ?>
              <?php echo $data->getTreatmentTypeForDisplay(); ?>
          <?php endif; ?>
      <?php endif; ?>
  </td>
  <td> <!-- Diagnoses and Medication show/hide actions -->
      <?php if (count($data->patient->secondarydiagnoses) > 0): ?>
        <a href="javascript:void(0)"
           data-show-label="Show&nbsp;Diagnoses" data-hide-label="Hide&nbsp;Diagnoses"
           onclick="toggleSection(this, '#collapse-section_<?php echo $data->patient->id . '_diagnoses'; ?>');">Show&nbsp;Diagnoses
        </a>
      <?php endif; ?>
      <?php if (count($data->patient->medications) > 0): ?>
        <a href="javascript:void(0)"
           data-show-label="Show&nbsp;Medication" data-hide-label="Hide&nbsp;Medication"
           onclick="toggleSection(this, '#collapse-section_<?php echo $data->patient->id . '_medication'; ?>');">Show&nbsp;Medication
        </a>
      <?php endif; ?>
  </td>
  <td> <!-- Accept/Reject/Shortlist actions -->
      <?php if ($canEditPatient && ($data->trial->status == Trial::STATUS_OPEN || $data->trial->status == Trial::STATUS_IN_PROGRESS)): ?>

          <?php if ($data->patient_status == TrialPatient::STATUS_SHORTLISTED): ?>

          <a href="javascript:void(0)"
             onclick="changePatientStatus(this, <?php echo $data->id; ?>, <?php echo TrialPatient::STATUS_ACCEPTED; ?>)"
             class="accept-patient-link"
             <?php if ($isInAnotherInterventionTrial): ?>style="color: #ad1515;"<?php endif; ?> >Accept
          </a>
          <?php endif; ?>

          <?php if ($data->patient_status == TrialPatient::STATUS_SHORTLISTED || $data->patient_status == TrialPatient::STATUS_ACCEPTED): ?>
          <a href="javascript:void(0)"
             onclick="changePatientStatus(this, <?php echo $data->id; ?>, <?php echo TrialPatient::STATUS_REJECTED; ?>)"
             class="accept-patient-link">Reject
          </a>
          <?php endif; ?>

          <?php if ($data->patient_status == TrialPatient::STATUS_REJECTED): ?>
          <span style="white-space: nowrap;">
            <a href="javascript:void(0)"
               onclick="changePatientStatus(this, <?php echo $data->id; ?>, <?php echo TrialPatient::STATUS_SHORTLISTED; ?>)"
               class="accept-patient-link">Re-Shortlist
            </a>
          </span>
          <?php endif; ?>

        <img class="loader" id="action-loader-<?php echo $data->id; ?>"
             src="<?php echo Yii::app()->assetManager->createUrl('img/ajax-loader.gif') ?>"
             alt="loading..." style="display: none;"/>
      <?php endif; ?>
  </td>
</tr>
<!-- Collapsible diagnoses section -->
<tr id="collapse-section_<?php echo $data->patient->id . '_diagnoses'; ?>" style="display:none">
  <td colspan="9">

    <table>
      <thead>
      <tr>
        <th>Diagnosis</th>
        <th>Confirmed/Unconfirmed</th>
        <th>Date</th>
      </tr>
      </thead>
      <tbody>
      <?php foreach ($data->patient->secondarydiagnoses as $diagnosis): ?>
        <tr>
          <td><?php echo $diagnosis->disorder->fully_specified_name; ?></td>
          <td><?php echo $diagnosis->isConfirmed() ? 'Confirmed' : 'Unconfirmed'; ?></td>
          <td><?php echo $diagnosis->dateText; ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

  </td>
</tr>
<!-- Collapsible medication section -->
<tr id="collapse-section_<?php echo $data->patient->id . '_medication'; ?>" style="display:none">
  <td colspan="9">

    <table>
      <thead>
      <tr>
        <th>Medication</th>
        <th>Administration</th>
        <th>Date From</th>
        <th>Date To</th>
      </tr>
      </thead>
      <tbody>
      <?php foreach ($data->patient->medications as $medication): ?>
        <tr>
          <td><?php echo $medication->getDrugLabel(); ?></td>
          <td><?= $medication->dose ?>
              <?= isset($medication->route->name) ? $medication->route->name : '' ?>
              <?= $medication->option ? "({$medication->option->name})" : '' ?>
              <?= isset($medication->frequency->name) ? $medication->frequency->name : '' ?></td>
          <td><?php echo Helper::formatFuzzyDate($medication->start_date); ?></td>
          <td><?php echo isset($medication->end_date) ? Helper::formatFuzzyDate($medication->end_date) : ''; ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

  </td>
</tr>
