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
use Gibbon\Forms\Element;
use Gibbon\View\Component;
use Gibbon\Forms\Traits\ButtonGroupTrait;

/**
 * TextField
 *
 * @version v14
 * @since   v14
 */
class TextField extends Input
{
    use ButtonGroupTrait;
    
    protected $autocomplete;
    protected $unique;

    /**
     * Create an HTML form input.
     * @param  string  $name
     */
    public function __construct($name)
    {
        $this->setAttribute('type', 'text');

        parent::__construct($name);
    }

    
    /**
     * Set a max character count for this text field.
     * @param   string  $value
     * @return  self
     */
    public function maxLength($value = '')
    {
        if (!empty($value)) {
            $this->setAttribute('maxlength', $value);
            $this->addValidation('Validate.Length', 'maximum: '.$value);
        }

        return $this;
    }

    /**
     * Set the default text that appears before any text has been entered.
     * @param   string  $value
     * @return  self
     */
    public function placeholder($value = '')
    {
        $this->setAttribute('placeholder', $value);

        return $this;
    }

    /**
     * Sets the input type that is used, such as url or email.
     * @param   string  $value
     * @return  self
     */
    public function setType($value = '')
    {
        $this->setAttribute('type', $value);

        return $this;
    }

    /**
     * Enables javascript autocompletion from the supplied set of values.
     * @param   string|array  $value
     * @return  self
     */
    public function autocomplete($value = '')
    {
        $this->autocomplete = (is_array($value))? $value : array($value);
        // $this->setAttribute('autocomplete', 'on');
        $this->setAttribute('list', $this->getID().'DataList');

        return $this;
    }

    /**
     * @deprecated Remove setters that start with isXXX for code consistency.
     */
    public function isUnique($ajaxURL, $data = [])
    {
        return $this->uniqueField($ajaxURL, $data);
    }

    /**
     * Add an AJAX uniqueness check to this field using the given URL.
     *
     * @param string $ajaxURL
     * @param array $data
     * @return self
     */
    public function uniqueField($ajaxURL, $data = [])
    {
        if ($this->getReadonly()) return $this;
        
        $label = $this->row->getElement('label'.$this->getName());
        $fieldLabel = (!empty($label))? $label->getLabelText() : ucfirst($this->getName());

        $this->unique = [
            'ajaxURL'      => $ajaxURL,
            'ajaxData'     => array_replace(array('fieldName' => $this->getName()), $data),
            'alertSuccess' => sprintf(__('%1$s available'), $fieldLabel),
            'alertFailure' => sprintf(__('%1$s already in use'), $fieldLabel),
            'alertError'   => __('An error has occurred.'),
        ];

        $this->setAttribute('x-model', "uniqueValue");
        $this->setAttribute('hx-post', $ajaxURL);
        $this->setAttribute('hx-trigger', 'input delay:300ms');
        $this->setAttribute('x-on:htmx:after-request.camel', 'unique = $event.detail.xhr.responseText <= 0;');

        return $this;
    }

    /**
     * Adds uniqueness text to the label description (if not already present)
     * @return string|bool
     */
    public function getLabelContext($label)
    {
        if (!empty($this->unique)) {
            return $this->getRequired()
                ? __('Must be unique.')
                : __('Must be unique if set.');
        }

        return false;
    }

    /**
     * Gets the HTML output for this form element.
     * @return  string
     */
    protected function getElement()
    {
        return Component::render(TextField::class, $this->getAttributeArray() + [
            'groupClass'       => $this->getGroupClass(),
            'unique'           => !empty($this->unique) ? $this->unique : [],
            'uniqueData'       => !empty($this->unique) ? json_encode($this->unique['ajaxData']) : '',
            'autocompleteList' => $this->autocomplete
                ? $this->autocomplete
                : '',
        ]);
    }
}
