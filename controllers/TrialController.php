<?php

/**
 * Class TrialController
 */
class TrialController extends BaseModuleController
{
    /**
     * The return code for actionTransitionState() if the transition was a success
     */
    const RETURN_CODE_OK = '0';
    /**
     * The return code for actionTransitionState() if the user tried to transition an open trial to in progress
     * while a patient is still shortlisted
     */
    const RETURN_CODE_CANT_OPEN_SHORTLISTED_TRIAL = '1';

    /**
     * The return code for actionAddPermission() if the user tried to share the trial with a user that it is
     * already shared with
     */
    const RETURN_CODE_USER_PERMISSION_ALREADY_EXISTS = '2';


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
     * @return array action filters
     */
    public function filters()
    {
        return array(
            'accessControl',
            'ajaxOnly + getTrialList',
        );
    }

    /**
     * Specifies the access control rules.
     * This method is used by the 'accessControl' filter.
     * @return array access control rules
     */
    public function accessRules()
    {
        return array(
            array(
                'allow',
                'actions' => array('getTrialList', 'permissions'),
                'users' => array('@'),
            ),
            array(
                'allow',
                'actions' => array('index', 'userAutoComplete'),
                'roles' => array('TaskCreateTrial', 'TaskViewTrial'),
            ),
            array(
                'allow',
                'actions' => array('view'),
                'expression' => '$user->checkAccess("TaskViewTrial") && Trial::checkTrialAccess($user, Yii::app()->getRequest()->getQuery("id"), UserTrialPermission::PERMISSION_VIEW)',
            ),
            array(
                'allow',
                'actions' => array('update', 'addPatient', 'removePatient'),
                'expression' => '$user->checkAccess("TaskViewTrial") && Trial::checkTrialAccess($user, Yii::app()->getRequest()->getQuery("id"), UserTrialPermission::PERMISSION_EDIT)',
            ),
            array(
                'allow',
                'actions' => array('addPermission', 'removePermission', 'transitionState'),
                'expression' => '$user->checkAccess("TaskViewTrial") && Trial::checkTrialAccess($user, Yii::app()->getRequest()->getQuery("id"), UserTrialPermission::PERMISSION_MANAGE)',
            ),
            array(
                'allow',
                'actions' => array('create'),
                'roles' => array('TaskCreateTrial'),
            ),
            array(
                'deny',  // deny all users
                'users' => array('*'),
            ),
        );
    }

    /**
     * Displays a particular model.
     * @param integer $id the ID of the model to be displayed
     * @throws CException Thrown if an error occurs when loading the data providers
     */
    public function actionView($id)
    {
        $model = $this->loadModel($id);
        $report = new OETrial_ReportCohort();

        $sortDir = Yii::app()->request->getParam('sort_dir', '0') === '0' ? 'asc' : 'desc';
        $sortBy = null;

        switch (Yii::app()->request->getParam('sort_by', -1)) {
            case 1:
            default:
                $sortBy = 'name';
                break;
            case 2:
                $sortBy = 'gender';
                break;
            case 3:
                $sortBy = 'age';
                break;
            case 4:
                $sortBy = 'ethnicity';
                break;
            case 5:
                $sortBy = 'external_reference';
                break;
            case 6:
                $sortBy = 'treatment_type';
                break;
        }

        $this->render('view', array(
            'model' => $model,
            'report' => $report,
            'dataProviders' => $model->getPatientDataProviders($sortBy, $sortDir),
            'sort_by' => (integer)Yii::app()->request->getParam('sort_by', null),
            'sort_dir' => (integer)Yii::app()->request->getParam('sort_dir', null),
        ));
    }

    /**
     * Returns the data model based on the primary key given in the GET variable.
     * If the data model is not found, an HTTP exception will be raised.
     * @param integer $id the ID of the model to be loaded
     * @return Trial the loaded model
     * @throws CHttpException
     */
    public function loadModel($id)
    {
        /* @var Trial $model */
        $model = Trial::model()->findByPk($id);
        if ($model === null) {
            throw new CHttpException(404, 'The requested page does not exist.');
        }

        return $model;
    }


    /**
     * Creates a new model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     */
    public function actionCreate()
    {
        $model = new Trial;
        $model->status = Trial::STATUS_OPEN;
        $model->owner_user_id = Yii::app()->user->id;

        if (isset($_POST['Trial'])) {
            $model->attributes = $_POST['Trial'];
            if ($model->save()) {
                $this->redirect(array('view', 'id' => $model->id));
            }
        }

        $this->render('create', array(
            'model' => $model,
        ));
    }

    /**
     * Updates a particular model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id the ID of the model to be updated
     * @throws CHttpException Thrown if the model cannot be loaded
     */
    public function actionUpdate($id)
    {
        $model = $this->loadModel($id);

        if (isset($_POST['Trial'])) {
            $model->attributes = $_POST['Trial'];
            if ($model->save()) {
                $this->redirect(array('view', 'id' => $model->id));
            }
        }

        $this->render('update', array(
            'model' => $model,
        ));
    }

    /**
     * Lists all models.
     */
    public function actionIndex()
    {
        // Get the sort direction, defaulting to ascending
        $sortDir = Yii::app()->request->getParam('sort_dir', '0') === '0' ? 'asc' : 'desc';

        // Get the column to sort by (the 't' table is the trial table, 'u' is the user that owns the trial)
        // Default to sorting by status
        $sortBy = Yii::app()->request->getParam('sort_by', -1);
        switch ($sortBy) {
            case 0:
                $sortBy = 'LOWER(t.name)';
                break;
            case 1:
                $sortBy = 't.started_date';
                break;
            case 2:
                $sortBy = 't.closed_date';
                break;
            case 3:
                $sortBy = "LOWER(u.first_name) $sortDir, LOWER(u.last_name)";
                break;
            case 4:
                $sortBy = 't.status';
                break;
            default:
                $sortBy = 'LOWER(t.name)';
                break;
        }

        $condition = "trial_type = :trialType AND EXISTS (
                        SELECT * FROM user_trial_permission utp WHERE utp.user_id = :userId AND utp.trial_id = t.id
                    ) ORDER BY $sortBy $sortDir, LOWER(t.name) ASC";

        $interventionTrialDataProvider = new CActiveDataProvider('Trial', array(
            'criteria' => array(
                'condition' => $condition,
                'join' => 'JOIN user u ON u.id = t.owner_user_id',
                'params' => array(
                    ':userId' => Yii::app()->user->id,
                    ':trialType' => Trial::TRIAL_TYPE_INTERVENTION,
                ),
            ),
        ));

        $nonInterventionTrialDataProvider = new CActiveDataProvider('Trial', array(
            'criteria' => array(
                'condition' => $condition,
                'join' => 'JOIN user u ON u.id = t.owner_user_id',
                'params' => array(
                    ':userId' => Yii::app()->user->id,
                    ':trialType' => Trial::TRIAL_TYPE_NON_INTERVENTION,
                ),
            ),
        ));

        $this->render('index', array(
            'interventionTrialDataProvider' => $interventionTrialDataProvider,
            'nonInterventionTrialDataProvider' => $nonInterventionTrialDataProvider,
            'sort_by' => (integer)Yii::app()->request->getParam('sort_by', null),
            'sort_dir' => (integer)Yii::app()->request->getParam('sort_dir', null),
        ));
    }

    /**
     * Adds a patient to the trial
     *
     * @param integer $id The ID of the Trial to add to
     * @param integer $patient_id THe ID of the patient to add
     * @param integer $patient_status The initial trial status for the patient (default to shortlisted)
     * @throws Exception Thrown if an error occurs when saving the TrialPatient record
     */
    public function actionAddPatient($id, $patient_id, $patient_status = TrialPatient::STATUS_SHORTLISTED)
    {
        $trial = Trial::model()->findByPk($id);
        $patient = Patient::model()->findByPk($patient_id);

        $trialPatient = new TrialPatient();
        $trialPatient->trial_id = $trial->id;
        $trialPatient->patient_id = $patient->id;
        $trialPatient->patient_status = $patient_status;

        if (!$trialPatient->save()) {
            throw new CHttpException(400,
                'Unable to create TrialPatient: ' . print_r($trialPatient->getErrors(), true));
        }
    }

    /**
     * @param integer $id The id of the trial to remove
     * @param integer $patient_id The id of the patient to remove
     * @throws CHttpException Raised when the record cannot be found
     * @throws Exception Raised when an error occurs when removing the record
     */
    public function actionRemovePatient($id, $patient_id)
    {
        $trialPatient = TrialPatient::model()->find(
            'patient_id = :patientId AND trial_id = :trialId',
            array(
                ':patientId' => $patient_id,
                ':trialId' => $id,
            )
        );

        if ($trialPatient === null) {
            throw new CHttpException(400, "Patient $patient_id cannot be removed from Trial $id");
        }


        if (!$trialPatient->delete()) {
            throw new CHttpException(400,
                'Unable to delete TrialPatient: ' . print_r($trialPatient->getErrors(), true));
        }
    }

    /**
     * Displays the permissions screen
     *
     * @param integer $id The ID of the Trial
     */
    public function actionPermissions($id)
    {
        $model = Trial::model()->findByPk($id);

        $permissionDataProvider = new CActiveDataProvider('UserTrialPermission', array(
            'criteria' => array(
                'condition' => 'trial_id = :trialId',
                'params' => array(
                    ':trialId' => $model->id,
                ),
                'order' => 'permission DESC',
            ),
        ));

        $newPermission = new UserTrialPermission();

        $this->render('permissions', array(
            'model' => $model,
            'newPermission' => $newPermission,
            'permissionDataProvider' => $permissionDataProvider,
        ));
    }

    /**
     * Creates a new Trial Permission using values in $_POST
     *
     * @param integer $id The Trial ID
     * @throws CHttpException Thrown if the permission couldn't be saved
     */
    public function actionAddPermission($id, $user_id, $permission, $role)
    {
        if (UserTrialPermission::model()->exists(
            'trial_id = :trialId AND user_id = :userId',
            array(
                ':trialId' => $id,
                ':userId' => $user_id,
            ))
        ) {
            echo self::RETURN_CODE_USER_PERMISSION_ALREADY_EXISTS;

            return;
        }

        $userPermission = new UserTrialPermission();
        $userPermission->trial_id = $id;
        $userPermission->user_id = $user_id;
        $userPermission->permission = $permission;
        $userPermission->role = $role;

        if (!$userPermission->save()) {
            throw new CHttpException(400,
                'Unable to create UserTrialPermission: ' . print_r($permission->getErrors(), true));
        }

        echo self::RETURN_CODE_OK;
    }

    /**
     * Removes a UserTrialPermission
     *
     * @param integer $id The ID of the trial
     * @param integer $permission_id The ID of the permission to remove
     * @throws CHttpException Thrown if the permission cannot be found
     * @throws CDbException Thrown if the permission cannot be deleted
     */
    public function actionRemovePermission($id, $permission_id)
    {
        /* @var UserTrialPermission $permission */
        $permission = UserTrialPermission::model()->findByPk($permission_id);
        if ($permission->trial->id !== $id) {
            throw new CHttpException(400);
        }

        if ($permission->user_id === Yii::app()->user->id) {
            echo self::REMOVE_PERMISSION_RESULT_CANT_REMOVE_SELF;

            return;
        }

        // THe last Manage permission in a trial can't be removed (there always has to be one manager for a trial)
        if ($permission->permission == UserTrialPermission::PERMISSION_MANAGE) {
            $count = UserTrialPermission::model()->count('trial_id = :trialId AND permission = :permission',
                array(
                    ':trialId' => $id,
                    ':permission' => UserTrialPermission::PERMISSION_MANAGE,
                )
            );

            if ($count <= 1) {
                echo self::REMOVE_PERMISSION_RESULT_CANT_REMOVE_LAST;

                return;
            }
        }


        if (!$permission->delete()) {
            throw new CHttpException(500,
                'An error occurred when attempting to delete the permission: '
                . print_r($permission->getErrors(), true));
        }

        echo self::REMOVE_PERMISSION_RESULT_SUCCESS;
    }

    /**
     * Performs the AJAX validation.
     * @param Trial $model the model to be validated
     */
    protected function performAjaxValidation($model)
    {
        if (isset($_POST['ajax']) && $_POST['ajax'] === 'trial-form') {
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }
    }

    /**
     * Transitions the given Trial to a new state.
     * A different return code is echoed out depending on whether the transition was successful
     *
     * @param integer $id The ID of the trial to transition
     * @param integer $new_state The new state to transition to (must be a valid state within Trial::getAllowedStatusRange()
     * @throws CHttpException Thrown if an error occurs when saving
     */
    public function actionTransitionState($id, $new_state)
    {
        /* @var Trial $model */
        $model = Trial::model()->findByPk($id);

        if ($new_state == Trial::STATUS_OPEN && $model->hasShortlistedPatients()) {
            echo self::RETURN_CODE_CANT_OPEN_SHORTLISTED_TRIAL;

            return;
        }

        if ($new_state == Trial::STATUS_CLOSED || $new_state == Trial::STATUS_CANCELLED) {
            $model->closed_date = date('Y-m-d H:i:s');
        } else {
            $model->closed_date = null;
        }

        if ($model->status == Trial::STATUS_OPEN && $new_state == Trial::STATUS_IN_PROGRESS) {
            $model->started_date = date('Y-m-d H:i:s');
        }

        $model->status = $new_state;
        if (!$model->save()) {
            throw new CHttpException(403, 'An error occurred when attempting to change the status');
        }

        echo self::RETURN_CODE_OK;
    }

    /**
     * Get a HTML list of all trials for the specified trial type.
     * @param $type string The trial type.
     */
    public function actionGetTrialList($type)
    {
        $trials = Trial::getTrialList($type);

        // Always pass the default list option (Any)
        echo CHtml::tag('option', array('value' => ''), CHtml::encode('Any'), true);

        // Pass all distinct trials that fall under the selected type. Perfectly OK for there to be no values here.
        foreach ($trials as $value => $key) {
            echo CHtml::tag('option', array('value' => $value), CHtml::encode($key), true);
        }
    }

    /**
     * Gets a JSON encoded list of users that can be assigned to the Trial and that match the search term.
     * Users will not be returned if they are already assigned to the trial, or if they don't have the "View Trial" permission.
     *
     * @param integer $id The trial ID
     * @param string $term The term to search for
     * @return string A JSON encoded array of users with id, label, username and value
     */
    public function actionUserAutoComplete($id, $term)
    {
        $model = $this->loadModel($id);

        $res = array();
        $term = strtolower($term);

        $criteria = new \CDbCriteria;
        $criteria->compare('LOWER(username)', $term, true, 'OR');
        $criteria->compare('LOWER(first_name)', $term, true, 'OR');
        $criteria->compare('LOWER(last_name)', $term, true, 'OR');

        $criteria->addCondition('id NOT IN (SELECT user_id FROM user_trial_permission WHERE trial_id = ' . $model->id . ')');
        $criteria->addCondition("EXISTS( SELECT * FROM authassignment WHERE userid = id AND itemname = 'View Trial')");

        $words = explode(' ', $term);
        if (count($words) > 1) {
            $first_criteria = new \CDbCriteria();
            $first_criteria->compare('LOWER(first_name)', $words[0], true);
            $first_criteria->compare('LOWER(last_name)', implode(" ", array_slice($words, 1, count($words) - 1)), true);
            $last_criteria = new \CDbCriteria();
            $last_criteria->compare('LOWER(first_name)', $words[count($words) - 1], true);
            $last_criteria->compare('LOWER(last_name)', implode(" ", array_slice($words, 0, count($words) - 2)), true);
            $first_criteria->mergeWith($last_criteria, 'OR');
            $criteria->mergeWith($first_criteria, 'OR');
        }

        $criteria->compare('active', true);

        foreach (\User::model()->findAll($criteria) as $user) {

            $res[] = array(
                'id' => $user->id,
                'label' => $user->getFullNameAndTitle(),
                'value' => $user->getFullName(),
                'username' => $user->username,
            );
        }

        echo \CJSON::encode($res);
    }
}
