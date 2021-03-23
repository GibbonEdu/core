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

namespace Gibbon\Forms;

use Gibbon\FileUploader;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\System\CustomFieldGateway;

class CustomFieldHandler
{
    protected $customFieldGateway;

    protected $contexts;
    protected $types;
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
                'First Aid'    => __('First Aid'),
                'Medical Form' => __('Medical Form'),
            ],
            __('Timetable Admin') => [
                'Course' => __('Course'),
                'Class' => __('Class'),
            ]
        ];

        $this->types = [
            __('Text') => [
                'varchar'    => __('Short Text (max 255 characters)'),
                'text'       => __('Long Text'),
                'editor'     => __('Rich Text'),
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
            $fieldValue = $_POST[$prefix.$field['gibbonCustomFieldID']] ?? null;

            if ($field['type'] == 'file' || $field['type'] == 'image') {
                if ($field['type'] == 'image') {
                    $this->fileUploader->getFileExtensions('Graphics/Design');
                }

                // Move attached file, if there is one
                if (!empty($_FILES[$prefix.$field['gibbonCustomFieldID'].'File']['tmp_name'])) {
                    $file = $_FILES[$prefix.$field['gibbonCustomFieldID'].'File'] ?? null;

                    // Upload the file, return the /uploads relative path
                    $fieldValue = $this->fileUploader->uploadFromPost($file, $field['name']);
                }
            }

            if (!is_null($fieldValue)) {
                if ($field['type'] == 'date') {
                    $fieldValue = Format::dateConvert($fieldValue);
                } elseif ($field['type'] == 'checkboxes') {
                    $fieldValue = implode(',', $fieldValue);
                }

                $fields[$field['gibbonCustomFieldID']] = $fieldValue;
            }

            if ($field['required'] == 'Y' && (is_null($fieldValue) || $fieldValue == '')) {
                $customRequireFail &= true;
            }
        }

        return json_encode($fields);
    }

    public function addCustomFieldsToForm(&$form, $context, $params = [], $fields = [])
    {
        $existingFields = !empty($fields) && is_string($fields)? json_decode($fields, true) : (is_array($fields) ? $fields : []);
        $customFieldsGrouped = $this->customFieldGateway->selectCustomFields($context, $params)->fetchGrouped();
        $prefix = $params['prefix'] ?? 'custom';

        if (empty($customFieldsGrouped)) {
            return;
        }

        if (!empty($params['heading'])) {
            $form->addRow()->addHeading(__($params['heading']), $params['headingLevel'] ?? 'h3');
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
                $form->addRow()->addHeading(__($heading), $params['headingLevel'] ?? 'h3');
            }

            foreach ($customFields as $field) {
                $fieldValue = $existingFields[$field['gibbonCustomFieldID']] ?? '';
                if (!empty($fieldValue) && $field['type'] == 'date') {
                    $fieldValue = Format::date($fieldValue);
                } elseif (!empty($fieldValue) && $field['type'] == 'checkboxes') {
                    $fieldValue = explode(',', $fieldValue);
                }

                $name = $prefix.$field['gibbonCustomFieldID'];
                $row = $field['type'] == 'editor' ? $form->addRow()->setHeading($heading)->addColumn() : $form->addRow()->setHeading($heading);
                    $row->addLabel($name, $field['name'])->description($field['description']);
                    $row->addCustomField($name, $field)->setValue($fieldValue);

            }
        }
    }

    public function addCustomFieldsToTable(&$table, $context, $params = [], $fields = [])
    {
        $existingFields = !empty($fields) && is_string($fields)? json_decode($fields, true) : (is_array($fields) ? $fields : []);
        $customFieldsGrouped = $this->customFieldGateway->selectCustomFields($context, $params + ['hideHidden' => '1'])->fetchGrouped();

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

        return $table;
    }

    public function addCustomFieldsToDataUpdate(&$form, $context, array $params = [], $oldValues = '', $newValues = '')
    {
        $oldFields = !empty($oldValues['fields'])? json_decode($oldValues['fields'], true) : [];
        $newFields = !empty($newValues['fields'])? json_decode($newValues['fields'], true) : [];

        $customFields = $this->customFieldGateway->selectCustomFields($context, $params)->fetchAll();

        foreach ($customFields as $field) {
            $fieldID = $field['gibbonCustomFieldID'];
            $label = __($field['name']);

            $oldValue = $oldFields[$fieldID] ?? '';
            $newValue = $newFields[$fieldID] ?? '';

            if ($field['type'] == 'date') {
                $oldValue = Format::date($oldValue);
                $newValue = Format::date($newValue);
            }

            $isMatching = ($oldValue != $newValue);

            $row = $form->addRow();
            $row->addLabel('new'.$fieldID.'On', $label);
            $row->addContent($oldValue);
            $row->addContent($newValue)->addClass($isMatching ? 'matchHighlightText' : '');

            if ($isMatching) {
                $row->addCheckbox('newcustom'.$fieldID.'On')->checked(true)->setClass('textCenter');
                $form->addHiddenValue('newcustom'.$fieldID, $newValue);
            } else {
                $row->addContent();
            }
        }
    }

    public function getFieldDataFromDataUpdate($context, $params = [], $fields = [])
    {
        $customFields = $this->customFieldGateway->selectCustomFields($context, $params)->fetchAll();

        $fields = is_string($fields) ? json_decode($fields, true) : $fields;
        foreach ($customFields as $field) {
            if (!isset($_POST['newcustom'.$field['gibbonCustomFieldID'].'On'])) continue;
            if (!isset($_POST['newcustom'.$field['gibbonCustomFieldID']])) continue;

            $value = $_POST['newcustom'.$field['gibbonCustomFieldID']] ?? '';

            if ($field['type'] == 'date' && !empty($value)) {
                $value = Format::dateConvert($value);
            }

            $fields[$field['gibbonCustomFieldID']] = $value;
        }

        return json_encode($fields);
    }
}
