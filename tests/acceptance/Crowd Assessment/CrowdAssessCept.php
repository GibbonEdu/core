<?php
/**
 * @covers modules/Crowd Assessment/crowdAssess.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('view all crowd assessments');
$I->loginAsAdmin();
$I->amOnModulePage('Crowd Assessment', 'crowdAssess.php');
$I->seeBreadcrumb('View All Assessments');
$I->see('The list below shows all lessons in which there is work that you can crowd assess.');
