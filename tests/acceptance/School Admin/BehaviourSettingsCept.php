<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('update Markbook Settings');
$I->loginAsAdmin();
$I->amOnModulePage('School Admin', 'behaviourSettings.php');

// Grab Original Settings --------------------------------------

$originalFormValues = $I->grabAllFormValues();
$I->seeInFormFields('#content form', $originalFormValues);

// Make Changes ------------------------------------------------

$newFormValues = array(
    'enableDescriptors'             => 'Y',
    'positiveDescriptors'           => 'Positive1,Positive2,Positive3',
    'negativeDescriptors'           => 'Negative1,Negative2,Negative3',
    'enableLevels'                  => 'Y',
    'levels'                        => 'Level1,Level2,Level3',
    'enableBehaviourLetters'        => 'Y',
    'behaviourLettersLetter1Count'  => '4',
    'behaviourLettersLetter1Text'   => 'Letter Test 1',
    'behaviourLettersLetter2Count'  => '8',
    'behaviourLettersLetter2Text'   => 'Letter Test 2',
    'behaviourLettersLetter3Count'  => '12',
    'behaviourLettersLetter3Text'   => 'Letter Test 3',
    'policyLink'                    => 'http://test',
);

$I->submitForm('#content form', $newFormValues, 'Submit');

// Verify Results ----------------------------------------------

$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $newFormValues);

// Restore Original Settings -----------------------------------

$I->submitForm('#content form', $originalFormValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $originalFormValues);
