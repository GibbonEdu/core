<?php
/**
 * @covers modules/Messenger/cannedResponse_manage.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('Manage canned responses');
$I->loginAsAdmin();
$I->amOnModulePage('Messenger', 'cannedResponse_manage.php');
$I->seeBreadcrumb('Manage Canned Responses');
