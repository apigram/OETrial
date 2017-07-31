<?php

/**
 * Class OETrial_ReportCohort
 */
class OETrial_ReportCohort extends BaseReport
{
    /**
     * @var int The ID of the trial
     */
    public $trialID;
    /**
     * @var TrialPatient[] The patients for the trial
     */
    public $patients = array();

    /**
     * @return array
     */
    public function rules()
    {
        return array(
            array('trialID', 'safe'),
        );
    }

    /**
     * @return CDbCommand The search command
     */
    public function getDbCommand()
    {
        return Yii::app()->db->createCommand()
            ->from('trial t')
            ->join('trial_patient t_p', 't.id = t_p.trial_id')
            ->join('patient p', 'p.id = t_p.patient_id')
            ->join('contact c', 'p.contact_id = c.id');
    }

    /**
     * Runs the report and adds the result set to $patients
     */
    public function run()
    {
        $select = 'p.id, p.hos_num, c.first_name, c.last_name, p.dob';

        $query = $this->getDbCommand();

        $or_conditions = array('t.id=:id');
        $whereParams = array(':id' => $this->trialID);

        $query->select($select);
        $condition = '( ' . implode(' AND ', $or_conditions) . ' )';

        $query->where($condition, $whereParams);

        foreach ($query->queryAll() as $item) {
            $this->addPatientResultItem($item);
        }
    }

    /**
     * Gets the description message to display at the top of the report
     *
     * @return string
     */
    public function description()
    {
        /* @var Trial $trial */
        $trial = Trial::model()->findByPk($this->trialID);

        return "Patients shortlisted for $trial->name";
    }

    /**
     * Adds one result row to $patients
     *
     * @param array $item
     */
    public function addPatientResultItem($item)
    {
        $this->patients[$item['id']] = array(
            'hos_num' => $item['hos_num'],
            'dob' => $item['dob'],
            'first_name' => $item['first_name'],
            'last_name' => $item['last_name'],
        );
    }

    /**
     * Output the report in CSV format.
     *
     * @return string
     */
    public function toCSV()
    {
        $output = $this->description() . "\n\n";

        $output .= Patient::model()->getAttributeLabel('hos_num') . ',' . Patient::model()->getAttributeLabel('dob') . ',' . Patient::model()->getAttributeLabel('first_name') . ',' . Patient::model()->getAttributeLabel('last_name') . "\n";

        foreach ($this->patients as $ts => $patient) {
            $output .= "\"{$patient['hos_num']}\",\"" . ($patient['dob'] ? date('j M Y',
                    strtotime($patient['dob'])) : 'Unknown') . "\",\"{$patient['first_name']}\",\"{$patient['last_name']}\"" . "\n";
        }

        return $output;
    }
}