<?php

/**
 * This is the model class for table "trial".
 *
 * The followings are the available columns in table 'trial':
 * @property integer $id
 * @property string $name
 * @property string $description
 * @property integer $owner_user_id
 * @property integer $status
 * @property integer $trial_type
 * @property string $started_date
 * @property string $closed_date
 * @property string $last_modified_date
 * @property string $last_modified_user_id
 * @property integer $created_user_id
 * @property string $created_date
 * @property string $external_reference
 *
 * The followings are the available model relations:
 * @property User $ownerUser
 * @property User $createdUser
 * @property User $lastModifiedUser
 * @property TrialPatient[] $trialPatients
 * @property UserTrialPermission[] $userPermissions
 */
class Trial extends BaseActiveRecordVersioned
{
    /**
     * The status when the Trial is first created
     */
    const STATUS_OPEN = 0;

    /**
     * The status when the Trial has begun (can only be moved here once all patients have accepted or rejected)
     */
    const STATUS_IN_PROGRESS = 1;

    /**
     * The status when the Trial has been completed and closed (can only be moved here from STATUS_IN_PROGRESS)
     */
    const STATUS_CLOSED = 2;

    /**
     * The status when the Trial has been closed prematurely
     */
    const STATUS_CANCELLED = 3;

    /**
     * The trial type for non-Intervention trial (meaning there are no restrictions on assigning patients to this the trial)
     */
    const TRIAL_TYPE_NON_INTERVENTION = 0;

    /**
     * The trial type for Intervention trials (meaning a patient can only be assigned to one ongoing Intervention trial at a time)
     */
    const TRIAL_TYPE_INTERVENTION = 1;

    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'trial';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('name, owner_user_id, status', 'required'),
            array('name', 'length', 'max' => 64),
            array('external_reference', 'length', 'max' => 100),
            array('owner_user_id, last_modified_user_id, created_user_id, status', 'length', 'max' => 10),
            array('status', 'in', 'range' => self::getAllowedStatusRange()),
            array('trial_type', 'in', 'range' => self::getAllowedTrialTypeRange()),
            array('description, last_modified_date, created_date, closed_date', 'safe'),

            // The following rule is used by search().
            // @todo Please remove those attributes that should not be searched.
            array(
                'id, name, description, owner_user_id, status, last_modified_date, last_modified_user_id, created_user_id, created_date',
                'safe',
                'on' => 'search',
            ),
        );
    }

    /**
     * Returns an array of all of the allowable values of "status"
     * @return integer[] The list of statuses
     */
    public static function getAllowedStatusRange()
    {
        return array(
            self::STATUS_OPEN,
            self::STATUS_IN_PROGRESS,
            self::STATUS_CLOSED,
            self::STATUS_CANCELLED,
        );
    }

    /**
     * Returns an array withs keys of the allowable values of status and values of the label for that status
     * @return array The array of status id/label key/value pairs
     */
    public static function getStatusOptions()
    {
        return array(
            self::STATUS_OPEN => 'Open',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_CLOSED => 'Closed',
            self::STATUS_CANCELLED => 'Cancelled',
        );
    }

    /**
     * Returns an array of all of the allowable values of "trial_type"
     * @return integer[] The list of types
     */
    public static function getAllowedTrialTypeRange()
    {
        return array(
            self::TRIAL_TYPE_NON_INTERVENTION,
            self::TRIAL_TYPE_INTERVENTION,
        );
    }

    /**
     * Returns an array withs keys of the allowable values of the trial status and values of the label for that type
     * @return array The array of trial type id/label key/value pairs
     */
    public static function getTrialTypeOptions()
    {
        return array(
            self::TRIAL_TYPE_NON_INTERVENTION => 'Non-Intervention',
            self::TRIAL_TYPE_INTERVENTION => 'Intervention',
        );
    }

    /**
     * Returns the trial type as a string
     *
     * @return string The trial type
     */
    public function getTypeString()
    {
        if (array_key_exists($this->trial_type, self::getTrialTypeOptions())) {
            return self::getTrialTypeOptions()[$this->trial_type];
        }

        return $this->trial_type;
    }

    /**
     * Returns the status as a string
     *
     * @return string The trial status
     */
    public function getStatusString()
    {
        if (array_key_exists($this->status, self::getStatusOptions())) {
            return self::getStatusOptions()[$this->status];
        }

        return $this->status;
    }

    /**
     * Returns the date this trial was started as a string
     *
     * @return string The started date as a string
     */
    public function getStartedDateForDisplay()
    {
        return $this->started_date !== null ? Helper::formatFuzzyDate($this->started_date) : 'Pending';
    }

    /**
     * Returns the date this trial was closed as a string
     *
     * @return string The closed date
     */
    public function getClosedDateForDisplay()
    {
        if ($this->started_date === null) {
            return null;
        } elseif ($this->closed_date !== null) {
            return Helper::formatFuzzyDate($this->closed_date);
        } else {
            return 'present';
        }
    }


    /**
     * @return array relational rules.
     */
    public function relations()
    {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
            'ownerUser' => array(self::BELONGS_TO, 'User', 'owner_user_id'),
            'createdUser' => array(self::BELONGS_TO, 'User', 'created_user_id'),
            'lastModifiedUser' => array(self::BELONGS_TO, 'User', 'last_modified_user_id'),
            'trialPatients' => array(self::HAS_MANY, 'TrialPatient', 'trial_id'),
            'userPermissions' => array(self::HAS_MANY, 'UserTrialPermission', 'trial_id'),
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array(
            'id' => 'ID',
            'name' => 'Name',
            'description' => 'Description',
            'owner_user_id' => 'Owner User',
            'status' => 'Status',
            'trial_type' => 'Trial Type',
            'closed_date' => 'Closed Date',
            'last_modified_date' => 'Last Modified Date',
            'last_modified_user_id' => 'Last Modified User',
            'created_user_id' => 'Created User',
            'created_date' => 'Created Date',
            'external_reference' => 'External Reference',
        );
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return Trial the static model class
     */
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * Overrides CActiveModel::afterSave()
     *
     * @throws Exception Thrown if a new permission cannot be created
     */
    protected function afterSave()
    {
        parent::afterSave();

        if ($this->getIsNewRecord()) {

            // Create a new permission assignment for the user that created the Trial
            $newPermission = new UserTrialPermission();
            $newPermission->user_id = Yii::app()->user->id;
            $newPermission->trial_id = $this->id;
            $newPermission->permission = UserTrialPermission::PERMISSION_MANAGE;
            if (!$newPermission->save()) {
                throw new CHttpException(500, 'The owner permission for the new trial could not be saved: '
                    . print_r($newPermission->getErrors(), true));
            }
        }
    }

    /**
     * Returns whether or not the given user can access the given trial using the given action
     * @param User $user The user to check access for
     * @param integer $trial_id The ID of the trial
     * @param integer $permission The ID of the controller action
     * @return bool True if access is permitted, otherwise false
     * @throws CDbException Thrown if an error occurs when looking up the user permissions
     */
    public static function checkTrialAccess($user, $trial_id, $permission)
    {
        /* @var Trial $model */
        $model = Trial::model()->findByPk($trial_id);
        $access_level = $model->getTrialAccess($user);

        return $access_level !== null && $access_level >= $permission;
    }

    /**
     * @param User $user The user to get access for
     * @return integer The user permission if they have one otherwise null)
     * @throws CDbException Thrown if an error occurs when executing the SQL statement
     */
    public function getTrialAccess($user)
    {
        $sql = 'SELECT MAX(permission) FROM user_trial_permission WHERE user_id = :userId AND trial_id = :trialId';
        $query = $this->getDbConnection()->createCommand($sql);

        return $query->queryScalar(array(':userId' => $user->id, ':trialId' => $this->id));
    }

    /**
     * Returns whether or not this trial has any shortlisted patients
     *
     * @return bool True if the trial has one or more shortlisted patients, otherwise false
     */
    public function hasShortlistedPatients()
    {
        return TrialPatient::model()->exists('trial_id = :trialId AND patient_status = :patientStatus',
            array(':trialId' => $this->id, ':patientStatus' => TrialPatient::STATUS_SHORTLISTED));
    }


    /**
     * Gets the data providers for each patient status
     * @param string $sort_by The field name to sort by
     * @param string $sort_dir The direction to sort the results by
     * @return array An array of data providers with one for each patient status
     * @throws CException Thrown if an error occurs when created the data providers
     */
    public function getPatientDataProviders($sort_by, $sort_dir)
    {

        $dataProviders = array();
        foreach (TrialPatient::getAllowedStatusRange() as $index => $status) {
            $dataProviders[$status] = $this->getPatientDataProvider($status, $sort_by, $sort_dir);
        }

        return $dataProviders;
    }

    /**
     * Create a data provider for patients in the Trial
     * @param integer $patient_status The status of patients of
     * @param string $sort_by The field name to sort by
     * @param string $sort_dir The direction to sort the results by
     * @return CActiveDataProvider The data provider of patients with the given status
     * @throws CException Thrown if the patient_status is invalid
     */
    public function getPatientDataProvider($patient_status, $sort_by, $sort_dir)
    {
        if (!in_array($patient_status, TrialPatient::getAllowedStatusRange())) {
            throw new CException("Unknown Trial Patient status: $patient_status");
        }

        // Get the column to sort by ('t' => trial_patient, p => patient, e => ethnic_group, c => contact))
        $sortBySql = null;
        switch ($sort_by) {
            case 'name':
            default:
                $sortBySql = "c.last_name $sort_dir, c.first_name";
                break;
            case 'gender':
                $sortBySql = 'p.gender';
                break;
            case 'age':
                $sortBySql = 'NOW() - p.dob';
                break;
            case 'ethnicity':
                $sortBySql = 'IFNULL(e.name, "Unknown")';
                break;
            case 'external_reference':
                $sortBySql = 'ISNULL(t.external_trial_identifier), t.external_trial_identifier';
                break;
            case 'treatment_type':
                $sortBySql = 'ISNULL(treatment_type), t.treatment_type';
                break;
        }

        $sortExpr = "$sortBySql $sort_dir, c.last_name ASC, c.first_name ASC";

        $patientDataProvider = new CActiveDataProvider('TrialPatient', array(
            'criteria' => array(
                'condition' => 'trial_id = :trialId AND patient_status = :patientStatus',
                'join' => 'JOIN patient p ON p.id = t.patient_id
                           JOIN contact c ON c.id = p.contact_id
                           LEFT JOIN ethnic_group e ON e.id = p.ethnic_group_id',
                'order' => $sortExpr,
                'params' => array(
                    ':trialId' => $this->id,
                    ':patientStatus' => $patient_status,
                ),
            ),
            'pagination' => array(
                'pageSize' => 10,
            ),
        ));

        return $patientDataProvider;
    }

    /**
     * Get a list of trials for a specific trial type. The output of this can be used to render drop-down lists.
     * @param $type string The trial type.
     * @return array A list of trials of the specified trial type.
     */
    public static function getTrialList($type)
    {
        if ($type === null || $type === '') {
            return array();
        }

        $trialModels = Trial::model()->findAll('trial_type=:type', array(':type' => $type));

        return CHtml::listData($trialModels, 'id', 'name');
    }
}
