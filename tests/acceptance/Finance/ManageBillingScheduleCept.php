<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('add, edit and delete a billing schedule');
$I->loginAsAdmin();
$I->amOnModulePage('Finance', 'billingSchedule_manage.php');

// Add ------------------------------------------------
$I->clickNavigation('Add');
$I->seeBreadcrumb('Add Entry');

$addFormValues = array(
    'name'             => 'Test Schedule',
    'active'           => 'Y',
    'description'      => 'This is a test.',
    'invoiceIssueDate' => '01/01/2020',
    'invoiceDueDate'   => '01/12/2020',
);

$I->submitForm('#content form', $addFormValues, 'Submit');
$I->seeSuccessMessage();

$gibbonFinanceBillingScheduleID = $I->grabEditIDFromURL();
$gibbonSchoolYearID = $I->grabValueFromURL('gibbonSchoolYearID');

// Edit ------------------------------------------------
$I->amOnModulePage('Finance', 'billingSchedule_manage_edit.php', array('gibbonFinanceBillingScheduleID' => $gibbonFinanceBillingScheduleID, 'gibbonSchoolYearID' => $gibbonSchoolYearID, 'search' => ''));
$I->seeBreadcrumb('Edit Entry');

$I->seeInFormFields('#content form', $addFormValues);

$editFormValues = array(
    'name'             => 'Test Schedule Also',
    'active'           => 'N',
    'description'      => 'This is also a test.',
    'invoiceIssueDate' => '30/01/2020',
    'invoiceDueDate'   => '30/12/2020',
);

$I->submitForm('#content form', $editFormValues, 'Submit');
$I->seeSuccessMessage();

// Delete ------------------------------------------------
// No manual option
$I->deleteFromDatabase('gibbonFinanceBillingSchedule', ['gibbonFinanceBillingScheduleID' => $gibbonFinanceBillingScheduleID]);