<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('manage fees');
$I->loginAsAdmin();

$gibbonSchoolYearID = $I->grabFromDatabase('gibbonSchoolYear', 'gibbonSchoolYearID', ['status' => 'Current']);

$I->amOnModulePage('Finance', 'fees_manage.php', ['gibbonSchoolYearID' => $gibbonSchoolYearID]);
$I->seeBreadcrumb('Manage Fees');

// TODO: This test requires fee categories to exist
// Add test for full CRUD once fee categories are available
