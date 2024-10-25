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

namespace Gibbon\Forms\Input;

use Gibbon\Forms\Input\Date;
use Gibbon\Forms\Layout\Element;
use Gibbon\Forms\ValidatableInterface;
use Gibbon\Forms\RowDependancyInterface;
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

    protected $validationOptions = [];
    protected $validation = [];

    /**
     * Create an HTML form input.
     * @param  string  $name
     */
    public function __construct($name)
    {
        $this->setID($name);
        $this->setName($name);
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
        $this->validation[] = ['type' => $type, 'params' => $params];
        return $this;
    }

    /**
     * Can this input be validated? Prevent LiveValidation for elements with no ID, and readonly inputs.
     * @return bool
     */
    public function isValidatable() {
        return !empty($this->getID()) && !$this->getReadonly() && !$this instanceof Toggle;
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
     * Get the HTML output of the content element.
     * @return  string
     */
    public function getOutput()
    {
        $this->setValidation()->enableValidation();

        return $this->prepended.$this->getElement().$this->appended;
    }

    /**
     * An internal method that can be overridden to add custom validation.
     * @return self
     */
    protected function setValidation()
    {
        return $this;
    }
    
     /**
     * Enabled validation by adding attributes to this input.
     */
    public function enableValidation()
    {
        if (!$this->isValidatable()) {
            return;
        }

        $validations = [];
        $message = '';
        $expression = '';

        if ($this->getRequired() == true) {
            $validations[] = 'required';
            $message = $this instanceof Select ? __('Please select an option') : __('This field is required');
        }

        foreach ($this->validation as $valid) {
            $type = !empty($valid['type']) ? trim(strtolower(strrchr($valid['type'], '.')), '. ') : '';
            $params = !empty($valid['params']) && is_string($valid['params']) ? '{'.json_decode($valid['params'], true).'}' : [];

            switch ($type) {
                case 'length':
                    $this->setAttribute('pattern', '.{0,'. $this->getAttribute('maxlength').'}');
                    break;
                case 'presence':
                    $validations[] = 'required';
                    $message = $this instanceof Select ? __('Please select an option') : __('This field is required');
                    break;
                case 'format':
                    $pattern = $params['pattern'] ?? '';
                    if (!empty($pattern)) {
                        $this->setAttribute('pattern', $pattern);
                    }
                    break;
                case 'inclusion':
                    $within = $params['within'] ?? '';
                    $expression = '$el.value?.includes("'.$within.'")';
                    $message = __('Should include').' '.$within;
                    break;
                case 'exclusion':
                    $within = $params['within'] ?? '';
                    $expression = '!$el.value?.includes("'.$within.'")';
                    $message = __('Should not include').' '.$within;
                    break;
                case 'email':
                    $validations[] = 'email';
                    $message = __('Please enter a valid email address');
                    break;
                case 'confirmation':
                    $match = $params['match'] ?? '';
                    $expression = '$el.value === $validate.value("'.$match.'")';
                    $message = __('Please ensure both passwords match');
                    break;
                case 'numericality':
                    $onlyInteger = $params['onlyInteger'] ?? false;
                    $validations[] = $onlyInteger ? 'integer' : 'number';
                    $message = $onlyInteger 
                        ? __('May contain only whole numbers')
                        :__('May contain only numbers');
                    // $expression = !empty($this->getAttribute('maxlength'))
                    //     ? '$el.value?.match("^.{0,'. $this->getAttribute('maxlength').'}$") !== null'
                    //     : '';
                    break;
                case 'acceptance':
                    $validations[] = 'group';
                    $expression = 1;
                    $message = __('Please check to confirm');
                    break;
            }
        }

        if (!empty($this->validation) || !empty($validations)) {
            $validations = !empty($validations)? '.'.implode('.', array_unique($validations)) : '';
            $this->setAttribute('x-validate' . $validations, $expression);
            $this->setAttribute('data-error-msg', $message);
        }
    }

    /**
     * Deprecated. Replaced with Alpine validation.
     * @deprecated v28
     * @return  string
     */
    public function getValidationOutput()
    {
        return '';
    }
}
