<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('add, edit and delete a school year');
$I->loginAsAdmin();
$I->amOnModulePage('School Admin', 'schoolYear_manage.php');

// Add ------------------------------------------------
$I->clickNavigation('Add');
$I->seeBreadcrumb('Add School Year');

$I->fillField('name', '2050-2051');
$I->selectOption('status', 'Upcoming');
$I->fillField('sequenceNumber', 100);
$I->fillField('firstDay', '2050-01-01');
$I->fillField('lastDay', '2051-01-01');
$I->click('Submit');

$I->see('Your request was completed successfully.', '.success');

$gibbonSchoolYearID = $I->grabEditIDFromURL();

// Edit ------------------------------------------------
$I->amOnModulePage('School Admin', 'schoolYear_manage_edit.php', array('gibbonSchoolYearID' => $gibbonSchoolYearID));
$I->seeBreadcrumb('Edit School Year');

$I->fillField('name', '1920-1921');
$I->selectOption('status', 'Past');
$I->fillField('sequenceNumber', 90);
$I->fillField('firstDay', '1920-01-01');
$I->fillField('lastDay', '1920-12-31');
$I->click('Submit');

$I->seeInField('name', '1920-1921');
$I->seeOptionIsSelected('status', 'Past');
$I->seeInField('sequenceNumber', 90);
$I->seeInField('firstDay', '1920-01-01');
$I->seeInField('lastDay', '1920-12-31');

// Delete ------------------------------------------------
$I->amOnModulePage('School Admin', 'schoolYear_manage_delete.php', array('gibbonSchoolYearID' => $gibbonSchoolYearID));

$I->click('Delete');
$I->see('Your request was completed successfully.', '.success');
