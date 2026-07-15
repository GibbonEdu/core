<?php
/**
 * @covers modules/Messenger/messenger_postQuickWall.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('Create a new quick wall message');
$I->loginAsAdmin();
$I->amOnModulePage('Messenger', 'messenger_postQuickWall.php');
$I->seeBreadcrumb('New Quick Wall Message');
