<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('check System Overview');
$I->loginAsAdmin();

$I->amOnModulePage('System Admin', 'systemOverview.php');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
