<?php
/**
 * @covers modules/Messenger/messenger_manage.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('Manage messages');
$I->loginAsAdmin();
$I->amOnModulePage('Messenger', 'messenger_manage.php');
$I->seeBreadcrumb('Manage Messages');
