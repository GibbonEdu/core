<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('explore activities');
$I->loginAsStudent();
$I->amOnModulePage('Activities', 'explore.php');
$I->seeBreadcrumb('Explore Activities');
