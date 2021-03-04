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

namespace Gibbon\Forms\Input;

use Gibbon\Forms\FormFactoryInterface;

/**
 * CustomField
 *
 * Turn an array of dynamic field information into a custom field
 *
 * @version v14
 * @since   v14
 */
class CustomField extends Input
{
    protected $factory;
    protected $fields;
    protected $type;
    protected $name;

    protected $customField;

    /**
     * Creates a variable input type from a passed row of custom field settings (often from the database).
     * @param  FormFactoryInterface  $factory
     * @param  string                $name
     * @param  array                 $fields
     */
    public function __construct(FormFactoryInterface $factory, $name, $fields)
    {
        $this->factory = $factory;
        $this->fields = $fields;
        $this->name = $name;

        //From Enum: 'varchar','text','date','url','select', ('checkboxes' unimplemented?)
        $this->type = $fields['type'] ?? 'varchar';
        $options = $fields['options'] ?? '';

        switch ($this->type) {
            case 'date':
                $this->customField = $this->factory->createDate($name);
                break;

            case 'time':
                $this->customField = $this->factory->createTime($name);
                break;

            case 'number':
                $this->customField = $this->factory->createNumber($name)->onlyInteger(false);
                if (!empty($options)) {
                    $this->customField->maxLength($options);
                }
                break;

            case 'url':
                $this->customField = $this->factory->createURL($name);
                break;

            case 'editor':
                global $guid;
                $this->customField = $this->factory->createEditor($name, $guid)->allowUpload(false)->showMedia(false);
                if (!empty($options) && intval($options) > 0) {
                    $this->customField->setRows($options);
                }
                break;

            case 'color':
                $this->customField = $this->factory->createColor($name);
                break;

            case 'image':
                $this->customField = $this->factory->createFileUpload($name.'File')->accepts('.jpg,.jpeg,.gif,.png,.svg');
                break;

            case 'file':
                $this->customField = $this->factory->createFileUpload($name.'File');
                if (!empty($options)) {
                    $this->customField->accepts($options);
                }
                break;

            case 'select':
                $this->customField = $this->factory->createSelect($name);
                if (is_string($options)) {
                    $optionArray = array_map('trim', explode(',', $options));
                    $options = [];
                    
                    // Enable [] around an option to create optgroups
                    for ($i = 0; $i < count($optionArray); $i++) {
                        $option = $optionArray[$i];
                        if (substr($option, 0, 1 ) == '[') {
                            $optGroup = trim($option, '[]');
                            continue;
                        }
                        if (!empty($optGroup)) {
                            $options[$optGroup][$option] = $option;
                        } else {
                            $options[$option] = $option;
                        }
                    }
                }
                if (!empty($options)) {
                    $this->customField->fromArray($options)->placeholder();
                }
                break;

            case 'checkboxes': 
                $this->customField = $this->factory->createCheckbox($name);
                if (!empty($options) && is_string($options)) {
                    $this->customField->fromString($options)->alignRight();
                } else if (!empty($options) && is_array($options)) {
                    $this->customField->fromArray($options)->alignRight();
                }
                break;

            case 'radio': 
                $this->customField = $this->factory->createRadio($name);
                if (!empty($options) && is_string($options)) {
                    $this->customField->fromString($options);
                } else if (!empty($options) && is_array($options)) {
                    $this->customField->fromArray($options);
                }
                break;

            case 'yesno':
                $this->customField = $this->factory->createYesNo($name)->placeholder();
                break;

            case 'text':
            case 'paragraph':
                $this->customField = $this->factory->createTextArea($name);
                if (!empty($options) && intval($options) > 0) {
                    $this->customField->setRows($options);
                }
                break;

            default:
            case 'words':
            case 'varchar':
                $this->customField = $this->factory->createTextField($name);
                if (!empty($options) && intval($options) > 0) {
                    $this->customField->maxLength($options);
                }
                break;
        }

        if (isset($fields['required']) && $fields['required'] == 'Y') {
            $this->customField->required();
            $this->required();
        }

        if (!empty($fields['default'])) {
            $this->customField->setValue($fields['default']);
        }

        $this->customField->setClass($this->type != 'checkboxes' && $this->type != 'radio' ? 'w-full' : '');
        $this->customField->setID(preg_replace('/[^a-zA-Z0-9]/', '', $this->customField->getName()));

        parent::__construct($name);
    }

    /**
     * Sets the value of the custom field depending on it's internal type.
     * @param  mixed  $value
     */
    public function setValue($value = '')
    {
        global $guid;

        switch($this->type) {

            case 'select':
            case 'yesno':
                $this->customField->selected($value);
                break;

            case 'radio':
            case 'checkboxes':
                $this->customField->checked($value);
                break;

            case 'date':
                $this->customField->setDateFromValue($value);
                break;

            case 'image':
            case 'file':
                $this->customField->setAttachment($this->name, $_SESSION[$guid]['absoluteURL'], $value);
                break;

            default:
            case 'url':
            case 'text':
            case 'varchar':
                $this->customField->setValue($value);
                break;
        }

        return $this;
    }

    /**
     * Gets the internal Input object
     * @return  object Input
     */
    protected function getElement()
    {
        return $this->customField->getElement();
    }

    /**
     * Get the validation output from the internal Input object.
     * @return  string
     */
    public function getValidationOutput()
    {
        return $this->customField->getValidationOutput();
    }
}
