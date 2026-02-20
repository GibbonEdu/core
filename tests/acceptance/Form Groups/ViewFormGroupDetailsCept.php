<?php
/**
 * @covers modules/Form Groups/formGroups_details.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('view form group details');
$I->loginAsAdmin();

$gibbonFormGroupID = $I->grabFromDatabase('gibbonFormGroup', 'gibbonFormGroupID', []);

$I->amOnModulePage('Form Groups', 'formGroups_details.php', ['gibbonFormGroupID' => $gibbonFormGroupID]);
$I->seeBreadcrumb('View Form Groups');
