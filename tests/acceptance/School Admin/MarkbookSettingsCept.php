<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('update Markbook Settings');
$I->loginAsAdmin();
$I->amOnModulePage('School Admin', 'markbookSettings.php');

// Grab Original Settings --------------------------------------

$originalFormValues = $I->grabAllFormValues();
$I->seeInFormFields('#content form', $originalFormValues);

// Make Changes ------------------------------------------------

$newFormValues = array(
    'enableEffort'                   => 'Y',
    'enableRubrics'                  => 'Y',
    'enableColumnWeighting'          => 'Y',
    'enableDisplayCumulativeMarks'   => 'Y',
    'enableRawAttainment'            => 'Y',
    'markbookType'                   => 'Test1,Test2,Test3',
    'enableGroupByTerm'              => 'Y',
    'attainmentAlternativeName'      => 'attainmentTest',
    'attainmentAlternativeNameAbrev' => 'aTest',
    'effortAlternativeName'          => 'effortTest',
    'effortAlternativeNameAbrev'     => 'eTest',
    'showStudentAttainmentWarning'   => 'Y',
    'showStudentEffortWarning'       => 'Y',
    'showParentAttainmentWarning'    => 'Y',
    'showParentEffortWarning'        => 'Y',
    'personalisedWarnings'           => 'Y',
);

$I->submitForm('#content form', $newFormValues, 'Submit');

// Verify Results ----------------------------------------------

$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $newFormValues);

// Restore Original Settings -----------------------------------

$I->submitForm('#content form', $originalFormValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $originalFormValues);
