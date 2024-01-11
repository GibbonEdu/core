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

use Gibbon\FileUploader;
use Gibbon\Domain\Students\StudentGateway;
use Gibbon\Module\Reports\Domain\ReportArchiveEntryGateway;
use Gibbon\Module\Reports\Domain\ReportArchiveGateway;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Reports/archive_manage_upload.php';

if (isActionAccessible($guid, $connection2, '/modules/Reports/archive_manage_upload.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $gibbonReportArchiveID = $_POST['gibbonReportArchiveID'] ?? '';
    $gibbonSchoolYearID = $_POST['gibbonSchoolYearID'] ?? '';
    $reportIdentifier = $_POST['reportIdentifier'] ?? '';
    $reportDate = $_POST['reportDate'] ?? '';
    $overwrite = $_POST['overwrite'] ?? 'N';
    $file = $_POST['file'] ?? '';
    $fileSeparator = $_POST['fileSeparator'] ?? '';
    $fileSection = $_POST['fileSection'] ?? '';

    if (empty($file) || empty($gibbonReportArchiveID) || empty($gibbonSchoolYearID) || empty($reportIdentifier) || empty($reportDate)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    $absolutePath = $session->get('absolutePath');
    if (!is_file($absolutePath.'/'.$file)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }
    
    $reportArchiveGateway = $container->get(ReportArchiveGateway::class);
    $reportArchiveEntryGateway = $container->get(ReportArchiveEntryGateway::class);
    $studentGateway = $container->get(StudentGateway::class);

    $archive = $reportArchiveGateway->getByID($gibbonReportArchiveID);
    if (empty($archive)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    $reportFolder = $gibbonSchoolYearID.'-'.preg_replace('/[^a-zA-Z0-9]/', '', $reportIdentifier);
    $destinationFolder = $archive['path'].'/'.$reportFolder;
    if (!is_dir($absolutePath.$destinationFolder)) {
        mkdir($absolutePath.$destinationFolder, 0755, true);
    }

    $fileUploader = new FileUploader($pdo, $session);
    $reports = $fileUploader->uploadFromZIP($absolutePath.'/'.$file, $destinationFolder, ['pdf']);

    $partialFail = false;
    $count = 0;

    foreach ($reports as $report) {
        // Optionally split the filenames by a separator character
        if (!empty($fileSeparator) && !empty($fileSection)) {
            $fileParts = explode($fileSeparator, mb_strstr($report['originalName'], '.', true));
            $username = $fileParts[$fileSection-1] ?? '';
        } else {
            $username = mb_strstr($report['originalName'], '.', true);
        }

        // Get the student data by username
        $studentEnrolment = $studentGateway->getStudentByUsername($gibbonSchoolYearID, $username);
        if (empty($username) || empty($studentEnrolment)) {
            $partialFail = true;
            continue;
        }

        $existingReport = $reportArchiveEntryGateway->selectBy([
            'gibbonReportArchiveID' => $gibbonReportArchiveID,
            'gibbonSchoolYearID' => $gibbonSchoolYearID,
            'reportIdentifier' => $reportIdentifier,
            'type' => 'Single',
            'gibbonYearGroupID' => $studentEnrolment['gibbonYearGroupID'],
            'gibbonPersonID' => $studentEnrolment['gibbonPersonID'],
        ])->fetch();

        // Optionally overwrite exiting files or skip them
        if (!empty($existingReport) && $overwrite == 'Y') {
            unlink($absolutePath.'/'.$archive['path'].'/'.$existingReport['filePath']);
        } elseif (!empty($existingReport) && $overwrite == 'N') {
            unlink($report['absolutePath']);
            continue;
        }

        // Create an archive entry for this file
        $archiveEntry = [
            'gibbonReportID' => 0,
            'gibbonReportArchiveID' => $gibbonReportArchiveID,
            'gibbonSchoolYearID' => $gibbonSchoolYearID,
            'gibbonYearGroupID' => $studentEnrolment['gibbonYearGroupID'],
            'gibbonFormGroupID' => $studentEnrolment['gibbonFormGroupID'],
            'gibbonPersonID' => $studentEnrolment['gibbonPersonID'],
            'type' => 'Single',
            'status' => 'Final',
            'reportIdentifier' => $reportIdentifier,
            'filePath' => $reportFolder.'/'.$report['filename'],
            'timestampCreated' => $reportDate.' 00:00:00',
            'timestampModified' => $reportDate.' 00:00:00',
        ];

        $inserted = $reportArchiveEntryGateway->insertAndUpdate($archiveEntry, $archiveEntry);
        if ($inserted) {
            $count++;
        }
    }

    unlink($absolutePath.'/'.$file);

    $URL .= $partialFail
        ? "&return=warning1"
        : "&return=success1";

    header("Location: {$URL}&imported={$count}");
}
