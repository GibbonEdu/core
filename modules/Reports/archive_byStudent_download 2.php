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

use Gibbon\Domain\User\UserGateway;
use Gibbon\Domain\School\SchoolYearGateway;
use Gibbon\Domain\Students\StudentGateway;
use Gibbon\Domain\FormGroups\FormGroupGateway;
use Gibbon\Module\Reports\Domain\ReportGateway;
use Gibbon\Module\Reports\Domain\ReportArchiveEntryGateway;
use Gibbon\Module\Reports\Domain\ReportArchiveGateway;

$_POST['address'] = '/modules/Reports/archive_byStudent_download.php';

require_once '../../gibbon.php';

$accessToken = $_GET['token'] ?? '';
$gibbonPersonIDAccessed = $_GET['gibbonPersonIDAccessed'] ?? $session->get('gibbonPersonID');

$returnPath = $session->get('absoluteURL').'/index.php?q=/modules/Reports/archive_byStudent_view.php&gibbonPersonID='.($_GET['gibbonPersonID'] ?? '');

if (empty($accessToken) && isActionAccessible($guid, $connection2, '/modules/Reports/archive_byStudent_download.php') == false) {
    // Access denied
    header("location:$returnPath&return=error0");
    exit;
} else {
    // Proceed!
    $gibbonReportArchiveEntryID = $_GET['gibbonReportArchiveEntryID'] ?? '';

    $reportArchiveGateway = $container->get(ReportArchiveGateway::class);
    $reportArchiveEntryGateway = $container->get(ReportArchiveEntryGateway::class);

    // Check for access to this archive
    if (!empty($accessToken)) {
        $returnPath = $session->get('absoluteURL').'/index.php?q=';
        $roleCategory = 'Parent';

        // Archive ID must exist
        if (empty($gibbonReportArchiveEntryID)) {
            header("location:$returnPath&return=error8");
            exit;
        }

        // Check for a valid archive record
        $archiveEntry = $reportArchiveEntryGateway->selectArchiveEntryByAccessToken($gibbonReportArchiveEntryID, $accessToken)->fetch();
        if (empty($archiveEntry)) {
            header("location:$returnPath&return=error8");
            exit;
        }

    } else {
        $roleCategory = $session->get('gibbonRoleIDCurrentCategory');
        $highestAction = getHighestGroupedAction($guid, '/modules/Reports/archive_byStudent_download.php', $connection2);
        if ($highestAction == 'View by Student') {
            $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
        } else if ($highestAction == 'View Reports_myChildren') {
            $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
            $children = $container->get(StudentGateway::class)
                ->selectAnyStudentsByFamilyAdult($session->get('gibbonSchoolYearID'), $session->get('gibbonPersonID'))
                ->fetchGroupedUnique();

            if (empty($children[$gibbonPersonID])) {
                $gibbonPersonID = null;
            }
        } else if ($highestAction == 'View Reports_mine') {
            $gibbonPersonID = $session->get('gibbonPersonID');
        }

        // Archive ID must exist
        if (empty($gibbonReportArchiveEntryID) || empty($gibbonPersonID)) {
            header("location:$returnPath&return=error1");
            exit;
        }

        // Check for a valid archive record
        $archiveEntry = $reportArchiveEntryGateway->getByID($gibbonReportArchiveEntryID);
        if (empty($archiveEntry)) {
            header("location:$returnPath&return=error0");
            exit;
        }

        // Archive person must match the incoming gibbonPersonID
        if ($archiveEntry['gibbonPersonID'] != $gibbonPersonID) {
            header("location:$returnPath&return=error0");
            exit;
        }
    }

    // Check for a valid archive base
    $archive = $reportArchiveGateway->getByID($archiveEntry['gibbonReportArchiveID']);
    if (empty($archive)) {
        header("location:$returnPath&return=error1");
        exit;
    }

    // Check access by role category
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
        $formGroup = $container->get(FormGroupGateway::class)->getByID($archiveEntry['gibbonFormGroupID']);
        $student = $container->get(UserGateway::class)->getByID($archiveEntry['gibbonPersonID']);
        if (empty($schoolYear) || empty($formGroup) || empty($student)) {
            header("location:$returnPath&return=error1");
            exit;
        }

        $filename = $schoolYear['name'].'-'.$formGroup['nameShort'].'-'.$student['username'].'.pdf';
    } else {
        $filename = basename($archiveEntry['filePath']);
    }

    $filepath = realpath($session->get('absolutePath') . $archive['path'] .'/'. $archiveEntry['filePath']);
    $outputType = ($action == 'view')? 'inline' : 'attachment';

    // Stream the file
    if (file_exists($filepath)) {
        if ($roleCategory == 'Parent' && !empty($gibbonPersonIDAccessed)) {
            // Update the archive with the most recent parent access info
            $reportArchiveEntryGateway->update($archiveEntry['gibbonReportArchiveEntryID'], [
                'gibbonPersonIDAccessed' => $gibbonPersonIDAccessed,
                'timestampAccessed' => date('Y-m-d H:i:s'),
            ]);
        }

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
