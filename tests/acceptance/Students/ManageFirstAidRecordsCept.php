<?php
/**
 * @covers modules/Students/firstAidRecord.php
 * @covers modules/Students/firstAidRecord_add.php
 * @covers modules/Students/firstAidRecord_edit.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('manage first aid records');
$I->loginAsAdmin();
$I->amOnModulePage('Students', 'firstAidRecord.php');
$I->seeBreadcrumb('First Aid Records');

// Add a new first aid record
$I->click('Add', 'a');
$I->seeBreadcrumb('Add');
$I->dontSeeErrors();
