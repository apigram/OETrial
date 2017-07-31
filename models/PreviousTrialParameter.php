<?php

/**
 * @inherit
 */
class PreviousTrialParameter extends CaseSearchParameter implements DBProviderInterface
{
    public $trial;
    public $type;
    public $status;
    public $treatmentType;

    /**
     * CaseSearchParameter constructor. This overrides the parent constructor so that the name can be immediately set.
     * @param string $scenario
     */
    public function __construct($scenario = '')
    {
        parent::__construct($scenario);
        $this->name = 'previous_trial';
    }

    public function getLabel()
    {
        // This is a human-readable value, so feel free to change this as required.
        return 'Previous Trial';
    }

    /**
     * Override this function for any new attributes added to the subclass. Ensure that you invoke the parent function first to obtain and augment the initial list of attribute names.
     * @return array An array of attribute names.
     */
    public function attributeNames()
    {
        return array_merge(parent::attributeNames(), array(
                'trial',
                'type',
                'status',
                'treatmentType',
            )
        );
    }

    /**
     * Override this function if the parameter subclass has extra validation rules. If doing so, ensure you invoke the parent function first to obtain the initial list of rules.
     * @return array The validation rules for the parameter.
     */
    public function rules()
    {
        return array_merge(parent::rules(), array(
                array('type, trial, status, treatmentType', 'safe'),
            )
        );
    }

    public function renderParameter($id)
    {
        $ops = array(
            '=' => 'Has been',
            '!=' => 'Has never been',
        );

        $types = Trial::getTrialTypeOptions();

        $trials = Trial::getTrialList($this->type);

        $statusList = array(
            TrialPatient::STATUS_SHORTLISTED => 'Shortlisted in',
            TrialPatient::STATUS_ACCEPTED => 'Accepted in',
            TrialPatient::STATUS_REJECTED => 'Rejected from',
        );

        $treatmentTypeList = TrialPatient::getTreatmentTypeOptions();

        ?>

      <div class="large-10 column">
        <div class="box">
          <div class="row">
            <div class="large-2 column">
                <?php echo CHtml::label($this->getLabel(), false); ?>
            </div>
            <div class="large-2 column">
                <?php echo CHtml::activeDropDownList($this, "[$id]operation", $ops,
                    array('prompt' => 'Select One...')); ?>
                <?php echo CHtml::error($this, "[$id]operation"); ?>
            </div>
            <div class="large-2 column">
                <?php echo CHtml::activeDropDownList($this, "[$id]status", $statusList,
                    array('empty' => 'Included in')); ?>
            </div>
            <div class="large-2 column trial-type">
                <?php echo CHtml::activeDropDownList($this, "[$id]type", $types,
                    array('empty' => 'Any Trial', 'onchange' => "getTrialList(this, $this->id)")); ?>
            </div>
            <div class="large-2 column trial-list end">
                <?php echo CHtml::activeDropDownList($this, "[$id]trial", $trials,
                    array('empty' => 'Any', 'style' => 'display: none;')); ?>
            </div>
          </div>
          <br/>
          <div class="row treatment-type-container"
               <?php if ($this->type !== '' && $this->type !== null && (int)$this->type === Trial::TRIAL_TYPE_NON_INTERVENTION): ?>style="display:none" <?php endif; ?>>
            <div class="large-2 column">&nbsp;</div>
            <div class="large-2 column">
              <p style="float: right; margin: 5px">and was given</p>
            </div>
            <div class="large-2 column">
                <?php echo CHtml::activeDropDownList($this, "[$id]treatmentType", $treatmentTypeList,
                    array('empty' => 'Any')); ?>
            </div>
            <div class="large-2 column end">
              <p style="margin: 5px">treatment</p>
            </div>
          </div>
        </div>
      </div>


      <script type="text/javascript">
        function getTrialList(target, parameter_id) {
          var parameterNode = $('.parameter#' + parameter_id);

          var trialType = parseInt($(target).val());
          var trialList = parameterNode.find('.trial-list select');
          var treatmentTypeContainer = parameterNode.find('.treatment-type-container');

          // Only show the treatment type if the trial type is set to "Any" or "Intervention"
          treatmentTypeContainer.toggle(isNaN(trialType) || trialType === <?php echo Trial::TRIAL_TYPE_INTERVENTION; ?>);

          if (isNaN(trialType)) {
            trialList.empty();
            trialList.hide();
          } else {
            $.ajax({
              url: '<?php echo Yii::app()->createUrl('/OETrial/trial/getTrialList'); ?>',
              type: 'GET',
              data: {type: trialType},
              success: function (response) {
                trialList.empty();
                trialList.append(response);
                trialList.show();
              }
            });
          }
        }
      </script>

        <?php
        Yii::app()->clientScript->registerScript('GetTrials', '
          $(".previous_trial").each(function() {
            var typeElem = $(this).find(".trial-type select");
            if (typeElem.val() !== "") {
              var trialElem = $(this).find(".trial-list select");
              trialElem.show();
            }
          });
        ', CClientScript::POS_READY); // Put this in $(document).ready() so it runs on every page churn from a search.
    }

    /**
     * Generate a SQL fragment representing the subquery of a FROM condition.
     * @param $searchProvider SearchProvider The search provider. This is used to determine whether or not the search provider is using SQL syntax.
     * @return mixed The constructed query string.
     * @throws CHttpException
     */
    public function query($searchProvider)
    {
        switch ($this->operation) {
            case '=':
                $joinCondition = 'JOIN';
                if ($this->type !== '' && $this->type !== null) {
                    if ($this->trial === '') {
                        // Any intervention/non-intervention trial
                        $condition = "t.trial_type = :p_t_type_$this->id";
                    } else {
                        // specific trial
                        $condition = "t_p.trial_id = :p_t_trial_$this->id";
                    }

                } else {
                    // Any trial
                    $condition = 't_p.trial_id IS NOT NULL';
                }

                if ($this->status !== '' && $this->status !== null) {
                    $condition .= " AND t_p.patient_status = :p_t_status_$this->id";
                } else {
                    // not in any trial
                    $condition .= ' AND t_p.patient_status IS NOT NULL';
                }

                if (($this->type === '' || $this->type === null || $this->type !== (int)Trial::TRIAL_TYPE_NON_INTERVENTION)
                    && $this->treatmentType !== '' && $this->treatmentType !== null
                ) {
                    $condition .= " AND t_p.treatment_type = :p_t_treatment_type_$this->id";
                }

                break;
            case '!=':
                $joinCondition = 'LEFT JOIN';
                if ($this->type !== '' && $this->type !== null) {
                    $condition = 't_p.trial_id IS NULL OR ';
                    if ($this->trial === '') {
                        // Not in any intervention/non-intervention trial
                        $condition .= "t.trial_type != :p_t_type_$this->id";
                    } else {
                        // Not in a specific trial
                        $condition .= "t_p.trial_id != :p_t_trial_$this->id";
                    }
                } else {
                    // not in any trial
                    $condition = 't_p.trial_id IS NULL';
                }

                if ($this->status !== '' && $this->status !== null) {
                    $condition .= " AND t_p.patient_status IS NULL OR t_p.patient_status != :p_t_status_$this->id";
                } else {
                    // not in any trial
                    $condition .= ' AND t_p.patient_status IS NULL';
                }

                if (($this->type === '' || $this->type === null || $this->type !== (int)Trial::TRIAL_TYPE_NON_INTERVENTION)
                    && $this->treatmentType !== '' && $this->treatmentType !== null
                ) {
                    $condition .= " AND t_p.treatment_type IS NULL OR t_p.treatment_type != :p_t_treatment_type_$this->id";
                }

                break;
            default:
                throw new CHttpException(400, 'Invalid operator specified.');
                break;
        }

        return "
SELECT p.id 
FROM patient p 
$joinCondition trial_patient t_p 
  ON t_p.patient_id = p.id 
$joinCondition trial t
  ON t.id = t_p.trial_id
WHERE $condition";
    }

    /**
     * Get the list of bind values for use in the SQL query.
     * @return array An array of bind values. The keys correspond to the named binds in the query string.
     */
    public function bindValues()
    {
        // Construct your list of bind values here. Use the format "bind" => "value".
        $binds = array();

        if ($this->trial !== '' && $this->trial !== null) {
            $binds[":p_t_trial_$this->id"] = $this->trial;
        } elseif ($this->type !== '' && $this->type !== null) {
            $binds[":p_t_type_$this->id"] = $this->type;
        }

        if ($this->status !== '' && $this->status !== null) {
            $binds[":p_t_status_$this->id"] = $this->status;
        }

        if (($this->type === '' || $this->type === null || $this->type !== (int)Trial::TRIAL_TYPE_NON_INTERVENTION)
            && $this->treatmentType !== '' && $this->treatmentType !== null
        ) {
            $binds[":p_t_treatment_type_$this->id"] = $this->treatmentType;
        }

        return $binds;
    }
}
