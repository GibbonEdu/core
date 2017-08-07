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

    protected $customField;

    public function __construct(FormFactoryInterface $factory, $name, $fields)
    {
        $this->factory = $factory;
        $this->fields = $fields;

        //From Enum: 'varchar','text','date','url','select', ('checkboxes' unimplemented?)
        $this->type = (isset($fields['type']))? $fields['type'] : 'varchar';
        $options = (isset($fields['options']))? $fields['options'] : '';

        switch($this->type) {

            case 'date':
                $this->customField = $this->factory->createDate($name);
                break;

            case 'url':
                $this->customField = $this->factory->createURL($name);
                break;

            case 'select':
                $this->customField = $this->factory->createSelect($name);
                if (!empty($options)) {
                    $this->customField->fromString($options)->placeholder();
                }
                break;

            case 'text':
                $this->customField = $this->factory->createTextArea($name);
                if (!empty($options) && intval($options) > 0) {
                    $this->customField->setRows($options);
                }
                break;

            default:
            case 'varchar':
                $this->customField = $this->factory->createTextField($name);
                if (!empty($options) && intval($options) > 0) {
                    $this->customField->maxLength($options);
                }
                break;
        }

        if ($fields['required'] == 'Y') {
            $this->customField->isRequired();
            $this->isRequired();
        }

        parent::__construct($name);
    }

    public function setValue($value = '')
    {
        switch($this->type) {

            case 'select':
                $this->customField->selected($value);
                break;

            case 'date':
                $this->customField->setDateFromValue($value);
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

    protected function getElement()
    {
        return $this->customField->getElement();
    }

    public function getValidationOutput()
    {
        return $this->customField->getValidationOutput();
    }
}
