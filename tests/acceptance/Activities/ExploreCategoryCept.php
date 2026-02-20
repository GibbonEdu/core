<?php
/**
 * @covers modules/Activities/explore_category.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('Explore activity category');
$I->loginAsAdmin();

// Get an existing activity type
$gibbonActivityTypeID = $I->grabFromDatabase('gibbonActivityType', 'gibbonActivityTypeID', []);

$I->amOnModulePage('Activities', 'explore_category.php', ['gibbonActivityTypeID' => $gibbonActivityTypeID]);
$I->seeBreadcrumb('Explore Activities');
