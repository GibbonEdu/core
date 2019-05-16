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

use Gibbon\Domain\Staff\StaffCoverageGateway;
use Gibbon\FileUploader;

require_once '../../gibbon.php';

$gibbonStaffCoverageID = $_POST['gibbonStaffCoverageID'] ?? '';

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Staff/coverage_view_edit.php&gibbonStaffCoverageID='.$gibbonStaffCoverageID;

if (isActionAccessible($guid, $connection2, '/modules/Staff/coverage_view_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $staffCoverageGateway = $container->get(StaffCoverageGateway::class);

    $type = $_POST['attachmentType'] ?? '';
    switch ($type) {
        case 'File': $content = $_POST['attachment'] ?? ''; break;
        case 'Text': $content = $_POST['text'] ?? ''; break;
        case 'Link': $content = $_POST['link'] ?? ''; break;
        default:     $content = '';
    }

    // Validate the required values are present
    if (empty($gibbonStaffCoverageID)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate the database relationships exist
    $coverage = $staffCoverageGateway->getByID($gibbonStaffCoverageID);

    if (empty($coverage)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    if ($coverage['gibbonPersonID'] != $_SESSION[$guid]['gibbonPersonID'] && $coverage['gibbonPersonIDStatus'] != $_SESSION[$guid]['gibbonPersonID']) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
        return;
    }

    // File Upload
    if ($type == 'File' && !empty($_FILES['file'])) {
        // Upload the file, return the /uploads relative path
        $fileUploader = new FileUploader($pdo, $gibbon->session);
        $content = $fileUploader->uploadFromPost($_FILES['file']);

        if (empty($content)) {
            $URL .= '&return=error3';
            header("Location: {$URL}");
            exit;
        }
    }

    // Update the database
    $updated = $staffCoverageGateway->update($gibbonStaffCoverageID, [
        'notesStatus'       => $_POST['notesStatus'] ?? '',
        'attachmentType'    => $type,
        'attachmentContent' => $content,
    ]);

    $URL .= !$updated
        ? "&return=error2"
        : "&return=success0";

    header("Location: {$URL}");
}
