<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('check Manage Events');
$I->loginAsAdmin();

$I->amOnModulePage('Calendar', 'calendar_event_manage.php');

// Basic Check -----------------------------------------

$I->dontSeeErrors();

// Search Test -----------------------------------------

$I->fillField('search', 'test');
$I->submitForm('#filters', []);
$I->dontSeeErrors();
