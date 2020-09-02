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

use Gibbon\Domain\User\UserGateway;
use Gibbon\Domain\System\EmailTemplateGateway;

require_once '../../gibbon.php';

$gibbonEmailTemplateID = $_POST['gibbonEmailTemplateID'] ?? '';

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/System Admin/emailTemplates_manage_edit.php&gibbonEmailTemplateID='.$gibbonEmailTemplateID;

if (isActionAccessible($guid, $connection2, '/modules/System Admin/emailTemplates_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $emailTemplateGateway = $container->get(EmailTemplateGateway::class);

    $data = [
        'templateSubject' => $_POST['templateSubject'] ?? '',
        'templateBody'    => $_POST['templateBody'] ?? '',
    ];

    // Validate the required values are present
    if (empty($gibbonEmailTemplateID) || empty($data['templateSubject']) || empty($data['templateBody'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Update the record
    $updated = $emailTemplateGateway->update($gibbonEmailTemplateID, $data);

    $URL .= !$updated
        ? "&return=error2"
        : "&return=success0";

    header("Location: {$URL}");
}
