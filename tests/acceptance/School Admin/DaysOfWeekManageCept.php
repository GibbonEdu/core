<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('update Days Of Week');
$I->loginAsAdmin();
$I->amOnModulePage('School Admin', 'daysOfWeek_manage.php');

// Grab Original Settings --------------------------------------

$originalFormValues = $I->grabAllFormValues();
$I->seeInFormFields('#content form', $originalFormValues);

// Make Changes ------------------------------------------------

$newFormValues = array(
    'MondayschoolDay'       => 'Y',
    'MondayschoolOpenH'     => '01',
    'MondayschoolOpenM'     => '00',
    'MondayschoolStartH'    => '02',
    'MondayschoolStartM'    => '15',
    'MondayschoolEndH'      => '03',
    'MondayschoolEndM'      => '30',
    'MondayschoolCloseH'    => '04',
    'MondayschoolCloseM'    => '45',
    
    'TuesdayschoolDay'      => 'Y',
    'TuesdayschoolOpenH'    => '02',
    'TuesdayschoolOpenM'    => '15',
    'TuesdayschoolStartH'   => '03',
    'TuesdayschoolStartM'   => '30',
    'TuesdayschoolEndH'     => '04',
    'TuesdayschoolEndM'     => '45',
    'TuesdayschoolCloseH'   => '05',
    'TuesdayschoolCloseM'   => '00',
    
    'WednesdayschoolDay'    => 'Y',
    'WednesdayschoolOpenH'  => '03',
    'WednesdayschoolOpenM'  => '30',
    'WednesdayschoolStartH' => '04',
    'WednesdayschoolStartM' => '45',
    'WednesdayschoolEndH'   => '05',
    'WednesdayschoolEndM'   => '00',
    'WednesdayschoolCloseH' => '06',
    'WednesdayschoolCloseM' => '15',
    
    'ThursdayschoolDay'     => 'Y',
    'ThursdayschoolOpenH'   => '04',
    'ThursdayschoolOpenM'   => '45',
    'ThursdayschoolStartH'  => '05',
    'ThursdayschoolStartM'  => '00',
    'ThursdayschoolEndH'    => '06',
    'ThursdayschoolEndM'    => '15',
    'ThursdayschoolCloseH'  => '07',
    'ThursdayschoolCloseM'  => '30',
    
    'FridayschoolDay'       => 'Y',
    'FridayschoolOpenH'     => '05',
    'FridayschoolOpenM'     => '00',
    'FridayschoolStartH'    => '06',
    'FridayschoolStartM'    => '15',
    'FridayschoolEndH'      => '07',
    'FridayschoolEndM'      => '30',
    'FridayschoolCloseH'    => '08',
    'FridayschoolCloseM'    => '45',
    
    'SaturdayschoolDay'     => 'Y',
    'SaturdayschoolOpenH'   => '06',
    'SaturdayschoolOpenM'   => '15',
    'SaturdayschoolStartH'  => '07',
    'SaturdayschoolStartM'  => '30',
    'SaturdayschoolEndH'    => '07',
    'SaturdayschoolEndM'    => '45',
    'SaturdayschoolCloseH'  => '09',
    'SaturdayschoolCloseM'  => '00',
    
    'SundayschoolDay'       => 'Y',
    'SundayschoolOpenH'     => '07',
    'SundayschoolOpenM'     => '30',
    'SundayschoolStartH'    => '08',
    'SundayschoolStartM'    => '45',
    'SundayschoolEndH'      => '09',
    'SundayschoolEndM'      => '00',
    'SundayschoolCloseH'    => '10',
    'SundayschoolCloseM'    => '15',
);

$I->submitForm('#content form', $newFormValues, 'Submit');

// Verify Results ----------------------------------------------

$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $newFormValues);

// Restore Original Settings -----------------------------------

$I->submitForm('#content form', $originalFormValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $originalFormValues);
