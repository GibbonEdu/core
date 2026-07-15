<?php
/**
 * @covers modules/Reports/archive_byFamily.php
 * @covers modules/Reports/archive_byStudent.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('view reports archive by family');
$I->loginAsParent();

// Test Archive by Family ----------------------------------

$I->amOnModulePage('Reports', 'archive_byFamily.php');
$I->seeBreadcrumb('View Reports');

// Basic Check
$I->dontSeeErrors();

