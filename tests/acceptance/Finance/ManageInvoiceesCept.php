<?php
/**
 * @covers modules/Finance/invoicees_manage.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('manage invoicees');
$I->loginAsAdmin();
$I->amOnModulePage('Finance', 'invoicees_manage.php');
$I->seeBreadcrumb('Manage Invoicees');

// Search Test -----------------------------------------

$I->fillField('search', 'test');
$I->submitForm('#action', []);
$I->dontSeeErrors();
