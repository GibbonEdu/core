<?php
/**
 * @covers modules/System Admin/import_history_view.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View import history details');
$I->loginAsAdmin();


$I->amOnModulePage('System Admin', 'import_history_view.php');
$I->dontSeeErrors();
