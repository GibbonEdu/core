<?php
/**
 * @covers modules/School Admin/formalAssessmentSettings.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('update formal assessment settings');
$I->loginAsAdmin();
$I->amOnModulePage('School Admin', 'formalAssessmentSettings.php');
$I->seeBreadcrumb('Formal Assessment Settings');

// Grab original values
$originalFormValues = $I->grabAllFormValues('#content form');

// Verify original values are displayed
$I->seeInFormFields('#content form', $originalFormValues);

// Submit modified values (use array_replace to modify only specific fields)
$formValues = array_replace($originalFormValues, array(
    'internalAssessmentTypes' => 'End of Year Exam, Mid-Year Exam',
));

$I->submitForm('#content form', $formValues, 'Submit');
$I->seeSuccessMessage();

// Restore original settings
$I->amOnModulePage('School Admin', 'formalAssessmentSettings.php');
$I->submitForm('#content form', $originalFormValues, 'Submit');
$I->seeSuccessMessage();
