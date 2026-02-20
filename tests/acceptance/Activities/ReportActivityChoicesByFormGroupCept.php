<?php
/**
 * @covers modules/Activities/report_activityChoices_byFormGroup.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('view activity choices by form group report');
$I->loginAsAdmin();
$I->amOnModulePage('Activities', 'report_activityChoices_byFormGroup.php');
$I->seeBreadcrumb('Activity Choices by Form Group');

// Select a form group if available
$formGroupCount = $I->grabMultiple('#content select[name=gibbonFormGroupID] option:not([value=""])');
if (count($formGroupCount) > 0) {
    $I->selectFromDropdown('gibbonFormGroupID', 1);
    $I->submitForm('#content form', []);
    $I->seeInCurrentUrl('gibbonFormGroupID=');
}
