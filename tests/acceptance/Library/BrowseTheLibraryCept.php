<?php
/**
 * @covers modules/Library/library_browse.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('Browse the library');
$I->loginAsStudent();
$I->amOnModulePage('Library', 'library_browse.php');
$I->seeBreadcrumb('Browse The Library');
