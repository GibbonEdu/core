<?php
/**
 * @covers modules/School Admin/schoolYearSpecialDay_manage.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('view school year special days');
$I->loginAsAdmin();

$gibbonSchoolYearID = $I->grabFromDatabase('gibbonSchoolYear', 'gibbonSchoolYearID', ['status' => 'Current']);

$I->amOnModulePage('School Admin', 'schoolYearSpecialDay_manage.php', array(
    'gibbonSchoolYearID' => $gibbonSchoolYearID
));
$I->seeBreadcrumb('Manage Special Days');

// Basic Check ----------------------------------------------
$I->dontSeeErrors();
