<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('ensure the frontpage works');
$I->amOnPage('/');
$I->see('Welcome', 'h2');
$I->see('Powered by Gibbon');
$I->see('SOMETHING RANDOM THAT DOESNT EXIST!!');
