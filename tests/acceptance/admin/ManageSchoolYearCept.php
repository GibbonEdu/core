<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('add, edit and delete a school year');
$I->loginAsAdmin();
$I->amOnPage('/index.php?q=/modules/School Admin/schoolYear_manage.php');

// Add ------------------------------------------------
$I->clickNavigation('Add');
$I->seeBreadcrumb('Add School Year');

$I->fillField('name', '2020-2021');
$I->selectOption('status', 'Upcoming');
$I->fillField('sequenceNumber', 100);
$I->fillField('firstDay', '01/01/2020');
$I->fillField('lastDay', '31/12/2020');
$I->click('Submit');

$I->see('Your request was completed successfully.', '.success');

$gibbonSchoolYearID = $I->grabEditIDFromURL();

// Edit ------------------------------------------------
$I->amOnPage('/index.php?q=/modules/School Admin/schoolYear_manage_edit.php&gibbonSchoolYearID='.$gibbonSchoolYearID);
$I->seeBreadcrumb('Edit School Year');

$I->fillField('name', '1920-1921');
$I->selectOption('status', 'Past');
$I->fillField('sequenceNumber', 90);
$I->fillField('firstDay', '01/01/1920');
$I->fillField('lastDay', '31/12/1920');
$I->click('Submit');

$I->seeInField('name', '1920-1921');
$I->seeOptionIsSelected('status', 'Past');
$I->seeInField('sequenceNumber', 90);
$I->seeInField('firstDay', '01/01/1920');
$I->seeInField('lastDay', '31/12/1920');

// Delete ------------------------------------------------
$I->amOnPage('/index.php?q=/modules/School Admin/schoolYear_manage_delete.php&gibbonSchoolYearID='.$gibbonSchoolYearID);
$I->seeBreadcrumb('Delete School Year');

$I->click('Yes');
$I->see('Your request was completed successfully.', '.success');
