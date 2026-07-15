<?php
/**
 * @covers modules/Markbook/markbook_view.php
 * @covers modules/Markbook/markbook_view_viewMyChildrensClasses.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('view my children\'s markbook as a parent');
$I->loginAsParent();

// Navigate to the page
$I->amOnModulePage('Markbook', 'markbook_view.php');
$I->seeBreadcrumb('View Markbook');

// Basic Check -----------------------------------------

$I->dontSeeErrors();

