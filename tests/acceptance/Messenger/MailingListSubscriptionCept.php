<?php
/**
 * @covers modules/Messenger/mailingListRecipients_manage_subscribe.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('Manage mailing list subscription');
$I->loginAsAdmin();
$I->amOnModulePage('Messenger', 'mailingListRecipients_manage_subscribe.php');
$I->seeBreadcrumb('Mailing List Subscription');
