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

use Gibbon\Domain\Forms\FormFieldGateway;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$urlParams = [
    'gibbonFormID'      => $_REQUEST['gibbonFormID'] ?? '',
    'gibbonFormPageID'  => $_REQUEST['gibbonFormPageID'] ?? '',
    'gibbonFormFieldID' => $_REQUEST['gibbonFormFieldID'] ?? '',
    'fieldGroup'        => $_REQUEST['fieldGroup'] ?? '',
];
$URL = $session->get('absoluteURL').'/index.php?q=/modules/System Admin/formBuilder_page_design.php&sidebar=false&'.http_build_query($urlParams);

if (isActionAccessible($guid, $connection2, '/modules/System Admin/formBuilder_page_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $formFieldGateway = $container->get(FormFieldGateway::class);

    $data = [
        'fieldType'    => $_POST['fieldType'] ?? 'varchar',
        'label'        => $_POST['label'] ?? '',
        'description'  => $_POST['description'] ?? null,
        'required'     => $_POST['required'] ?? 'N',
        'hidden'       => $_POST['hidden'] ?? 'N',
        'prefill'      => $_POST['prefill'] ?? 'N',
        'defaultValue' => $_POST['defaultValue'] ?? null,
    ];

    if (!empty($_POST['options'])) {
        $data['options'] = $_POST['options'] ?? '';
    }

    // Validate the required values are present
    if (empty($data['label']) && empty($urlParams['gibbonFormID']) || empty($urlParams['gibbonFormPageID']) || empty($urlParams['gibbonFormFieldID'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate the database relationships exist
    if (!$formFieldGateway->exists($urlParams['gibbonFormFieldID'])) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Update the record
    $updated = $formFieldGateway->update($urlParams['gibbonFormFieldID'], $data);

    $URL .= !$updated
        ? "&return=error2"
        : "&return=success0";

    header("Location: {$URL}");
}
