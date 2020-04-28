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

use Gibbon\Module\Reports\Domain\ReportArchiveEntryGateway;
use Gibbon\Module\Reports\Domain\ReportArchiveGateway;
use Gibbon\FileUploader;

require_once '../../gibbon.php';

$URL = $gibbon->session->get('absoluteURL').'/index.php?q=/modules/Reports/archive_manage.php';

if (isActionAccessible($guid, $connection2, '/modules/Reports/archive_manage_import.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $gibbonReportArchiveID = $_POST['gibbonReportArchiveID'] ?? '';
    $gibbonSchoolYearID = $_POST['gibbonSchoolYearID'] ?? '';
    $reportIdentifier = $_POST['reportIdentifier'] ?? '';

    if (empty($_FILES['file']) || empty($gibbonReportArchiveID) || empty($gibbonSchoolYearID) || empty($reportIdentifier) ) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    $absolutePath = $gibbon->session->get('absolutePath');
    $reportArchiveGateway = $container->get(ReportArchiveGateway::class);
    $reportArchiveEntryGateway = $container->get(ReportArchiveEntryGateway::class);

    $archive = $reportArchiveGateway->getByID($gibbonReportArchiveID);
    if (empty($archive)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    $partialFail = false;
    $path = str_replace('\\', '/', $_FILES['file']['tmp_name']);
    $zip = new ZipArchive();

    if ($zip->open($path) === true) { // Success
        $fileUploader = new FileUploader($pdo, $gibbon->session);
        $count = 0;
        $total = $zip->numFiles;

        for ($i = 0; $i < $zip->numFiles; ++$i) {
            if (substr($zip->getNameIndex($i), 0, 8) == '__MACOSX') continue;
            
            $filename = $zip->getNameIndex($i);
            $username = strstr($filename, '.', true);
            $extension = mb_substr(mb_strrchr(strtolower($filename), '.'), 1);

            if ($extension != 'pdf') {
                $partialFail = true;
            }

            // Ensure the file info matches a user and student enrolment
            $data = ['username' => $username, 'gibbonSchoolYearID' => $gibbonSchoolYearID];
            $sql = "SELECT * FROM gibbonStudentEnrolment JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) WHERE gibbonPerson.username=:username AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID";
            $studentEnrolment = $pdo->selectOne($sql, $data);

            if (!empty($username) && !empty($studentEnrolment)) {
                // Get or make a folder to upload into
                $reportFolder = preg_replace('/[^a-zA-Z0-9]/', '', $reportIdentifier);
                $destinationFolder = $absolutePath.$archive['path'].'/'.$reportFolder;

                if (!is_dir($destinationFolder)) {
                    mkdir($destinationFolder, 0755, true);
                }

                // Upload the file, grab the /uploads relative path
                $destinationName = $fileUploader->getRandomizedFilename($filename, $destinationFolder);
                $filePathRelative = $reportFolder.'/'.$destinationName;

                if (!(@copy('zip://'.$path.'#'.$filename, $destinationFolder.'/'.$destinationName))) {
                    $partialFail = true;
                }

                // Create an archive entry for this file
                $archiveEntry = [
                    'gibbonReportID' => 0,
                    'gibbonReportArchiveID' => $gibbonReportArchiveID,
                    'gibbonSchoolYearID' => $gibbonSchoolYearID,
                    'gibbonYearGroupID' => $studentEnrolment['gibbonYearGroupID'],
                    'gibbonRollGroupID' => $studentEnrolment['gibbonRollGroupID'],
                    'gibbonPersonID' => $studentEnrolment['gibbonPersonID'],
                    'type' => 'Single',
                    'status' => 'Final',
                    'reportIdentifier' => $reportIdentifier,
                    'filePath' => $filePathRelative,
                    'timestampCreated' => date('Y-m-d H:i:s'),
                    'timestampModified' => date('Y-m-d H:i:s'),
                ];

                $inserted = $reportArchiveEntryGateway->insertAndUpdate($archiveEntry, $archiveEntry);
                if ($inserted) $count++;
            }
        }
    } else {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    $URL .= $partialFail
        ? "&return=warning1"
        : "&return=success1";

    header("Location: {$URL}&imported={$count}&total={$total}");
}
