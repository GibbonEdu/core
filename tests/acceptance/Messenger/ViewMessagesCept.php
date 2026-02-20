<?php
/**
 * @covers modules/Messenger/messageWall_view.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View messages');
$I->loginAsStudent();
$I->amOnModulePage('Messenger', 'messageWall_view.php');
$I->seeBreadcrumb('Today\'s Messages');
