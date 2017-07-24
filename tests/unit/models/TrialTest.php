<?php

class TrialTest extends CDbTestCase
{
    public $fixtures = array(
        'user' => 'User',
        'trial' => 'Trial',
        'patient' => 'Patient',
        'trial_patient' => 'TrialPatient',
        'user_trial_permission' => 'UserTrialPermission',
    );

    public static function setupBeforeClass()
    {
        Yii::app()->getModule('OETrial');
    }

    public function setUp()
    {
        parent::setUp();
    }

    public function testTitle()
    {
        $trial = new Trial();
        $trial->name = null;
        $this->assertFalse($trial->save(), 'A Trial cannot be saved with a null name');
    }

    public function testCreatedDate()
    {
        $trial = new Trial();
        $trial->started_date = date('Y-m-d', strtotime('2012-12-21'));
        $this->assertEquals($trial->getStartedDateForDisplay(), '21 Dec 2012');

        $trial->started_date = date('Y-m-d', strtotime('1970-1-1'));
        $this->assertEquals($trial->getStartedDateForDisplay(), '1 Jan 1970');

        $trial->started_date = null;
        $this->assertEquals($trial->getStartedDateForDisplay(), 'Pending');
    }

    public function testClosedDate()
    {
        $trial = new Trial();
        $trial->started_date = date('Y-m-d', strtotime('1970-01-01'));
        $trial->closed_date = date('Y-m-d', strtotime('2012-12-21'));
        $this->assertEquals($trial->getClosedDateForDisplay(), '21 Dec 2012');

        $trial->closed_date = date('Y-m-d', strtotime('1970-1-1'));
        $this->assertEquals($trial->getClosedDateForDisplay(), '1 Jan 1970');

        $trial->started_date = null;
        $trial->closed_date = null;
        $this->assertNull($trial->getClosedDateForDisplay());

        $trial->started_date = date('Y-m-d', strtotime('1970-01-01'));
        $trial->closed_date = null;
        $this->assertEquals($trial->getClosedDateForDisplay(), 'present');
    }

    public function testDataProvidersExist()
    {
        $providers = $this->trial('trial1')->getPatientDataProviders(null, null);
        $this->assertArrayHasKey(TrialPatient::STATUS_ACCEPTED, $providers);

        $this->assertGreaterThan(0, count($providers), 'There should be at least one data provider returned');

        foreach (TrialPatient::getAllowedStatusRange() as $status) {
            $this->assertArrayHasKey($status, $providers, 'A data provider of each patient status should be returned');
        }
    }

    public function testDataProviderContent()
    {
        $providers = $this->trial('trial1')->getPatientDataProviders(null, null);

        /* @var CActiveDataProvider $shortlistedPatientProvider */
        $shortlistedPatientProvider = $providers[TrialPatient::STATUS_SHORTLISTED];
        $data = $shortlistedPatientProvider->getData();
        $this->assertCount(2, $data, 'Trial1 should have exactly 2 shortlisted patients');
    }

    public function testNoPatientsInDataProvider()
    {
        $providers = $this->trial('trial2')->getPatientDataProviders(null, null);

        /* @var CActiveDataProvider $shortlistedPatientProvider */
        $shortlistedPatientProvider = $providers[TrialPatient::STATUS_SHORTLISTED];
        $data = $shortlistedPatientProvider->getData();
        $this->assertCount(0, $data, 'Trial2 should have no shortlisted patients');
    }

    public function testDataProviderNameOrdering()
    {
        $shortlistedPatientProvider = $this->trial('trial1')->getPatientDataProvider(TrialPatient::STATUS_SHORTLISTED,
            'name', 'asc');
        $data = $shortlistedPatientProvider->getData();
        $this->assertCount(2, $data, 'There should be two patients in trial1');

        $this->assertLessThan($data[1]->patient->last_name, $data[0]->patient->last_name,
            'The list of patients should be sorted alphabetically by last name');
    }

    public function testDataProviderNameOrderingDesc()
    {
        $shortlistedPatientProvider = $this->trial('trial1')->getPatientDataProvider(TrialPatient::STATUS_SHORTLISTED,
            'name', 'desc');
        $data = $shortlistedPatientProvider->getData();
        $this->assertCount(2, $data, 'There should be two patients in trial1');

        $this->assertGreaterThan($data[1]->patient->last_name, $data[0]->patient->last_name,
            'The list of patients should be sorted alphabetically descending by last name');
    }

    public function testDataProviderAgeOrdering()
    {
        $shortlistedPatientProvider = $this->trial('trial1')->getPatientDataProvider(TrialPatient::STATUS_SHORTLISTED,
            'age', 'asc');
        $data = $shortlistedPatientProvider->getData();
        $this->assertCount(2, $data, 'There should be two patients in trial1');

        $this->assertLessThan($data[1]->patient->getAge(), $data[0]->patient->getAge(),
            'The list of patients should be sorted by age ascending');
    }

    public function testDataProviderAgeOrderingDesc()
    {
        $shortlistedPatientProvider = $this->trial('trial1')->getPatientDataProvider(TrialPatient::STATUS_SHORTLISTED,
            'age', 'desc');
        $data = $shortlistedPatientProvider->getData();
        $this->assertCount(2, $data, 'There should be two patients in trial1');

        $this->assertGreaterThan($data[1]->patient->getAge(), $data[0]->patient->getAge(),
            'The list of patients should be sorted by age descending');
    }

    public function testDataProviderExternalRefOrdering()
    {
        $shortlistedPatientProvider = $this->trial('trial1')->getPatientDataProvider(TrialPatient::STATUS_SHORTLISTED,
            'external_reference', 'asc');
        $data = $shortlistedPatientProvider->getData();
        $this->assertCount(2, $data, 'There should be two patients in trial1');

        $this->assertLessThan($data[1]->external_trial_identifier, $data[0]->external_trial_identifier,
            'The list of patients should be sorted by external id ascending');
    }

    public function testDataProviderExternalRefOrderingDesc()
    {
        $shortlistedPatientProvider = $this->trial('trial1')->getPatientDataProvider(TrialPatient::STATUS_SHORTLISTED,
            'external_reference', 'desc');
        $data = $shortlistedPatientProvider->getData();
        $this->assertCount(2, $data, 'There should be two patients in trial1');

        $this->assertGreaterThan($data[1]->external_trial_identifier, $data[0]->external_trial_identifier,
            'The list of patients should be sorted by external id descending');
    }

    public function testHasShortlistedPatients()
    {
        $this->assertTrue($this->trial('trial1')->hasShortlistedPatients(),
            'Trial1 should have at least one shortlisted patient');
        $this->assertFalse($this->trial('trial2')->hasShortlistedPatients(),
            'Trial2 should have no shortlisted patients');
    }

    public function testCheckTrialAccessManage()
    {
        $this->assertTrue(Trial::checkTrialAccess($this->user('user1'), $this->trial('trial1')->id,
            UserTrialPermission::PERMISSION_VIEW), 'user1 should have view access to trial1');

        $this->assertTrue(Trial::checkTrialAccess($this->user('user1'), $this->trial('trial1')->id,
            UserTrialPermission::PERMISSION_EDIT), 'user1 should have edit access to trial1');

        $this->assertTrue(Trial::checkTrialAccess($this->user('user1'), $this->trial('trial1')->id,
            UserTrialPermission::PERMISSION_MANAGE), 'user1 should have manage access to trial1');
    }

    public function testCheckTrialAccessView()
    {
        $this->assertTrue(Trial::checkTrialAccess($this->user('user2'), $this->trial('trial1')->id,
            UserTrialPermission::PERMISSION_VIEW), 'user2 should have view access to trial1');

        $this->assertFalse(Trial::checkTrialAccess($this->user('user2'), $this->trial('trial1')->id,
            UserTrialPermission::PERMISSION_EDIT), 'user2 should not have edit access to trial1');

        $this->assertFalse(Trial::checkTrialAccess($this->user('user2'), $this->trial('trial1')->id,
            UserTrialPermission::PERMISSION_MANAGE), 'user2 should not have manage access to trial1');
    }

    public function testCheckTrialAccessEdit()
    {
        $this->assertTrue(Trial::checkTrialAccess($this->user('user3'), $this->trial('trial1')->id,
            UserTrialPermission::PERMISSION_VIEW), 'user3 should have view access to trial1');

        $this->assertTrue(Trial::checkTrialAccess($this->user('user3'), $this->trial('trial1')->id,
            UserTrialPermission::PERMISSION_EDIT), 'user3 should not have edit access to trial1');

        $this->assertFalse(Trial::checkTrialAccess($this->user('user3'), $this->trial('trial1')->id,
            UserTrialPermission::PERMISSION_MANAGE), 'user3 should not have manage access to trial1');
    }

    public function testGetTrialAccess()
    {
        /* @var Trial $trial */
        $trial = $this->trial('trial1');
        $this->assertEquals(UserTrialPermission::PERMISSION_MANAGE, $trial->getTrialAccess($this->user('user1')));
        $this->assertEquals(UserTrialPermission::PERMISSION_VIEW, $trial->getTrialAccess($this->user('user2')));
        $this->assertEquals(UserTrialPermission::PERMISSION_EDIT, $trial->getTrialAccess($this->user('user3')));
    }

    public function tearDown()
    {
        parent::tearDown();
    }
}