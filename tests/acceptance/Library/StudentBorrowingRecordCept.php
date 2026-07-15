<?php
/**
 * @covers modules/Library/report_studentBorrowingRecord.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View student borrowing record');
$I->loginAsAdmin();
$I->amOnModulePage('Library', 'report_studentBorrowingRecord.php');
$I->seeBreadcrumb('Student Borrowing Record');
