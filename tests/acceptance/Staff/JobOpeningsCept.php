<?php
/**
 * @covers modules/Staff/jobOpenings_manage.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View job openings');
$I->loginAsAdmin();
$I->amOnModulePage('Staff', 'jobOpenings_manage.php');
$I->seeBreadcrumb('Job Openings');
