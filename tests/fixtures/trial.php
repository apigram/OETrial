<?php

return array(
    'trial1' => array(
        'id' => 1,
        'name' => 'Trial 1',
        'description' => 'Trial Description',
        'owner_user_id' => $this->getRecord('user', 'user1')->id,
        'status' => Trial::STATUS_OPEN,
        'trial_type' => Trial::TRIAL_TYPE_INTERVENTION,
    ),
    'trial2' => array(
        'id' => 2,
        'name' => 'Trial 2',
        'description' => 'Trial Description',
        'owner_user_id' => $this->getRecord('user', 'user1')->id,
        'status' => Trial::STATUS_OPEN,
        'trial_type' => Trial::TRIAL_TYPE_INTERVENTION,
    ),
    'trial3' => array(
        'id' => 3,
        'name' => 'Trial 3',
        'description' => 'Trial Description',
        'owner_user_id' => $this->getRecord('user', 'user1')->id,
        'status' => Trial::STATUS_CLOSED,
        'trial_type' => Trial::TRIAL_TYPE_INTERVENTION,
    ),
);
