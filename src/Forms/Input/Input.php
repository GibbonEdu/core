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
     * Can this input be validated? Prevent LiveValidation for elements with no ID, and readonly inputs.
     * @return bool
     */
    public function isValidatable() {
        return !empty($this->getID()) && !$this->getReadonly();
    }

    /**
     * An input has validation if it's validatable and either required or has defined validations.
     * @return bool
     */
    public function hasValidation()
    {
        return $this->isValidatable() && ($this->getRequired() == true || !empty($this->validation));
    }

    /**
     * Get a stringified json object of the current validations.
     * @return string
     */
    public function getValidationAsJSON()
    {
        return json_encode($this->buildValidations());
    }

    /**
     * Gets the HTML output for this form element.
     * @return  string
     */
    public function getValidationOutput()
    {
        $output = '';

        if ($this->hasValidation()) {
            $output .= 'var '.$this->getID().'Validate=new LiveValidation(\''.$this->getID().'\', {'.implode(',', $this->validationOptions).' }); '."\r";

            foreach ($this->buildValidations() as $valid) {
                $output .= $this->getID().'Validate.add('.$valid['type'].', {'.$valid['params'].' } ); '."\r";
            }
        }

        return $output;
    }

    /**
     * Get the array of current validations for this input.
     * @return array
     */
    protected function buildValidations()
    {
        if (!$this->isValidatable()) {
            return array();
        }

        if ($this->getRequired() == true) {
            if ($this instanceof Checkbox && $this->getOptionCount() == 1) {
                $this->addValidation('Validate.Acceptance');
            } else {
                $this->addValidation('Validate.Presence');
            }
        }

        return $this->validation;
    }
}
