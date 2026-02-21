<?php
/**
 * @covers modules/System Admin/export_run.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Export Run');
$I->loginAsAdmin();

$I->amOnModulePage('System Admin', 'export_run.php', [
    'type' => 'studentEnrolment',
    'data' => 0,
]);
$I->dontSeeErrors();
