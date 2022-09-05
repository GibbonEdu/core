<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('update Alert Level Settings');
$I->loginAsAdmin();
$I->amOnModulePage('School Admin', 'alertLevelSettings.php');

// Grab Original Settings --------------------------------------

$originalFormValues = $I->grabAllFormValues();
$I->seeInFormFields('#content form', $originalFormValues);

// Make Changes ------------------------------------------------

$newFormValues = array(
    'name0'           => 'LowTest',
    'nameShort0'      => 'LT',
    'color0'          => '000000',
    'colorBG0'        => '111111',
    'description0'    => 'Low Test',

    'name1'           => 'MedTest',
    'nameShort1'      => 'MT',
    'color1'          => '222222',
    'colorBG1'        => '333333',
    'description1'    => 'Med Test',

    'name2'           => 'HighTest',
    'nameShort2'      => 'HT',
    'color2'          => '444444',
    'colorBG2'        => '555555',
    'description2'    => 'High Test',
);

$I->submitForm('#content form', $newFormValues, 'Submit');

// Verify Results ----------------------------------------------

$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $newFormValues);

// Restore Original Settings -----------------------------------

$I->submitForm('#content form', $originalFormValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $originalFormValues);
