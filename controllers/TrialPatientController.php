<?php

/**
 * Class TrialPatientController
 */
class TrialPatientController extends BaseModuleController
{
    /**
     * The return value for a actionChangeStatus() if the status change is successful
     */
    const STATUS_CHANGE_CODE_OK = 'success';
    /**
     * The return code for actionChangeStatus() if the patient is already in another intervention trial
     */
    const STATUS_CHANGE_CODE_ALREADY_IN_INTERVENTION = 'already_in_intervention';

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
                'actions' => array('changeStatus', 'updateExternalId', 'updateTreatmentType'),
                'expression' => 'TrialPatient::checkTrialPatientAccess($user, Yii::app()->getRequest()->getQuery("id"), UserTrialPermission::PERMISSION_EDIT)',
            ),
            array(
                'deny',  // deny all users
                'users' => array('*'),
            ),
        );
    }

    /**
     * Returns the data model based on the primary key given in the GET variable.
     * If the data model is not found, an HTTP exception will be raised.
     * @param int $id the ID of the model to be loaded
     * @return TrialPatient the loaded model
     * @throws CHttpException
     */
    public function loadModel($id)
    {
        /* @var TrialPatient $model */
        $model = TrialPatient::model()->findByPk($id);
        if ($model === null) {
            throw new CHttpException(404, 'The requested page does not exist.');
        }

        return $model;
    }

    /**
     * Performs the AJAX validation.
     * @param TrialPatient $model the model to be validated
     */
    protected function performAjaxValidation($model)
    {
        if (isset($_POST['ajax']) && $_POST['ajax'] === 'trial-patient-form') {
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }
    }

    /**
     * Changes the status of a patient in a trial to a given value
     * @param int $id The id of the TrialPatient to change the status for
     * @param int $new_status The new status of the TrialPatient
     * @throws CHttpException Thrown the model cannot be saved
     */
    public function actionChangeStatus($id, $new_status)
    {
        $model = $this->loadModel($id);

        if ((int)$new_status === TrialPatient::STATUS_ACCEPTED &&
            (int)$model->trial->trial_type === Trial::TRIAL_TYPE_INTERVENTION &&
            $model->patient->isCurrentlyInInterventionTrial()
        ) {
            echo self::STATUS_CHANGE_CODE_ALREADY_IN_INTERVENTION;

            return;
        }

        $model->patient_status = $new_status;
        if (!$model->save()) {
            throw new CHttpException(400,
                'An error occurred when saving the model: ' . print_r($model->getErrors(), true));
        }

        echo self::STATUS_CHANGE_CODE_OK;
    }


    /**
     * Changes the external_trial_identifier of a TrialPatient record
     *
     * @param int $id The ID of the TrialPatient record
     * @param string $new_external_id The new external reference
     * @throws CHttpException Thrown if an error occurs when saving the model or if it cannot be found
     */
    public function actionUpdateExternalId($id, $new_external_id)
    {
        $model = $this->loadModel($id);
        $model->external_trial_identifier = $new_external_id;

        if (!$model->save()) {
            throw new CHttpException(400,
                'An error occurred when saving the model: ' . print_r($model->getErrors(), true));
        }
    }

    /**
     * Updates the treatment type of a trial-patient with a new treatment type
     *
     * @param int $id The ID of the TrialPatient model to update
     * @param int $treatment_type The new treatment type
     * @throws CHttpException Thrown if an error occurs when saving the TrialPatient
     */
    public function actionUpdateTreatmentType($id, $treatment_type)
    {
        $model = $this->loadModel($id);

        if ((int)$model->trial->status !== Trial::STATUS_CLOSED) {
            throw new CHttpException(400, 'You cannot change the treatment type until the trial is closed.');
        }

        $model->treatment_type = $treatment_type;

        if (!$model->save()) {
            throw new CHttpException(400,
                'An error occurred when saving the model: ' . print_r($model->getErrors(), true));
        }
    }
}
