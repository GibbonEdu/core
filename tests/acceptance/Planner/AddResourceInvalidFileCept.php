<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('add a new Resource with an invalid filetype');
$I->loginAsAdmin();
$I->amOnModulePage('Planner', 'resources_manage_add.php');

// Add ------------------------------------------------
$I->seeBreadcrumb('Add Resource');

$I->selectOption('type', 'File');
$I->attachFile('file', 'invalid.php');
$I->fillField('name', 'Invalid Upload');
$I->fillField('tags', 'Text');
$I->selectOption('category', 'Text');
$I->click('Submit');

$I->see('', '.error');
