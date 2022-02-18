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

use Gibbon\Domain\Forms\FormFieldGateway;
use Gibbon\Forms\Builder\FormBuilder;

require_once '../../gibbon.php';

$urlParams = [
    'gibbonFormID'     => $_POST['gibbonFormID'] ?? '',
    'gibbonFormPageID' => $_POST['gibbonFormPageID'] ?? '',
    'fieldGroup'       => $_POST['fieldGroup'] ?? '',
];

$URL = $session->get('absoluteURL').'/index.php?q=/modules/System Admin/formBuilder_page_design.php&sidebar=false&'.http_build_query($urlParams);

if (isActionAccessible($guid, $connection2, '/modules/System Admin/formBuilder_page_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $formFieldGateway = $container->get(FormFieldGateway::class);
    $partialFail = false;
    
    // Validate the required values are present
    if (empty($urlParams['gibbonFormID']) || empty($urlParams['gibbonFormPageID']) || empty($urlParams['fieldGroup'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Get the field group class for the selected option
    $formBuilder = $container->get(FormBuilder::class);
    $fieldGroupClass = $formBuilder->getFieldGroup($urlParams['fieldGroup']);

    if (empty($fieldGroupClass)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    $fields = $_POST['fields'] ?? [];
    $sequenceNumber = $_POST['sequenceNumber'] ?? -1;

    // Check that at least one checkbox has been checked
    if (empty($fields)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    if ($sequenceNumber >= 0) {
        $formFieldGateway->bumpSequenceNumbersByAmount($urlParams['gibbonFormPageID'], $sequenceNumber, count($fields));
        $sequenceNumber = $sequenceNumber + 1;
    } else {
        $sequenceNumber = $formFieldGateway->getNextSequenceNumberByPage($urlParams['gibbonFormPageID']) ?? 1;
    }

    foreach ($fields as $fieldName) {
        if ($fieldName == 'generic') {
            $fieldName = lcfirst(preg_replace('/[^a-zA-Z0-9]/', '', $_POST['label'] ?? $fieldName));
        } else {
            $field = $fieldGroupClass->getField($fieldName);
            if (empty($field)) {
                $partialFail = true;
                continue;
            }

            if ($field['type'] == 'heading' || $field['type'] == 'subheading') {
                $fieldGroup = 'LayoutHeadings';
            }
        }

        $data = [
            'gibbonFormPageID' => $urlParams['gibbonFormPageID'],
            'fieldName'        => $fieldName,
            'fieldType'        => $_POST['type'] ?? $field['type'] ?? 'varchar',
            'fieldGroup'       => $fieldGroup ?? $urlParams['fieldGroup'],
            'required'         => $_POST['required'] ?? $field['required'] ?? 'N',
            'options'          => $_POST['options'] ?? $field['options'] ?? null,
            'label'            => $_POST['label'] ?? $field['label'] ?? '',
            'description'      => $_POST['description'] ?? $field['description'] ?? null,
            'sequenceNumber'   => $sequenceNumber,
        ];

        $gibbonFormFieldID = $formFieldGateway->insert($data);
        $partialFail = !$gibbonFormFieldID;

        $sequenceNumber++;
        $fieldGroup = null;
    }

    $URL .= $partialFail
        ? "&return=warning1"
        : "&return=success0";

    header("Location: {$URL}");
}
