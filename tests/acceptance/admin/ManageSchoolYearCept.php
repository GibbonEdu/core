<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('add, edit and delete a school year');
$I->loginAsAdmin();
$I->amOnPage('/index.php?q=/modules/School Admin/schoolYear_manage.php');

// Add
$I->click('Add', '.linkTop a');
$I->see('Add School Year', '.trailEnd');

$I->fillField('name', '2020-2021');
$I->selectOption('status', 'Upcoming');
$I->fillField('sequenceNumber', 99);
$I->fillField('firstDay', '01/01/2020');
$I->fillField('lastDay', '31/12/2020');
$I->click('Submit');

$I->see('Your request was completed successfully.', '.success');

$gibbonSchoolYearID = $I->grabFromCurrentUrl('/editID=(\d+)/');

// Edit
$I->amOnPage('/index.php?q=/modules/School Admin/schoolYear_manage_edit.php&gibbonSchoolYearID='.$gibbonSchoolYearID);
$I->see('Edit School Year', '.trailEnd');

$I->fillField('sequenceNumber', 90);
$I->fillField('firstDay', '01/01/2021');
$I->fillField('lastDay', '31/12/2021');
$I->click('Submit');

$I->seeInField('sequenceNumber', 90);
$I->seeInField('firstDay', '01/01/2021');
$I->seeInField('lastDay', '31/12/2021');

// Delete
$I->amOnPage('/index.php?q=/modules/School Admin/schoolYear_manage_delete.php&gibbonSchoolYearID='.$gibbonSchoolYearID);
$I->see('Delete School Year', '.trailEnd');

$I->click('Yes');
$I->see('Your request was completed successfully.', '.success');
