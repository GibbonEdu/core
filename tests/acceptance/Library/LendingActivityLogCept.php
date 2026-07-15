<?php
/**
 * @covers modules/Library/library_lending.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View lending and activity log');
$I->loginAsAdmin();
$I->amOnModulePage('Library', 'library_lending.php');
$I->seeBreadcrumb('Lending & Activity Log');
