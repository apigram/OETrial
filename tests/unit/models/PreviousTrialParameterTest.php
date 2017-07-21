<?php

/**
 * Created by PhpStorm.
 * User: andre
 * Date: 31/05/2017
 * Time: 4:51 PM
 */
class PreviousTrialParameterTest extends CDbTestCase
{
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
                        if ($operator === '=') {
                            $joinCondition = 'JOIN';
                            if ($this->object->type !== '' && isset($this->object->type)) {
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
                        } elseif ($operator === '!=') {
                            $joinCondition = 'LEFT JOIN';
                            if ($this->object->type !== '' && isset($this->object->type)) {
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
}
