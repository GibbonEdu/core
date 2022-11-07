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

use Gibbon\Services\Module\Resource;
use Gibbon\Domain\Forms\FormPageGateway;

require_once '../../gibbon.php';

$gibbonFormID = $_POST['gibbonFormID'] ?? '';
$redirect = $_POST['redirect'] ?? '';

if ($redirect == 'design') {
    $URL = $session->get('absoluteURL').'/index.php?q=/modules/System Admin/formBuilder_page_design.php&sidebar=false&gibbonFormID='.$gibbonFormID;
} else {
    $URL = $session->get('absoluteURL').'/index.php?q=/modules/System Admin/formBuilder_page_add.php&gibbonFormID='.$gibbonFormID;
}


if (isActionAccessible($guid, $connection2, Resource::fromRoute('System Admin', 'formBuilder_page_add')) == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $formPageGateway = $container->get(FormPageGateway::class);

    $data = [
        'gibbonFormID'   => $gibbonFormID,
        'name'           => $_POST['name'] ?? '',
        'introduction'   => $_POST['introduction'] ?? '',
        'postscript'     => $_POST['postscript'] ?? '',
        'sequenceNumber' => $formPageGateway->getNextSequenceNumberByForm($gibbonFormID) ?? 1,
    ];

    // Validate the required values are present
    if (empty($data['name']) || empty($gibbonFormID)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate that this record is unique
    if (!$formPageGateway->unique($data, ['name', 'gibbonFormID'])) {
        $URL .= '&return=error7';
        header("Location: {$URL}");
        exit;
    }

    // Create the record
    $gibbonFormPageID = $formPageGateway->insert($data);

    $URL .= !$gibbonFormPageID
        ? "&return=error2"
        : "&return=success0";

    header("Location: {$URL}&editID=$gibbonFormPageID&gibbonFormPageID=$gibbonFormPageID");
}
