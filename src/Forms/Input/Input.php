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

use Gibbon\Forms\Layout\Element;
use Gibbon\Forms\RowDependancyInterface;
use Gibbon\Forms\ValidatableInterface;
use Gibbon\Forms\Traits\InputAttributesTrait;

/**
 * Abstract base class for form input elements.
 *
 * @version v14
 * @since   v14
 */
abstract class Input extends Element implements ValidatableInterface, RowDependancyInterface
{
    use InputAttributesTrait;

    protected $row;

    protected $validationOptions = array();
    protected $validation = array();

    /**
     * Create an HTML form input.
     * @param  string  $name
     */
    public function __construct($name)
    {
        $this->setID($name);
        $this->setName($name);
        $this->setClass('standardWidth');
    }

    /**
     * Method for RowDependancyInterface to automatically set a reference to the parent Row object.
     * @param  object  $row
     */
    public function setRow($row)
    {
        $this->row = $row;
    }

    /**
     * Add a LiveValidation option to the javascript object (eg: onlyOnSubmit: true, onlyOnBlur: true)
     * @param  string  $option
     */
    public function addValidationOption($option = '')
    {
        $this->validationOptions[] = $option;
        return $this;
    }

    /**
     * Add a LiveValidation setting to this element by type (eg: Validate.Presence)
     * @param  string  $type
     * @param  string  $params
     */
    public function addValidation($type, $params = '')
    {
        $this->validation[] = array('type' => $type, 'params' => $params);
        return $this;
    }

    /**
     * Gets the HTML output for this form element.
     * @return  string
     */
    public function getValidationOutput()
    {
        $output = '';

        // Prevent LiveValidation breaking for elements with no ID
        if (empty($this->getID())) {
            return $output;
        }

        // Don't validate readonly input
        if ($this->getReadonly() == true) {
            return $output;
        }

        if ($this->getRequired() == true || !empty($this->validation)) {
            $output .= 'var '.$this->getID().'Validate=new LiveValidation(\''.$this->getID().'\', {'.implode(',', $this->validationOptions).' }); '."\r";

            if ($this->getRequired() == true) {
                if ($this instanceof Checkbox && $this->getOptionCount() == 1) {
                    $this->addValidation('Validate.Acceptance');
                } else {
                    $this->addValidation('Validate.Presence');
                }
            }

            if (!empty($this->validation) && is_array($this->validation)) {
                foreach ($this->validation as $valid) {
                    $output .= $this->getID().'Validate.add('.$valid['type'].', {'.$valid['params'].' } ); '."\r";
                }
            }
        }

        return $output;
    }
}
