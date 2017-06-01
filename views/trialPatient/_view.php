<?php
/* @var $this TrialPatientController */
/* @var $data TrialPatient */
?>

<div class="trial-patient-container">

    <?php echo CHtml::link(CHtml::encode($data->patient->getFullName()), array('//patient/view', 'id' => $data->patient_id)); ?>
    <br/>

    <b><?php echo CHtml::encode($data->getAttributeLabel('external_trial_identifier')); ?>:</b>
    <?php echo CHtml::encode($data->external_trial_identifier); ?>
    <br/>


</div>


<div class="result box generic">
    <h3 class="box-title">
        <?php echo CHtml::link($data->patient->contact->last_name . ', ' . $data->patient->contact->first_name . ($data->patient->is_deceased ? ' (Deceased)' : ''), array('/patient/view', 'id' => $data->patient->id)); ?>
    </h3>
    <div class="row data-row">
        <div class="large-12 column">
            <?php
            echo $data->patient->gender . ' ' . '(' . $data->patient->getAge() . ')';
            ?>
        </div>
    </div>

    <div class="row data-row">
        <div class="large-12 column">
            <div>
                <a href="#collapse-section_<?php echo $data->id . '_diagnosis'; ?>" class="section-toggle"
                        data-show-label="Show Diagnoses" data-hide-label="Hide Diagnoses" rel="nofollow">Show Diagnoses
                </a>
            </div>
            <div id="collapse-section_<?php echo $data->id . '_diagnosis'; ?>" style="display:none">
                <div class="diagnoses detail row data-row">
                    <div class="large-12 column">
                        <table>
                            <thead>
                            <tr>
                                <th>Diagnosis</th>
                                <th>Date</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($data->patient->secondarydiagnoses as $diagnosis): ?>
                                <tr>
                                    <td><?php echo $diagnosis->disorder->fully_specified_name; ?></td>
                                    <td><?php echo $diagnosis->dateText; ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="row data-row">
        <div class="large-12 column">
            <div>
                <a href="#collapse-section_<?php echo $data->id . '_medication'; ?>" class="section-toggle"
                        data-show-label="Show Medications" data-hide-label="Hide Medications" rel="nofollow">Show Medications
                </a>
            </div>
            <div id="collapse-section_<?php echo $data->id . '_medication'; ?>" style="display:none">
                <div class="medications detail row data-row">
                    <div class="large-12 column">
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
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($data->patient_status == TrialPatient::STATUS_SHORTLISTED) {
        echo CHtml::link('Accept Patient',
            'javascript:void(0)',
            array(
                'onclick' => "changePatientStatus(this, $data->id, " . TrialPatient::STATUS_ACCEPTED . ")", 'class' => 'accept-patient-link'
            )
        );
    }

    if ($data->patient_status == TrialPatient::STATUS_SHORTLISTED || $data->patient_status == TrialPatient::STATUS_ACCEPTED) {
        echo CHtml::link('Reject Patient',
            'javascript:void(0)',
            array(
                'onclick' => "changePatientStatus(this, $data->id, " . TrialPatient::STATUS_REJECTED . ")", 'class' => 'accept-patient-link'
            )
        );
    }

    if ($data->patient_status == TrialPatient::STATUS_REJECTED) {
        echo CHtml::link('Shortlist Patient',
            'javascript:void(0)',
            array(
                'onclick' => "changePatientStatus(this, $data->id, " . TrialPatient::STATUS_SHORTLISTED . ")", 'class' => 'accept-patient-link'
            )
        );
    } ?>
</div>

