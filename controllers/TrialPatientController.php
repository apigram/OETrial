<?php

/**
 * Class TrialPatientController
 */
class TrialPatientController extends BaseModuleController
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
     * @throws Exception Thrown the model cannot be saved
     */
    public function actionChangeStatus($id, $new_status)
    {
        $trialPatient = $this->loadModel($id);
        $result = $trialPatient->changeStatus($new_status);
        echo $result;
    }

    /**
     * Changes the external_trial_identifier of a TrialPatient record
     *
     * @param int $id The ID of the TrialPatient record
     * @param string $new_external_id The new external reference
     * @throws Exception Thrown if an error occurs when saving the model or if it cannot be found
     */
    public function actionUpdateExternalId($id, $new_external_id)
    {
        $model = $this->loadModel($id);
        $model->updateExternalId($new_external_id);
    }

    /**
     * Updates the treatment type of a trial-patient with a new treatment type
     *
     * @param int $id The ID of the TrialPatient model to update
     * @param int $treatment_type The new treatment type
     * @throws Exception Thrown if an error occurs when saving the TrialPatient
     */
    public function actionUpdateTreatmentType($id, $treatment_type)
    {
        $model = $this->loadModel($id);
        $model->updateTreatmentType($treatment_type);
    }
}
