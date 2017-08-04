<?php

/**
 * This is the model class for table "trial".
 *
 * The followings are the available columns in table 'trial':
 * @property int $id
 * @property string $name
 * @property string $description
 * @property int $owner_user_id
 * @property int $status
 * @property int $trial_type
 * @property string $started_date
 * @property string $closed_date
 * @property string $external_data_link
 * @property string $last_modified_date
 * @property string $last_modified_user_id
 * @property int $created_user_id
 * @property string $created_date
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
    const STATUS_OPEN = 1;
    /**
     * The status when the Trial has begun (can only be moved here once all patients have accepted or rejected)
     */
    const STATUS_IN_PROGRESS = 2;
    /**
     * The status when the Trial has been completed and closed (can only be moved here from STATUS_IN_PROGRESS)
     */
    const STATUS_CLOSED = 3;
    /**
     * The status when the Trial has been closed prematurely
     */
    const STATUS_CANCELLED = 4;


    /**
     * The trial type for non-Intervention trial (meaning there are no restrictions on assigning patients to this the trial)
     */
    const TRIAL_TYPE_NON_INTERVENTION = 1;

    /**
     * The trial type for Intervention trials (meaning a patient can only be assigned to one ongoing Intervention trial at a time)
     */
    const TRIAL_TYPE_INTERVENTION = 2;

    /**
     * The success return code for addUserPermission()
     */
    const RETURN_CODE_USER_PERMISSION_OK = 'success';

    /**
     * The return code for addUserPermission() if the user tried to share the trial with a user that it is
     * already shared with
     */
    const RETURN_CODE_USER_PERMISSION_ALREADY_EXISTS = 'permission_already_exists';

    /**
     * The return code for actionRemovePermission() if all went well
     */
    const REMOVE_PERMISSION_RESULT_SUCCESS = 'success';
    /**
     * The return code for actionRemovePermission() if the user tried to remove the last user with manage privileges
     */
    const REMOVE_PERMISSION_RESULT_CANT_REMOVE_LAST = 'remove_last_fail';
    /**
     * The return code for actionRemovePermission() if the user tried to remove themselves from the Trial
     */
    const REMOVE_PERMISSION_RESULT_CANT_REMOVE_SELF = 'remove_self_fail';

    /**
     * The return code for actionTransitionState() if the transition was a success
     */
    const RETURN_CODE_OK = 'success';
    /**
     * The return code for actionTransitionState() if the user tried to transition an open trial to in progress
     * while a patient is still shortlisted
     */
    const RETURN_CODE_CANT_OPEN_SHORTLISTED_TRIAL = 'cant_open_with_shortlisted_patients';

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
            array('external_data_link', 'url', 'defaultScheme' => 'http'),
            array('external_data_link', 'length', 'max' => 255),
            array('owner_user_id, last_modified_user_id, created_user_id, status', 'length', 'max' => 10),
            array('status', 'in', 'range' => self::getAllowedStatusRange()),
            array('trial_type', 'in', 'range' => self::getAllowedTrialTypeRange()),
            array('description, last_modified_date, created_date, closed_date', 'safe'),
        );
    }

    /**
     * Returns an array of all of the allowable values of "status"
     * @return int[] The list of statuses
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
     * @return int[] The list of types
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
        }

        if ($this->closed_date !== null) {
            return Helper::formatFuzzyDate($this->closed_date);
        }

        return 'present';
    }

    /**
     * Gets the relation rules for Trial
     *
     * @return array relational rules.
     */
    public function relations()
    {
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
            'external_data_link' => 'External Data Link',
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
            $newPermission->role = 'Trial Owner';

            if (!$newPermission->save()) {
                throw new CHttpException(500, 'The owner permission for the new trial could not be saved: '
                    . print_r($newPermission->getErrors(), true));
            }
        }
    }

    /**
     * Returns whether or not the given user can access the given trial using the given action
     * @param User $user The user to check access for
     * @param int $trial_id The ID of the trial
     * @param int $permission The ID of the controller action
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
     * @return int The user permission if they have one otherwise null)
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
     * @param int $patient_status The status of patients of
     * @param string $sort_by The field name to sort by
     * @param string $sort_dir The direction to sort the results by
     * @return CActiveDataProvider The data provider of patients with the given status
     * @throws CException Thrown if the patient_status is invalid
     */
    public function getPatientDataProvider($patient_status, $sort_by, $sort_dir)
    {
        if (!in_array((int)$patient_status, TrialPatient::getAllowedStatusRange(), true)) {
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

    /**
     * Adds a patient to the trial
     *
     * @param Patient $patient The patient to add
     * @param int $patient_status The initial trial status for the patient (default to shortlisted)
     * @returns TrialPatient The new TrialPatient record
     * @throws Exception Thrown if an error occurs when saving the TrialPatient record
     */
    public function addPatient(Patient $patient, $patient_status)
    {
        $trialPatient = new TrialPatient();
        $trialPatient->trial_id = $this->id;
        $trialPatient->patient_id = $patient->id;
        $trialPatient->patient_status = $patient_status;
        $trialPatient->treatment_type = TrialPatient::TREATMENT_TYPE_UNKNOWN;

        if (!$trialPatient->save()) {
            throw new Exception(
                'Unable to create TrialPatient: ' . print_r($trialPatient->getErrors(), true));
        }

        $this->audit('trial', 'add-patient');

        return $trialPatient;
    }

    /**
     * @param int $patient_id The id of the patient to remove
     * @throws Exception Raised when an error occurs when removing the record
     */
    public function removePatient($patient_id)
    {
        $trialPatient = TrialPatient::model()->find(
            'patient_id = :patientId AND trial_id = :trialId',
            array(
                ':patientId' => $patient_id,
                ':trialId' => $this->id,
            )
        );

        if ($trialPatient === null) {
            throw new Exception("Patient $patient_id cannot be removed from Trial $this->>id");
        }

        if (!$trialPatient->delete()) {
            throw new Exception('Unable to delete TrialPatient: ' . print_r($trialPatient->getErrors(), true));
        }

        $this->audit('trial', 'remove-patient');
    }

    /**
     * Creates a new Trial Permission using values in $_POST
     *
     * @param int $user_id The ID of the User record to add the permission to
     * @param int $permission The permission level the user will be given (view/edit/manage)
     * @param string $role The role the user will have
     * @returns string The return code
     * @throws Exception Thrown if the permission couldn't be saved
     */
    public function addUserPermission($user_id, $permission, $role)
    {
        if (UserTrialPermission::model()->exists(
            'trial_id = :trialId AND user_id = :userId',
            array(
                ':trialId' => $this->id,
                ':userId' => $user_id,
            ))
        ) {
            return self::RETURN_CODE_USER_PERMISSION_ALREADY_EXISTS;
        }

        $userPermission = new UserTrialPermission();
        $userPermission->trial_id = $this->id;
        $userPermission->user_id = $user_id;
        $userPermission->permission = $permission;
        $userPermission->role = $role;

        if (!$userPermission->save()) {
            throw new Exception('Unable to create UserTrialPermission: ' . print_r($userPermission->getErrors(), true));
        }

        $this->audit('trial', 'add-user-permission');

        return self::RETURN_CODE_USER_PERMISSION_OK;
    }

    /**
     * Removes a UserTrialPermission
     *
     * @param int $permission_id The ID of the permission to remove
     * @throws CHttpException Thrown if the permission cannot be found
     * @return string The return code
     * @throws Exception Thrown if the permission cannot be deleted
     */
    public function removeUserPermission($permission_id)
    {
        /* @var UserTrialPermission $permission */
        $permission = UserTrialPermission::model()->findByPk($permission_id);
        if ($permission->trial->id !== $this->id) {
            throw new CHttpException(400);
        }

        if ($permission->user_id === Yii::app()->user->id) {
            return self::REMOVE_PERMISSION_RESULT_CANT_REMOVE_SELF;
        }

        // The last Manage permission in a trial can't be removed (there always has to be one manager for a trial)
        if ((int)$permission->permission === UserTrialPermission::PERMISSION_MANAGE) {
            $managerCount = UserTrialPermission::model()->count('trial_id = :trialId AND permission = :permission',
                array(
                    ':trialId' => $this->id,
                    ':permission' => UserTrialPermission::PERMISSION_MANAGE,
                )
            );

            if ($managerCount <= 1) {
                return self::REMOVE_PERMISSION_RESULT_CANT_REMOVE_LAST;
            }
        }

        if (!$permission->delete()) {
            throw new Exception('An error occurred when attempting to delete the permission: '
                . print_r($permission->getErrors(), true));
        }

        $this->audit('trial', 'remove-user-permission');

        return self::REMOVE_PERMISSION_RESULT_SUCCESS;
    }

    /**
     * Transitions the given Trial to a new state.
     * A different return code is echoed out depending on whether the transition was successful
     *
     * @param int $new_state The new state to transition to (must be a valid state within Trial::getAllowedStatusRange()
     * @return string The return  code
     * @throws Exception Thrown if an error occurs when saving
     */
    public function transitionState($new_state)
    {
        if ((int)$this->status === Trial::STATUS_OPEN && (int)$new_state === Trial::STATUS_IN_PROGRESS && $this->hasShortlistedPatients()) {
            return self::RETURN_CODE_CANT_OPEN_SHORTLISTED_TRIAL;
        }

        if ((int)$new_state === Trial::STATUS_CLOSED || (int)$new_state === Trial::STATUS_CANCELLED) {
            $this->closed_date = date('Y-m-d H:i:s');
        } else {
            $this->closed_date = null;
        }

        if ((int)$this->status === Trial::STATUS_OPEN && (int)$new_state === Trial::STATUS_IN_PROGRESS) {
            $this->started_date = date('Y-m-d H:i:s');
        }

        $this->status = $new_state;
        if (!$this->save()) {
            throw new Exception('An error occurred when attempting to change the status: ' . print_r($this->getErrors(),
                    true));
        }

        $this->audit('trial', 'transition-state');

        return self::RETURN_CODE_OK;
    }
}
