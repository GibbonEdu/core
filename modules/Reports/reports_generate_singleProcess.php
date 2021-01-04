<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

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

use Gibbon\Domain\Students\StudentGateway;
use Gibbon\Module\Reports\ArchiveFile;
use Gibbon\Module\Reports\ReportBuilder;
use Gibbon\Module\Reports\Domain\ReportGateway;
use Gibbon\Module\Reports\Domain\ReportArchiveGateway;
use Gibbon\Module\Reports\Domain\ReportArchiveEntryGateway;
use Gibbon\Module\Reports\Renderer\MpdfRenderer;
use Gibbon\Module\Reports\Renderer\TcpdfRenderer;

require_once '../../gibbon.php';

$gibbonReportID = $_POST['gibbonReportID'] ?? '';
$contextData = $_POST['contextData'] ?? '';
$identifiers = $_POST['identifier'] ?? [];
$status = $_POST['status'] ?? [];

$URL = $gibbon->session->get('absoluteURL').'/index.php?q=/modules/Reports/reports_generate_single.php&gibbonReportID='.$gibbonReportID.'&contextData='.$contextData;

if (isActionAccessible($guid, $connection2, '/modules/Reports/reports_generate_batch.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $partialFail = false;

    ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
    
    $reportGateway = $container->get(ReportGateway::class);
    $reportArchiveEntryGateway = $container->get(ReportArchiveEntryGateway::class);
    $studentGateway = $container->get(StudentGateway::class);

    $report = $reportGateway->getByID($gibbonReportID);

    // Validate the database relationships exist
    if (empty($gibbonReportID) || empty($report)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Set reports to cache in a separate location
    $cachePath = $gibbon->session->has('cachePath') ? $gibbon->session->get('cachePath').'/reports' : '/uploads/cache';
    $container->get('twig')->setCache($gibbon->session->get('absolutePath').$cachePath);

    $reportBuilder = $container->get(ReportBuilder::class);
    $archive = $container->get(ReportArchiveGateway::class)->getByID($report['gibbonReportArchiveID']);
    $archiveFile = $container->get(ArchiveFile::class);
    
    $template = $reportBuilder->buildTemplate($report['gibbonReportTemplateID'], $status == 'Draft');
    $renderer = $container->get($template->getData('flags') == 1 ? MpdfRenderer::class : TcpdfRenderer::class);

    foreach ($identifiers as $identifier) {

        $ids = ['gibbonStudentEnrolmentID' => $identifier, 'gibbonReportingCycleID' => $report['gibbonReportingCycleID']];
        $reports = $reportBuilder->buildReportSingle($template, $report, $ids);

        // Archive
        if ($student = $studentGateway->getByID($identifier)) {
            $path = $archiveFile->getSingleFilePath($gibbonReportID, $student['gibbonYearGroupID'], $identifier);
            $renderer->render($template, $reports, $gibbon->session->get('absolutePath').$archive['path'].'/'.$path);

            $reportArchiveEntryGateway->insertAndUpdate([
                'reportIdentifier'      => $report['name'],
                'gibbonReportID'        => $gibbonReportID,
                'gibbonReportArchiveID' => $report['gibbonReportArchiveID'],
                'gibbonSchoolYearID'    => $student['gibbonSchoolYearID'],
                'gibbonYearGroupID'     => $student['gibbonYearGroupID'],
                'gibbonRollGroupID'     => $student['gibbonRollGroupID'],
                'gibbonPersonID'        => $student['gibbonPersonID'],
                'type'                  => 'Single',
                'status'                => $status,
                'filePath'              => $path,
            ], ['status' => $status, 'timestampModified' => date('Y-m-d H:i:s'), 'filePath' => $path]);
        } else {
            $partialFail = true;
        }
    }

    $URL .= $partialFail
        ? "&return=error3"
        : "&return=success0";

    header("Location: {$URL}");
}
