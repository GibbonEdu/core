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
        if (!empty($params) && is_string($params)) {
            $paramList = array_chunk(preg_split('/[:]|, /', $params), 2);
            
            $params = !empty($paramList)
                ? array_combine(array_column($paramList, 0), array_column($paramList, 1))
                : [];
            $params = array_map(function ($item) {
                return trim($item, ' \'"');
            }, $params);
        }

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
        $this->setValidation()->enableValidation($this);

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
     * Enables validation by adding attributes to this input.
     *
     * @param Input $element
     * @return void
     */
    public function enableValidation(Input $element)
    {
        if (!$element->isValidatable()) {
            return;
        }

        $validations = [];
        $message = $expression = '';
        $failureMessage = null;

        if ($element->getRequired() == true) {
            $validations[] = 'required';
            $message = $element instanceof Select ? __('Please select an option') : __('This field is required');
        }

        foreach ($element->validation as $valid) {
            $type = !empty($valid['type']) ? trim(strtolower(strrchr($valid['type'], '.')), '. ') : '';
            $params = $valid['params'] ?? [];
            $failureMessage = $params['failureMessage'] ?? $failureMessage;

            switch ($type) {
                case 'presence':
                    $validations[] = 'required';
                    $message = $element instanceof Select ? __('Please select an option') : __('This field is required');
                    break;
                case 'format':
                    $pattern = $params['pattern'] ?? '';
                    if (!empty($pattern)) {
                        $element->setAttribute('pattern', trim($pattern, ' \/'));
                    }
                    break;
                case 'inclusion':
                    $within = $params['within'] ?? '';
                    $within = stripos($within, '[') === false ? '['.$within.']' : $within;

                    if (!empty($within)) {
                        $expression = !empty($params['partialMatch']) && $params['partialMatch'] == 'true'
                        ? strtolower($within).'.some(needle=>$el.value?.toLowerCase().includes(needle));'
                        : strtolower($within).'.some(needle=>$el.value?.toLowerCase() == needle);';
                    }

                    $message = __('Should include').' '.trim($within, '\'[]');
                    break;
                case 'exclusion':
                    $within = $params['within'] ?? '';
                    $within = stripos($within, '[') === false ? '['.$within.']' : $within;

                    if (!empty($within)) {
                        $expression = !empty($params['partialMatch']) && $params['partialMatch'] == 'true'
                        ? strtolower($within).'.every(needle=>!$el.value?.toLowerCase().includes(needle));'
                        : strtolower($within).'.every(needle=>$el.value?.toLowerCase() != needle);';
                    }

                    $message = __('Should not include').' '.trim($within, '\'[]');
                    break;
                case 'email':
                    $validations[] = 'email';
                    $message = __('Please enter a valid email address');
                    break;
                case 'url':
                    $validations[] = 'url';
                    $message = __('Please enter a valid URL');
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
                        : __('May contain only numbers');
                    break;
                case 'acceptance':
                    $validations[] = 'group';
                    $expression = 1;
                    $message = __('Please check to confirm');
                    break;
            }
        }

        $message = $failureMessage ?? $message;

        if (!empty($element->validation) || !empty($validations)) {
            $validations = !empty($validations)? '.'.implode('.', array_unique($validations)) : '';
            $element->setAttribute('x-validate' . $validations, $expression);
            $element->setAttribute('data-error-msg', $message);
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
