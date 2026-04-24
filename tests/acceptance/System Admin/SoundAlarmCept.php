<?php
/**
 * @covers modules/System Admin/alarm.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('trigger an alarm');
$I->loginAsAdmin();
$I->amOnModulePage('System Admin', 'alarm.php');

// Upload alarm sound -----------------------------------

$I->attachFile('file', 'attachment.txt');
$I->selectOption('alarm', 'General');
$I->click('Submit');

$I->see('Your request was completed successfully.', '.success');
$I->seeOptionIsSelected('alarm', 'General');

$file = $I->grabFromDatabase('gibbonSetting', 'value', ['scope' => 'System Admin', 'name' => 'customAlarmSound']);
$I->assertNotEmpty($file);

// Turn off alarm --------------------------------------

$I->selectOption('alarm', 'None');
$I->click('Submit');

$I->see('Your request was completed successfully.', '.success');

// Cleanup ------------------------------------------------
$I->deleteFile('../'.$file);
$I->updateInDatabase('gibbonSetting', ['value' => ''], ['scope' => 'System Admin', 'name' => 'customAlarmSound']);
