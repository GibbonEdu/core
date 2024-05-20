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

namespace Gibbon\Forms;

use Gibbon\FileUploader;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\System\CustomFieldGateway;

class CustomFieldHandler
{
    /**
     * @var \Gibbon\Domain\System\CustomFieldGateway
     */
    protected $customFieldGateway;

    /**
     * @var \Gibbon\FileUploader
     */
    protected $fileUploader;

    /**
     * @var string[][]
     */
    protected $contexts;

    /**
     * @var string[][]
     */
    protected $types;

    /**
     * @var string[][]
     */
    protected $headings;

    public function __construct(CustomFieldGateway $customFieldGateway, FileUploader $fileUploader)
    {
        $this->customFieldGateway = $customFieldGateway;
        $this->fileUploader = $fileUploader;

        $this->contexts = [
            __('User Admin') => [
                'User' => __('User'),
            ],
            __('Staff') => [
                'Staff' => __('Staff'),
            ],
            __('Students') => [
                'Student Enrolment' => __('Student Enrolment'),
                'Behaviour'         => __('Behaviour'),
                'Individual Needs'  => __('Individual Needs'),
                'First Aid'         => __('First Aid'),
                'Medical Form'      => __('Medical Form'),
            ],
            __('Timetable Admin') => [
                'Course' => __('Course'),
                'Class'  => __('Class'),
            ],
            __('Planner') => [
                'Lesson Plan' => __('Lesson Plan'),
            ],
            __('School Admin') => [
                'Department' => __('Department'),
            ],
            __('Other') => [
                'Custom' => __('Custom Context'),
            ]
        ];

        $this->types = [
            __('Text') => [
                'varchar'    => __('Short Text (max 255 characters)'),
                'text'       => __('Long Text'),
                'editor'     => __('Rich Text'),
                'code'       => __('Code'),
            ],
            __('Options') => [
                'select'     => __('Dropdown'),
                'checkboxes' => __('Checkboxes'),
                'radio'      => __('Radio'),
            ],
            __('Dates') => [
                'date'       => __('Date'),
                'time'       => __('Time'),
            ],
            __('File') => [
                'file'       => __('File'),
                'image'      => __('Image'),
            ],
            __('Other') => [
                'yesno'      => __('Yes/No'),
                'number'     => __('Number'),
                'url'        => __('Link'),
                'color'      => __('Colour'),
            ]
        ];

        $this->headings = [
            'User' => [
                'Basic Information'      => __('Basic Information'),
                'System Access'          => __('System Access'),
                'Contact Information'    => __('Contact Information'),
                'School Information'     => __('School Information'),
                'Background Information' => __('Background Information'),
                'Employment'             => __('Employment'),
                'Emergency Contacts'     => __('Emergency Contacts'),
                'Miscellaneous'          => __('Miscellaneous'),
            ],
            'Staff' => [
                'Basic Information' => __('Basic Information'),
                'First Aid'         => __('First Aid'),
                'Biography'         => __('Biography'),
            ],
            'Student Enrolment' => [
                'Basic Information' => __('Basic Information'),
            ],
            'Behaviour' => [
                'Step 1' => __('Step 1'),
                'Details' => __('Details'),
            ],
            'Individual Needs' => [
                'Individual Education Plan' => __('Individual Education Plan'),
            ],
            'First Aid' => [
                'Basic Information' => __('Basic Information'),
                'Follow Up'         => __('Follow Up'),
            ],
            'Medical Form' => [
                'General Information' => __('General Information'),
            ],
            'Course' => [
                'Basic Details' => __('Basic Details'),
                'Display Information' => __('Display Information'),
                'Configure' => __('Configure'),
            ],
            'Class' => [
                'Basic Details' => __('Basic Details'),
            ],
            'Department' => [
                'Basic Details' => __('Basic Details'),
            ],
            'Lesson Plan' => [
                'Basic Information' => __('Basic Information'),
                'Lesson Content' => __('Lesson Content'),
                'Homework' => __('Homework'),
                'Markbook' => __('Markbook'),
                'Advanced Options' => __('Advanced Options'),
                'Outcomes' => __('Outcomes'),
                'Access' => __('Access'),
                'Guests' => __('Guests'),
            ],
            'Custom' => [
            ],
        ];
    }

    public function getContexts()
    {
        return $this->contexts;
    }

    public function getTypes()
    {
        return $this->types;
    }

    public function getHeadings()
    {
        return $this->headings;
    }

    public function getFieldDataFromPOST($context, $params = [], &$customRequireFail = false)
    {
        $customFields = $this->customFieldGateway->selectCustomFields($context, $params)->fetchAll();
        $prefix = $params['prefix'] ?? 'custom';
        $fields = [];

        foreach ($customFields as $field) {
            $fieldName = $prefix.$field['gibbonCustomFieldID'];
            $fieldValue = $this->getFieldValueFromPOST($fieldName, $field['type']);

            if (!is_null($fieldValue)) {
                $fields[$field['gibbonCustomFieldID']] = $fieldValue;
            }

            if ($field['required'] == 'Y' && (is_null($fieldValue) || $fieldValue == '')) {
                $customRequireFail &= true;
            }
        }

        return json_encode($fields);
    }

    public function getFieldValueFromPOST($fieldName, $fieldType)
    {
        $fieldValue = $fieldType == 'editor' || $fieldType == 'code'
                ? $_POST[$fieldName.'CustomEditor'] ?? null
                : $_POST[$fieldName] ?? null;

        if ($fieldType == 'file' || $fieldType == 'image') {
            if ($fieldType == 'image') {
                $this->fileUploader->getFileExtensions('Graphics/Design');
            }

            // Move attached file, if there is one
            if (!empty($_FILES[$fieldName.'File']['tmp_name'])) {
                $file = $_FILES[$fieldName.'File'] ?? null;

                // Upload the file, return the /uploads relative path
                $fieldValue = $this->fileUploader->uploadFromPost($file, $fieldName);
            } else if (empty($_POST[$fieldName])) {
                // Remove the attachment if it has been deleted, otherwise retain the original value
                $fieldValue = null;
            }
        }

        if (!is_null($fieldValue)) {
            if ($fieldType == 'date') {
                $fieldValue = Format::dateConvert($fieldValue);
            } elseif ($fieldType == 'checkboxes') {
                $fieldValue = implode(',', $fieldValue);
            }
        }

        return $fieldValue;
    }

    public function addCustomFieldsToForm(&$form, $context, $params = [], $fields = [])
    {
        $existingFields = !empty($fields) && is_string($fields)? json_decode($fields, true) : (is_array($fields) ? $fields : []);
        $customFieldsGrouped = $this->customFieldGateway->selectCustomFields($context, $params)->fetchGrouped();
        $prefix = $params['prefix'] ?? 'custom';
        $table = $context == 'Individual Needs' ? $params['table'] : $form;

        if (empty($customFieldsGrouped)) {
            return;
        }

        if (!empty($params['heading'])) {
            $table = $context == 'Individual Needs' 
                ? $form->addRow()->addTable()->setClass('smallIntBorder fullWidth mt-2')
                : $form;

            $row = $table->addRow()->addClass($params['class'] ?? '');
            $row->addHeading(__($params['heading']), $params['headingLevel'] ?? 'h3');
        }

        foreach ($customFieldsGrouped as $heading => $customFields) {
            if (empty($customFields)) continue;

            // Enable adding a prefix to all headings (eg: application form parents)
            if (!empty($params['headingPrefix'])) {
                $heading = $params['headingPrefix'].' '.$heading;
            }

            // Handle adding a default heading for fields if a heading has been manually set
            if ((empty($heading) || $heading == 'Other Information') && !empty($params['heading'])) {
                $heading = $params['heading'];
            }

            // Handle creating a new heading if the form doesn't already have one
            if (!empty($heading) && !$form->hasHeading($heading)) {
                $table = $context == 'Individual Needs' 
                    ? $form->addRow()->addTable()->setClass('smallIntBorder fullWidth mt-2')
                    : $form;

                $row = $table->addRow()->addClass($params['class'] ?? '');
                $row->addHeading(__($heading), $params['headingLevel'] ?? 'h3');
            }

            foreach ($customFields as $field) {
                $fieldValue = $existingFields[$field['gibbonCustomFieldID']] ?? '';
                if (!empty($fieldValue) && $field['type'] == 'date') {
                    $fieldValue = Format::date($fieldValue);
                } elseif (!empty($fieldValue) && $field['type'] == 'checkboxes') {
                    $fieldValue = explode(',', $fieldValue);
                }

                $name = $prefix.$field['gibbonCustomFieldID'];
                $row = $table->addRow()->addClass($params['class'] ?? '')->setHeading($heading);

                if ($field['type'] == 'editor' || $field['type'] == 'code') {
                    $name = $name.'CustomEditor';
                    $row = $row->addColumn();
                }

                $row->addLabel($name, $field['name'])->description(Format::hyperlinkAll($field['description']));
                $row->addCustomField($name, $field)->setValue($fieldValue)->readonly($params['readonly'] ?? false);
            }
        }
    }

    public function addCustomFieldsToTable(&$table, $context, $params = [], $fields = [])
    {
        $existingFields = !empty($fields) && is_string($fields)? json_decode($fields, true) : (is_array($fields) ? $fields : []);
        $customFieldsGrouped = $this->customFieldGateway->selectCustomFields($context, $params + ['hideHidden' => '1'])->fetchGrouped();
        $allowHTMLFields = [];

        if (empty($table)) {
            $table = DataTable::createDetails('customFields');
        }

        if (!empty($existingFields)) {
            $table->withData([$existingFields]);
        }

        foreach ($customFieldsGrouped as $heading => $customFields) {
            // Try to get existing columns by custom field heading or parameter heading.
            $headingCol = $table->getColumn($heading);
            if (empty($headingCol) && empty($heading) && !empty($params['heading'])) {
                $headingCol = $table->getColumn($params['heading']);
            }

            // If no heading column exists, add one
            if (empty($headingCol) && !empty($heading)) {
                $headingCol = $table->addColumn($heading, __($heading));
            } elseif (empty($headingCol) && !empty($params['heading'])) {
                $headingCol = $table->addColumn($params['heading'], __($params['heading']));
            }

            foreach ($customFields as $field) {
                $col = !empty($headingCol)
                    ? $headingCol->addColumn($field['gibbonCustomFieldID'], __($field['name']))
                    : $table->addColumn($field['gibbonCustomFieldID'], __($field['name']));

                switch ($field['type']) {
                    case 'code':
                        $allowHTMLFields[] = $field['name'];
                        break;
                    case 'editor':
                        $col->addClass('col-span-3');
                        break;
                    case 'date':
                        $col->format(Format::using('date', $field['gibbonCustomFieldID']));
                        break;
                    case 'url':
                        $col->format(Format::using('link', [$field['gibbonCustomFieldID'], $field['gibbonCustomFieldID']]));
                        break;
                    case 'file':
                    case 'image':
                        $col->format(function ($values) use ($field) {
                            return !empty($values[$field['gibbonCustomFieldID']])
                                ? Format::link($values[$field['gibbonCustomFieldID']], __('Attachment'), '', ['target' => '_blank'])
                                : '';
                        });
                        break;
                    case 'yesno':
                        $col->format(Format::using('yesno', $field['gibbonCustomFieldID']));
                        break;
                    case 'color':
                        $col->format(function ($values) use ($field) {
                            $value = $values[$field['gibbonCustomFieldID']] ?? '';
                            return "<span class='tag text-xxs w-12' title='$value' style='background-color: $value'>&nbsp;</span>";
                        });
                        break;
                }
            }
        }

        $table->addMetaData('allowHTML', $allowHTMLFields);

        return $table;
    }

    public function addCustomFieldsToDataUpdate(&$form, $context, array $params = [], $oldValues = '', $newValues = '')
    {
        $oldFields = !empty($oldValues['fields'])? json_decode($oldValues['fields'], true) : [];
        $newFields = !empty($newValues['fields'])? json_decode($newValues['fields'], true) : [];

        $customFields = $this->customFieldGateway->selectCustomFields($context, $params)->fetchAll();
        $changeCount = 0;

        foreach ($customFields as $field) {
            $fieldID = $field['gibbonCustomFieldID'];
            $label = __($field['name']);

            $oldValue = $oldFields[$fieldID] ?? '';
            $newValue = $newFields[$fieldID] ?? '';

            if ($field['type'] == 'date') {
                $oldValue = Format::date($oldValue);
                $newValue = Format::date($newValue);
            }

            $isNotMatching = ($oldValue != $newValue);

            $row = $form->addRow();
            $row->addLabel('new'.$fieldID.'On', $label);

            if ($field['type'] == 'file' || $field['type'] == 'image') {
                $row->addContent(!empty($oldValue) ? Format::link('./'.$oldValue, $oldValue, ['target' => '_blank']) : '');
                $row->addContent(!empty($newValue) ? Format::link('./'.$newValue, $newValue, ['class' => $isNotMatching ? 'matchHighlightText underline' : '', 'target' => '_blank']) : '');
            } else {
                $row->addContent($oldValue);
                $row->addContent($newValue)->addClass($isNotMatching ? 'matchHighlightText' : '');
            }

            if ($isNotMatching) {
                $row->addCheckbox('newcustom'.$fieldID.'On')->checked(true)->setClass('textCenter');
                $form->addHiddenValue('newcustom'.$fieldID, $newValue);
                $changeCount++;
            } else {
                $row->addContent();
            }
        }

        return $changeCount;
    }

    public function getFieldDataFromDataUpdate($context, $params = [], $fields = [])
    {
        $customFields = $this->customFieldGateway->selectCustomFields($context, $params)->fetchAll();

        $fields = is_string($fields) ? json_decode($fields, true) : $fields;
        foreach ($customFields as $field) {
            if (!isset($_POST['newcustom'.$field['gibbonCustomFieldID'].'On'])) continue;
            if (!isset($_POST['newcustom'.$field['gibbonCustomFieldID']])) continue;

            $value = $field['type'] == 'editor' || $field['type'] == 'code'
                ? $_POST['newcustom'.$field['gibbonCustomFieldID'].'CustomEditor'] ?? ''
                : $_POST['newcustom'.$field['gibbonCustomFieldID']] ?? '';

            if ($field['type'] == 'date' && !empty($value)) {
                $value = Format::dateConvert($value);
            }

            $fields[$field['gibbonCustomFieldID']] = $value;
        }

        return json_encode($fields);
    }

    public function formatFieldData($customFields = [], $fields = [])
    {  
        if (empty($customFields)) return $fields;

        $fields = is_string($fields) ? json_decode($fields, true) : $fields;

        foreach ($customFields as $index => $field) {
            if (empty($fields[$field['gibbonCustomFieldID']])) continue;

            $value = $fields[$field['gibbonCustomFieldID']];

            switch ($field['type']) {
                case 'date':
                    $value = Format::date($value);
                    break;
                case 'url':
                    $value = Format::link($value, $value);
                    break;
                case 'file':
                case 'image':
                    $value = Format::link($value, __('Attachment'), '', ['target' => '_blank']);
                    break;
                case 'yesno':
                    $value = Format::yesNo($value);
                    break;
                case 'color':
                    $value = "<span class='tag text-xxs w-12' title='$value' style='background-color: $value'>&nbsp;</span>";
                    break;
            }

            $fields[$field['gibbonCustomFieldID']] = $value;
        }

        return $fields;
    }
}
