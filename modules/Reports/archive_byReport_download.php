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

use Gibbon\Domain\School\SchoolYearGateway;
use Gibbon\Domain\School\YearGroupGateway;
use Gibbon\Module\Reports\Domain\ReportArchiveEntryGateway;
use Gibbon\Module\Reports\Domain\ReportArchiveGateway;
use Gibbon\Module\Reports\Domain\ReportGateway;

$_POST['address'] = '/modules/Reports/archive_byReport_download.php';

require_once '../../gibbon.php';

$returnPath = $session->get('absoluteURL').'/index.php?q=/modules/Reports/archive_byReport.php';

if (isActionAccessible($guid, $connection2, '/modules/Reports/archive_byReport_download.php') == false) {
    // Access denied
    header("location:$returnPath&return=error0");
} else {
    // Proceed!
    $gibbonReportArchiveEntryID = $_GET['gibbonReportArchiveEntryID'] ?? '';

    // Archive ID must exist
    if (empty($gibbonReportArchiveEntryID)) {
        header("location:$returnPath&return=error1");
        exit;
    }

    $reportArchiveGateway = $container->get(ReportArchiveGateway::class);
    $reportArchiveEntryGateway = $container->get(ReportArchiveEntryGateway::class);
    $archiveEntry = $reportArchiveEntryGateway->getByID($gibbonReportArchiveEntryID);

    // Check for a valid archive record
    if (empty($archiveEntry)) {
        header("location:$returnPath&return=error1");
        exit;
    }

    // Check for a valid archive base
    $archive = $reportArchiveGateway->getByID($archiveEntry['gibbonReportArchiveID']);
    if (empty($archive)) {
        header("location:$returnPath&return=error1");
        exit;
    }

    // Check access by role category
    $roleCategory = $session->get('gibbonRoleIDCurrentCategory');
    $roleAccess = false;
    if ($roleCategory == 'Staff' && $archive['viewableStaff'] == 'Y') {
        $roleAccess = true;
    } elseif ($roleCategory == 'Student' && $archive['viewableStudents'] == 'Y') {
        $roleAccess = true;
    } elseif ($roleCategory == 'Parent' && $archive['viewableParents'] == 'Y') {
        $roleAccess = true;
    } elseif ($roleCategory == 'Other' && $archive['viewableOther'] == 'Y') {
        $roleAccess = true;
    }

    if (!$roleAccess) {
        header("location:$returnPath&return=error0");
        exit;
    }

    // Report must be accessible by endDate
    $report = $container->get(ReportGateway::class)->getByID($archiveEntry['gibbonReportID']);
    $canViewDraftReports = isActionAccessible($guid, $connection2, '/modules/Reports/archive_byReport.php', 'View Draft Reports');
    if (!$canViewDraftReports && !empty($report['accessDate']) && date('Y-m-d H:i:s') < $report['accessDate']) {
        header("location:$returnPath&return=error1");
        exit;
    }

    $action = $_GET['action'] ?? 'download';

    // Rename the file to something human-readable before downloading
    if (!empty($archiveEntry['gibbonReportID'])) {
        $schoolYear = $container->get(SchoolYearGateway::class)->getByID($archiveEntry['gibbonSchoolYearID']);
        $yearGroup = $container->get(YearGroupGateway::class)->getByID($archiveEntry['gibbonYearGroupID']);
        if (empty($schoolYear) || empty($yearGroup)) {
            header("location:$returnPath&return=error1");
            exit;
        }

        $filename = $schoolYear['name'].'-'.$archiveEntry['reportIdentifier'].'-'.$yearGroup['nameShort'].'.pdf';
    } else {
        $filename = basename($archiveEntry['filePath']);
    }

    $filepath = realpath($session->get('absolutePath') . $archive['path'] .'/'. $archiveEntry['filePath']);

    $outputType = ($action == 'view')? 'inline' : 'attachment';

    // Stream the file
    if (file_exists($filepath)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/pdf');
        header('Content-Disposition: '.$outputType.'; filename="'.htmlentities($filename).'"');
        header('Content-Transfer-Encoding: base64');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filepath));
        echo file_get_contents($filepath);
        exit;
    }

    // Otherwise return to referrer page
    header("location:$returnPath&return=error3");
    exit;
}
