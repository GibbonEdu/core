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

use Gibbon\Domain\System\CustomFieldGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonCustomFieldID = $_GET['gibbonCustomFieldID'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/customFields_edit.php&gibbonCustomFieldID=$gibbonCustomFieldID";

if (isActionAccessible($guid, $connection2, '/modules/System Admin/customFields_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    // Proceed!
    $enablePublicRegistration = $container->get(SettingGateway::class)->getSettingByScope('User Admin', 'enablePublicRegistration');
    $customFieldGateway = $container->get(CustomFieldGateway::class);

    $data = [
        'context'                  => $_POST['context'] ?? '',
        'name'                     => $_POST['name'] ?? '',
        'active'                   => $_POST['active'] ?? '',
        'description'              => $_POST['description'] ?? '',
        'type'                     => $_POST['type'] ?? '',
        'options'                  => $_POST['options'] ?? '',
        'required'                 => $_POST['required'] ?? '',
        'hidden'                   => $_POST['hidden'] ?? 'N',
        'heading'                  => $_POST['headingCustom'] ?? $_POST['heading'] ?? '',
        'activeDataUpdater'        => $_POST['activeDataUpdater'] ?? '0',
        'activeApplicationForm'    => $_POST['activeApplicationForm'] ?? '0',
        'activePublicRegistration' => $enablePublicRegistration == 'Y' ? ($_POST['activePublicRegistration'] ?? '0') : '0',
    ];
    
    if ($data['type'] == 'varchar') $data['options'] = min(max(0, intval($data['options'])), 255);
    if ($data['type'] == 'text') $data['options'] = max(0, intval($data['options']));

    // Prevent rich text and code editors from being used on public forms
    if ($data['type'] == 'editor' || $data['type'] == 'code') {
        $data['activeDataUpdater'] = '0';
        $data['activeApplicationForm'] = '0';
        $data['activePublicRegistration'] = '0';
    }

    // Handle role category checkboxes
    $roleCategories = $_POST['roleCategories'] ?? [];
    $data['activePersonStudent'] = in_array('activePersonStudent', $roleCategories);
    $data['activePersonStaff'] = in_array('activePersonStaff', $roleCategories);
    $data['activePersonParent'] = in_array('activePersonParent', $roleCategories);
    $data['activePersonOther'] = in_array('activePersonOther', $roleCategories);
    
    // Validate the required values are present
    if (empty($gibbonCustomFieldID) || empty($data['name']) || empty($data['active']) || empty($data['type']) || empty($data['required'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate the database relationships exist
    if (!$customFieldGateway->exists($gibbonCustomFieldID)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Validate that this record is unique
    if (!$customFieldGateway->unique($data, ['context', 'name'], $gibbonCustomFieldID)) {
        $URL .= '&return=error7';
        header("Location: {$URL}");
        exit;
    }

    // Update the record
    $updated = $customFieldGateway->update($gibbonCustomFieldID, $data);

    $URL .= !$updated
        ? "&return=error2"
        : "&return=success0&editID=$gibbonCustomFieldID";

    header("Location: {$URL}");
}
