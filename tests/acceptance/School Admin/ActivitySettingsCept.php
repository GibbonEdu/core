<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('update Activity Settings');
$I->loginAsAdmin();
$I->amOnModulePage('School Admin', 'activitySettings.php');

// Grab Original Settings --------------------------------------

$originalFormValues = $I->grabAllFormValues();
$I->seeInFormFields('#content form', $originalFormValues);

// Make Changes ------------------------------------------------

$newFormValues = array(
    'dateType'                      => 'Term',
    'maxPerTerm'                    => '3',
    'access'                        => 'View',
    'payment'                       => 'Single + Per Activity',
    'enrolmentType'                 => 'Selection',
    'backupChoice'                  => 'Y',
    'activityTypes'                 => 'A,B,C,D',
    'disableExternalProviderSignup' => 'Y',
    'hideExternalProviderCost'      => 'Y',
);

$I->submitForm('#content form', $newFormValues, 'Submit');

// Verify Results ----------------------------------------------

$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $newFormValues);

// Restore Original Settings -----------------------------------

$I->submitForm('#content form', $originalFormValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $originalFormValues);
