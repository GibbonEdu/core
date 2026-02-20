<?php
/**
 * @covers modules/Attendance/report_graph_byType.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('view attendance trends report');
$I->loginAsAdmin();
$I->amOnModulePage('Attendance', 'report_graph_byType.php');
$I->seeBreadcrumb('Attendance Trends');
