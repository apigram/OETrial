<?php

/**
 * This is the model class for table "user_trial_permission".
 *
 * The followings are the available columns in table 'user_trial_permission':
 * @property integer $id
 * @property integer $user_id
 * @property integer $trial_id
 * @property string $permission
 * @property string $role
 * @property string $last_modified_user_id
 * @property string $last_modified_date
 * @property string $created_user_id
 * @property string $created_date
 *
 * The followings are the available model relations:
 * @property User $user
 * @property User $createdUser
 * @property User $lastModifiedUser
 * @property Trial $trial
 */
class UserTrialPermission extends BaseActiveRecordVersioned
{
    const PERMISSION_VIEW = 0;
    const PERMISSION_EDIT = 1;
    const PERMISSION_MANAGE = 2;

    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'user_trial_permission';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('user_id, trial_id, permission', 'required'),
            array('trial_id', 'numerical', 'integerOnly' => true),
            array('user_id, permission, last_modified_user_id, created_user_id', 'length', 'max' => 10),
            array('role', 'length', 'max' => 255),
            array('last_modified_date, created_date', 'safe'),
        );
    }

    /**
     * Returns an array of all of the allowable values of "permission"
     * @return integer[] The list of permissions
     */
    public static function getAllowedPermissionRange()
    {
        return array(
            self::PERMISSION_MANAGE,
            self::PERMISSION_EDIT,
            self::PERMISSION_VIEW,
        );
    }

    /**
     * Returns an array withs keys of the allowable values of permission and values of the label for that status
     * @return array The array of permission id/label key/value pairs
     */
    public static function getPermissionOptions()
    {
        return array(
            self::PERMISSION_MANAGE => 'Manage, Edit and View',
            self::PERMISSION_EDIT => 'Edit and View',
            self::PERMISSION_VIEW => 'View',
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
            'user' => array(self::BELONGS_TO, 'User', 'user_id'),
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
            'user_id' => 'User',
            'trial_id' => 'Trial',
            'permission' => 'Permission',
            'role' => 'User Role',
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
     * @return UserTrialPermission the static model class
     */
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }
}
