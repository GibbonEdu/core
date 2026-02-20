<?php
/**
 * @covers modules/Messenger/mailingLists_manage.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('Manage mailing lists');
$I->loginAsAdmin();
$I->amOnModulePage('Messenger', 'mailingLists_manage.php');
$I->seeBreadcrumb('Manage Mailing Lists');
