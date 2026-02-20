<?php
/**
 * @covers modules/Finance/invoices_view.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('view invoices');
$I->loginAsParent();

$gibbonSchoolYearID = $I->grabFromDatabase('gibbonSchoolYear', 'gibbonSchoolYearID', ['status' => 'Current']);

$I->amOnModulePage('Finance', 'invoices_view.php', ['gibbonSchoolYearID' => $gibbonSchoolYearID]);
$I->seeBreadcrumb('View Invoices');
