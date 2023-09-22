<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

use Gibbon\Module\Reports\Domain\ReportGateway;
use Gibbon\Module\Reports\ReportBuilder;
use Gibbon\Module\Reports\Domain\ReportArchiveEntryGateway;
use Gibbon\Domain\Students\StudentGateway;

$gibbonReportID = $_GET['gibbonReportID'] ?? '';
$contextData = $_GET['contextData'] ?? '';
$gibbonStudentEnrolmentID = $_GET['gibbonStudentEnrolmentID'] ?? [];

if (isActionAccessible($guid, $connection2, '/modules/Reports/reports_generate_batch.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $partialFail = false;
    
    $reportGateway = $container->get(ReportGateway::class);
    $reportArchiveEntryGateway = $container->get(ReportArchiveEntryGateway::class);
    $studentGateway = $container->get(StudentGateway::class);

    $report = $reportGateway->getByID($gibbonReportID);
    if (empty($gibbonReportID) || empty($report)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $reportBuilder = $container->get(ReportBuilder::class);
    $template = $reportBuilder->buildTemplate($report['gibbonReportTemplateID'], 'Draft');

    $ids = ['gibbonStudentEnrolmentID' => $gibbonStudentEnrolmentID, 'gibbonReportingCycleID' => $report['gibbonReportingCycleID']];
    $reports = $reportBuilder->buildReportSingle($template, $report, $ids);

    echo '<pre>';
    print_r($reports);
    echo '</pre>';
}
