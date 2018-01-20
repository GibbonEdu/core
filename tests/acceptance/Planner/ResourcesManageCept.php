<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('add, edit and delete resources');
$I->loginAsAdmin();
$I->amOnModulePage('Planner', 'resources_manage.php');

// Add ------------------------------------------------
$I->clickNavigation('Add');
$I->seeBreadcrumb('Add Resource');

$addFormValues = array(
    'type'        => 'HTML',
    'html'        => '<p>Testing</p>',
    'name'        => 'HTML Test',
    'description' => 'This is a test.',
);

$I->fillField('tags', 'TestTag');
$I->selectFromDropdown('category', 2);

$I->submitForm('#content form', $addFormValues, 'Submit');
$I->seeSuccessMessage();

$gibbonResourceID = $I->grabEditIDFromURL();

// Edit ------------------------------------------------
$I->amOnModulePage('Planner', 'resources_manage_edit.php', array('gibbonResourceID' => $gibbonResourceID, 'search' => ' '));
$I->seeBreadcrumb('Edit Resource');

$I->seeInFormFields('#content form', $addFormValues);
$I->see('TestTag');

$editFormValues = array(
    'html'        => '<p>Testing Also</p>',
    'name'        => 'HTML Test Too',
    'description' => 'This is also a test.',
);

$I->fillField('tags', 'TestTag');
$I->fillField('type', 'HTML');

$I->submitForm('#content form', $editFormValues, 'Submit');
$I->seeSuccessMessage();

$I->seeInFormFields('#content form', $editFormValues);

// Delete ------------------------------------------------
$I->deleteFromDatabase('gibbonResource', ['gibbonResourceID' => $gibbonResourceID]);
