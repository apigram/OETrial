<?php

/**
 * Class TrialController
 */
class TrialController extends BaseModuleController
{
    /**
     * @return array action filters
     */
    public function filters()
    {
        return array(
            'accessControl',
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
     * @param int $id the ID of the model to be displayed
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
            'sort_by' => (int)Yii::app()->request->getParam('sort_by', null),
            'sort_dir' => (int)Yii::app()->request->getParam('sort_dir', null),
        ));
    }

    /**
     * Returns the data model based on the primary key given in the GET variable.
     * If the data model is not found, an HTTP exception will be raised.
     * @param int $id the ID of the model to be loaded
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
        $model->trial_type = Trial::TRIAL_TYPE_NON_INTERVENTION;
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
     * @param int $id the ID of the model to be updated
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
            'sort_by' => (int)Yii::app()->request->getParam('sort_by', null),
            'sort_dir' => (int)Yii::app()->request->getParam('sort_dir', null),
        ));
    }

    /**
     * Displays the permissions screen
     *
     * @param int $id The ID of the Trial
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
     * Adds a patient to the trial
     *
     * @param int $id The ID of the Trial to add to
     * @param int $patient_id The ID of the patient to add
     * @param int $patient_status The initial trial status for the patient (default to shortlisted)
     * @throws Exception Thrown if an error occurs when saving the TrialPatient record
     */
    public function actionAddPatient($id, $patient_id, $patient_status = TrialPatient::STATUS_SHORTLISTED)
    {
        $trial = $this->loadModel($id);
        /* @var Patient $patient */
        $patient = Patient::model()->findByPk($patient_id);
        $trial->addPatient($patient, $patient_status);
    }

    /**
     * @param int $id The id of the trial to remove
     * @param int $patient_id The id of the patient to remove
     * @throws CHttpException Raised when the record cannot be found
     * @throws Exception Raised when an error occurs when removing the record
     */
    public function actionRemovePatient($id, $patient_id)
    {
        $trial = $this->loadModel($id);
        $trial->removePatient($patient_id);
    }

    /**
     * Creates a new Trial Permission using values in $_POST
     *
     * @param int $id The Trial ID
     * @param int $user_id The ID of the User record to add the permission to
     * @param int $permission The permission level the user will be given (view/edit/manage)
     * @param string $role The role the user will have
     * @throws Exception Thrown if the permission couldn't be saved
     */
    public function actionAddPermission($id, $user_id, $permission, $role)
    {
        $trial = $this->loadModel($id);
        $result = $trial->addUserPermission($user_id, $permission, $role);
        echo $result;
    }

    /**
     * Removes a UserTrialPermission
     *
     * @param int $id The ID of the trial
     * @param int $permission_id The ID of the permission to remove
     * @throws CHttpException Thrown if the permission cannot be found
     * @throws Exception Thrown if the permission cannot be deleted
     */
    public function actionRemovePermission($id, $permission_id)
    {
        $trial = $this->loadModel($id);
        $result = $trial->removeUserPermission($permission_id);
        echo $result;
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
     * @param int $id The ID of the trial to transition
     * @param int $new_state The new state to transition to (must be a valid state within Trial::getAllowedStatusRange()
     * @throws CHttpException Thrown if an error occurs when saving
     */
    public function actionTransitionState($id, $new_state)
    {
        $trial = $this->loadModel($id);
        $result = $trial->transitionState($new_state);
        echo $result;
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
     * @param int $id The trial ID
     * @param string $term The term to search for
     * @return string A JSON encoded array of users with id, label, username and value
     * @throws CHttpException Thrown if an error occurs when loading the model
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
            $first_criteria->compare('LOWER(last_name)', implode(' ', array_slice($words, 1, count($words) - 1)), true);
            $last_criteria = new \CDbCriteria();
            $last_criteria->compare('LOWER(first_name)', $words[count($words) - 1], true);
            $last_criteria->compare('LOWER(last_name)', implode(' ', array_slice($words, 0, count($words) - 2)), true);
            $first_criteria->mergeWith($last_criteria, 'OR');
            $criteria->mergeWith($first_criteria, 'OR');
        }

        $criteria->compare('active', true);

        /* @var User $user */
        foreach (User::model()->findAll($criteria) as $user) {

            $res[] = array(
                'id' => $user->id,
                'label' => $user->getFullNameAndTitle(),
                'value' => $user->getFullName(),
                'username' => $user->username,
            );
        }

        echo CJSON::encode($res);
    }
}
