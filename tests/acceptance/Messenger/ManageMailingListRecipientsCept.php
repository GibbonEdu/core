<?php
/**
 * @covers modules/Messenger/mailingListRecipients_manage.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('Manage mailing list recipients');
$I->loginAsAdmin();
$I->amOnModulePage('Messenger', 'mailingListRecipients_manage.php');
$I->seeBreadcrumb('Manage Mailing List Recipients');
