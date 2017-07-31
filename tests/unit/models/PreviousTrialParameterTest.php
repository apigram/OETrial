<?php

/**
 * Class PreviousTrialParameterTest
 */
class PreviousTrialParameterTest extends CDbTestCase
{
    /**
     * @var PreviousTrialParameter $object
     */
    protected $object;
    protected $searchProvider;
    protected $invalidProvider;

    public static function setUpBeforeClass()
    {
        Yii::app()->getModule('OECaseSearch');
    }

    protected function setUp()
    {
        $this->object = new PreviousTrialParameter();
        $this->searchProvider = new DBProvider('mysql');
        $this->object->id = 0;
    }

    protected function tearDown()
    {
        unset($this->object, $this->searchProvider); // start from scratch for each test.
    }

    /**
     * @covers PreviousTrialParameter::query()
     */
    public function testQuery()
    {
        $correctOps = array(
            '=',
            '!=',
        );
        $invalidOps = array(
            'NOT LIKE',
        );

        $types = array(
            Trial::TRIAL_TYPE_NON_INTERVENTION,
            Trial::TRIAL_TYPE_INTERVENTION,
            '',
        );

        $trials = array(
            1,
            '',
        );

        $statusList = array(
            '',
            TrialPatient::STATUS_SHORTLISTED,
            TrialPatient::STATUS_ACCEPTED,
            TrialPatient::STATUS_REJECTED,
        );

        // Ensure the query is correct for each operator and returns a set of results.
        foreach ($correctOps as $operator) {
            $this->object->operation = $operator;
            foreach ($types as $type) {
                $this->object->type = $type;
                foreach ($trials as $trial) {
                    $this->object->trial = $trial;
                    foreach ($statusList as $status) {
                        $this->object->status = $status;
                        foreach (TrialPatient::getAllowedTreatmentTypeRange() as $treatmentType) {
                            $this->object->treatmentType = $treatmentType;
                            if ($operator === '=') {
                                $joinCondition = 'JOIN';
                                if ($this->object->type !== '' && $this->object->type !== null) {
                                    if ($this->object->trial === '') {
                                        // Any intervention/non-intervention trial
                                        $condition = 't.trial_type = :p_t_type_0';
                                    } else {
                                        // specific trial
                                        $condition = 't_p.trial_id = :p_t_trial_0';
                                    }

                                } else {
                                    // Any trial
                                    $condition = 't_p.trial_id IS NOT NULL';
                                }
                                if ($this->object->status !== '') {
                                    $condition .= ' AND t_p.patient_status = :p_t_status_0';
                                } else {
                                    // not in any trial
                                    $condition .= ' AND t_p.patient_status IS NOT NULL';
                                }

                                if (($this->object->type === '' || $this->object->type === null || $this->object->type !== (int)Trial::TRIAL_TYPE_NON_INTERVENTION)
                                    && $this->object->treatmentType !== '' && $this->object->treatmentType !== null
                                ) {
                                    $condition .= ' AND t_p.treatment_type = :p_t_treatment_type_0';
                                }

                            } elseif ($operator === '!=') {
                                $joinCondition = 'LEFT JOIN';
                                if ($this->object->type !== '' && $this->object->type !== null) {
                                    if ($this->object->trial === '') {
                                        // Not in any intervention/non-intervention trial
                                        $condition = 't_p.trial_id IS NULL OR t.trial_type != :p_t_type_0';
                                    } else {
                                        // Not in a specific trial
                                        $condition = 't_p.trial_id IS NULL OR t_p.trial_id != :p_t_trial_0';
                                    }
                                } else {
                                    // not in any trial
                                    $condition = 't_p.trial_id IS NULL';
                                }

                                if ($this->object->status !== '') {
                                    $condition .= ' AND t_p.patient_status IS NULL OR t_p.patient_status != :p_t_status_0';
                                } else {
                                    // not in any trial
                                    $condition .= ' AND t_p.patient_status IS NULL';
                                }

                                if (($this->object->type === '' || $this->object->type === null || $this->object->type !== (int)Trial::TRIAL_TYPE_NON_INTERVENTION)
                                    && $this->object->treatmentType !== '' && $this->object->treatmentType !== null
                                ) {
                                    $condition .= ' AND t_p.treatment_type IS NULL OR t_p.treatment_type != :p_t_treatment_type_0';
                                }
                            }
                            $sqlValue = "
SELECT p.id 
FROM patient p 
$joinCondition trial_patient t_p 
  ON t_p.patient_id = p.id 
$joinCondition trial t
  ON t.id = t_p.trial_id
WHERE $condition";
                            $this->assertEquals($sqlValue, $this->object->query($this->searchProvider));
                        }
                    }
                }
            }
        }

        // Ensure that a HTTP exception is raised if an invalid operation is specified.
        $this->setExpectedException(CHttpException::class);
        foreach ($invalidOps as $operator) {
            $this->object->operation = $operator;
            $this->object->query($this->searchProvider);
        }
    }

    /**
     * @covers PreviousTrialParameter::bindValues()
     */
    public function testBindValues()
    {
        $this->object->trial = 1;
        $this->object->type = Trial::TRIAL_TYPE_INTERVENTION;
        $expected = array(
            ':p_t_trial_0' => $this->object->trial,
        );

        // Ensure that all bind values are returned.
        $this->assertEquals($expected, $this->object->bindValues());

        $this->object->trial = '';

        $expected = array(
            ':p_t_type_0' => $this->object->type,
        );

        $this->assertEquals($expected, $this->object->bindValues());

        // Both trial-specific binds (type and trial) should never be returned. Only one should be returned.
        $incorrect = array(
            ':p_t_type_0' => $this->object->type,
            ':p_t_trial_0' => $this->object->trial,
        );

        $this->assertNotEquals($incorrect, $this->object->bindValues());

        $this->object->trial = 1;
        $this->object->status = TrialPatient::STATUS_SHORTLISTED;

        $expected = array(
            ':p_t_trial_0' => $this->object->trial,
            ':p_t_status_0' => TrialPatient::STATUS_SHORTLISTED,
        );

        $this->assertEquals($expected, $this->object->bindValues());
    }

    public function testTreatmentTypeBindValues()
    {
        $this->object->treatmentType = '';
        $this->assertEmpty($this->object->bindValues(),
            'The treatment type bind should not be set if the parameter is blank');

        $this->object->treatmentType = TrialPatient::TREATMENT_TYPE_PLACEBO;
        $expected = array(
            ':p_t_treatment_type_0' => TrialPatient::TREATMENT_TYPE_PLACEBO,
        );
        $this->assertEquals($expected, $this->object->bindValues(), 'The treatment type bind was expected');


        $this->object->treatmentType = TrialPatient::TREATMENT_TYPE_PLACEBO;
        $this->object->type = Trial::TRIAL_TYPE_NON_INTERVENTION;
        $expected = array(
            ':p_t_type_0' => $this->object->type,
        );
        $result = $this->object->bindValues();
        $this->assertEquals($expected, $result,
            'The treatment type parameter should not be set for non-intervention trials: ');
    }

}
