<?php
/**
 * @covers modules/Staff/substitutes_manage.php
 * @covers modules/Staff/substitutes_manage_add.php
 * @covers modules/Staff/substitutes_manage_edit.php
 * @covers modules/Staff/substitutes_manage_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('add, edit and delete a substitute');
$I->loginAsAdmin();
$I->amOnModulePage('Staff', 'substitutes_manage.php');
$I->seeBreadcrumb('Manage Substitutes');

// Add ------------------------------------------------
$I->click('Add', 'a');
$I->seeBreadcrumb('Add');

$I->selectFromDropdown('gibbonPersonID', 2);

$formValues = array(
    'type' => 'Internal',
    'details' => 'Test substitute details',
    'priority' => '1',
);

$I->submitForm('#content form', $formValues, 'Submit');
$I->seeSuccessMessage();

$gibbonSubstituteID = $I->grabEditIDFromURL();

// Edit ------------------------------------------------
$I->amOnModulePage('Staff', 'substitutes_manage_edit.php', array(
    'gibbonSubstituteID' => $gibbonSubstituteID
));
$I->seeBreadcrumb('Edit');

$I->seeInFormFields('#content form', array(
    'type' => 'Internal',
));

$formValues = array(
    'type' => 'External',
    'details' => 'Updated substitute details',
    'priority' => '2',
);

$I->submitForm('#content form', $formValues, 'Submit');
$I->seeSuccessMessage();

// Delete ------------------------------------------------
$I->amOnModulePage('Staff', 'substitutes_manage_delete.php', array(
    'gibbonSubstituteID' => $gibbonSubstituteID
));

$I->click('Yes');
$I->seeSuccessMessage();
