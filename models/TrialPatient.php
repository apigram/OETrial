<?php

/**
 * This is the model class for table "trial_patient".
 *
 * The followings are the available columns in table 'trial_patient':
 * @property integer $id
 * @property string $external_trial_identifier
 * @property integer $trial_id
 * @property integer $patient_id
 * @property integer $patient_status
 * @property integer $treatment_type
 * @property integer $last_modified_user_id
 * @property string $last_modified_date
 * @property integer $created_user_id
 * @property string $created_date
 *
 * The followings are the available model relations:
 * @property Patient $patient
 * @property User $createdUser
 * @property User $lastModifiedUser
 * @property Trial $trial
 */
class TrialPatient extends BaseActiveRecordVersioned
{
    /**
     * The status when the patient has been just added to a Trial, but hasn't been accepted or rejected yet
     */
    const STATUS_SHORTLISTED = 0;

    /**
     * The status when the patient has been accepted into the Trial
     */
    const STATUS_ACCEPTED = 1;

    /**
     * The status when the patient hsa been rejected from the Trial
     */
    const STATUS_REJECTED = 2;

    /**
     * The treatment type when users don't know whether the patient had intervention treatment or not (also the default value)
     */
    const TREATMENT_TYPE_UNKNOWN = 0;
    /**
     * The treatment type when it is known that the patient had intervention surgery or medication
     */
    const TREATMENT_TYPE_INTERVENTION = 1;
    /**
     * The treatment type when the patient had a placebo instead of intervention surgery or medicine
     */
    const TREATMENT_TYPE_PLACEBO = 2;

    /**
     * Gets an array of the different possible patient statuses
     * @return array The array of statues
     */
    public static function getAllowedStatusRange()
    {
        return array(
            self::STATUS_SHORTLISTED,
            self::STATUS_ACCEPTED,
            self::STATUS_REJECTED,
        );
    }

    /**
     * Gets an array with keys as the different possible patient statuses, and the values as the label for that status
     *
     * @return integer[] The status options
     */
    public static function getStatusOptions()
    {
        return array(
            self::STATUS_SHORTLISTED => 'Shortlisted',
            self::STATUS_ACCEPTED => 'Accepted',
            self::STATUS_REJECTED => 'Rejected',
        );
    }

    /**
     * Gets an array of the different possible treatment types
     * @return array The array of statues
     */
    public static function getAllowedTreatmentTypeRange()
    {
        return array(
            self::TREATMENT_TYPE_UNKNOWN,
            self::TREATMENT_TYPE_INTERVENTION,
            self::TREATMENT_TYPE_PLACEBO,
        );
    }

    /**
     * Gets an array with keys as the different possible treatment types, and the values as the label for that treatment type
     *
     * @return integer[] The treatment options
     */
    public static function getTreatmentTypeOptions()
    {
        return array(
            self::TREATMENT_TYPE_UNKNOWN => 'Unknown',
            self::TREATMENT_TYPE_INTERVENTION => 'Intervention',
            self::TREATMENT_TYPE_PLACEBO => 'Placebo',
        );
    }

    /**
     * Returns the status as a displayable string
     *
     * @return string The status string
     */
    public function getStatusForDisplay()
    {
        if (array_key_exists($this->patient_status, self::getStatusOptions())) {
            return self::getStatusOptions()[$this->patient_status];
        }

        return $this->patient_status;
    }

    /**
     * Returns the treatment type as a displayable string
     *
     * @return string The treatment type string
     */
    public function getTreatmentTypeForDisplay()
    {
        if($this->trial->trial_type == Trial::TRIAL_TYPE_NON_INTERVENTION) {
            return 'N/A';
        }

        if (array_key_exists($this->treatment_type, self::getTreatmentTypeOptions())) {
            return self::getTreatmentTypeOptions()[$this->treatment_type];
        }

        return $this->treatment_type;
    }

    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'trial_patient';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        return array(
            array('trial_id, patient_id, patient_status', 'required'),
            array('trial_id', 'numerical', 'integerOnly' => true),
            array('external_trial_identifier', 'length', 'max' => 64),
            array('patient_id, patient_status', 'length', 'max' => 10),
            array('patient_status', 'in', 'range' => self::getAllowedStatusRange()),
            array('treatment_type', 'in', 'range' => self::getAllowedTreatmentTypeRange()),
            // The trial_id and the patient_id must be unique together
            array(
                'trial_id',
                'unique',
                'criteria' => array(
                    'condition' => '`patient_id`=:patientId',
                    'params' => array(
                        ':patientId' => $this->patient_id,
                    ),
                ),
            ),
            array('last_modified_date, created_date', 'safe'),
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
            'patient' => array(self::BELONGS_TO, 'Patient', 'patient_id'),
            'createdUser' => array(self::BELONGS_TO, 'User', 'created_user_id'),
            'lastModifiedUser' => array(self::BELONGS_TO, 'User', 'last_modified_user_id'),
            'trial' => array(self::BELONGS_TO, 'Trial', 'trial_id'),
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array(
            'id' => 'ID',
            'external_trial_identifier' => 'Trial Identifier',
            'trial_id' => 'Trial',
            'patient_id' => 'Patient',
            'patient_status' => 'Patient Status',
            'treatment_type' => 'Treatment Type',
            'last_modified_user_id' => 'Last Modified User',
            'last_modified_date' => 'Last Modified Date',
            'created_user_id' => 'Created User',
            'created_date' => 'Created Date',
        );
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return TrialPatient the static model class
     */
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * Checks whether a user has access to a certain TrialPatient
     *
     * @param CWebuser $user The user to check access for
     * @param integer $trial_patient_id The ID of the TrialPatient to check access for
     * @param integer $permission The permission to check
     * @return bool True if $user is allowed to perform $permission on $trial_patient_id, otherwise false
     * @throws CHttpException Thrown by Trial::checkTrialAccess()
     */
    public static function checkTrialPatientAccess($user, $trial_patient_id, $permission)
    {
        /* @var TrialPatient $model */
        $model = TrialPatient::model()->findByPk($trial_patient_id);

        return Trial::checkTrialAccess($user, $model->trial_id, $permission);
    }
}
