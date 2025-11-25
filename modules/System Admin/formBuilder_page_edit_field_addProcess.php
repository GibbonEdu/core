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
use Gibbon\Forms\Builder\FormBuilder;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['label' => 'HTML', 'description' => 'HTML', 'options' => 'RAW']);

$urlParams = [
    'gibbonFormID'     => $_POST['gibbonFormID'] ?? '',
    'gibbonFormPageID' => $_POST['gibbonFormPageID'] ?? '',
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
    $duplicateFail = [];
    
    // Validate the required values are present
    if (empty($urlParams['gibbonFormID']) || empty($urlParams['gibbonFormPageID'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Get the field group class for the selected option
    $formBuilder = $container->get(FormBuilder::class);

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

    foreach ($fields as $fieldGroup => $fieldGroupFields) {
        $fieldGroupClass = $formBuilder->getFieldGroup($fieldGroup);

        if (empty($fieldGroupClass)) {
            $partialFail = true;
        }

        foreach ($fieldGroupFields as $fieldName) {
            if ($fieldName == 'generic') {
                $fieldName = strtolower(preg_replace('/[\~\`\!\@\%\#\$%\^&\*\(\)\+\=\{\}\[\]\|\:;"\'\\<>\,\.\?\/ ]/', '', $_POST['label'] ?? $fieldName));
            } else {
                $field = $fieldGroupClass->getField($fieldName);
                if (empty($field)) {
                    $partialFail = true;
                    continue;
                }

                if (!empty($field['type']) && ($field['type'] == 'heading' || $field['type'] == 'subheading')) {
                    $fieldName = $field['type'].preg_replace('/[\~\`\!\@\%\#\$%\^&\*\(\)\+\=\{\}\[\]\|\:;"\'\\<>\,\.\?\/ ]/', '', $_POST['label'] ?? $fieldName);
                    $fieldGroupName = 'LayoutHeadings';
                }
            }

            if ($fieldName == $formBuilder->getDetail('honeyPot')) {
                $partialFail = true;
                continue;
            }

            // Check for fields that already exist with the same field name
            $existing = $formFieldGateway->getFieldInForm($urlParams['gibbonFormID'], $fieldName);
            if (!empty($existing)) {
                // Allow certain fields to increment the field name if more than one field has been added.
                if (in_array($fieldGroup, ['GenericFields', 'LayoutText', 'LayoutHeadings', ])) {
                    $hasCounter = is_int(substr($fieldName, -1, 1));
                    $countStart = $hasCounter ? substr($fieldName, -1, 1) : 1;

                    $fieldNameBase = $hasCounter ? substr($fieldName, 0, -1) : $fieldName;
                    for ($i = $countStart+1; $i <= 9; $i++) {
                        $fieldName = $fieldNameBase.$i;
                        $existing = $formFieldGateway->getFieldInForm($urlParams['gibbonFormID'], $fieldName);
                        if (empty($existing)) {
                            break;
                        }
                    }
                }

                if (!empty($existing)) {
                    $duplicateFail[] = $fieldName;
                    $partialFail = true;
                    continue;
                }
            }

            $data = [
                'gibbonFormPageID' => $urlParams['gibbonFormPageID'],
                'fieldName'        => $fieldName,
                'fieldType'        => $_POST['type'] ?? $field['type'] ?? 'varchar',
                'fieldGroup'       => $fieldGroupName ?? $fieldGroup,
                'required'         => $_POST['required'] ?? $field['required'] ?? 'N',
                'hidden'           => $_POST['hidden'] ?? $field['hidden'] ?? 'N',
                'prefill'          => $_POST['prefill'] ?? $field['prefill'] ?? 'N',
                'options'          => $_POST['options'] ?? $field['options'] ?? null,
                'label'            => $_POST['label'] ?? $field['label'] ?? '',
                'description'      => $_POST['description'] ?? $field['description'] ?? null,
                'sequenceNumber'   => $sequenceNumber,
            ];

            $gibbonFormFieldID = $formFieldGateway->insert($data);
            $partialFail &= !$gibbonFormFieldID;

            $sequenceNumber++;
            $fieldGroupName = null;
        }
    }

    $URL .= $partialFail
        ? "&return=warning1&duplicate=".implode(',', $duplicateFail)
        : "&return=success0";

    header("Location: {$URL}");
}
