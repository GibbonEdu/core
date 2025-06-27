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

use Gibbon\Domain\Staff\StaffCoverageGateway;
use Gibbon\FileUploader;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['text' => 'HTML', 'link' => 'URL']);

$gibbonStaffCoverageID = $_POST['gibbonStaffCoverageID'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Staff/coverage_view_edit.php&gibbonStaffCoverageID='.$gibbonStaffCoverageID;

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

    if ($coverage['gibbonPersonID'] != $session->get('gibbonPersonID') && $coverage['gibbonPersonIDStatus'] != $session->get('gibbonPersonID')) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
        return;
    }

    // File Upload
    if ($type == 'File') {
        if (!empty($_FILES['file'])) {
            // Upload the file, return the /uploads relative path
            $fileUploader = new FileUploader($pdo, $session);
            $content = $fileUploader->uploadFromPost($_FILES['file']);
    
            if (empty($content)) {
                $URL .= '&return=error3';
                header("Location: {$URL}");
                exit;
            }
        } else {
            // Remove the attachment if it has been deleted, otherwise retain the original value
            $content = empty($_POST['attachment']) ? '' : $coverage['attachmentContent'];
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
