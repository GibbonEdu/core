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

use Gibbon\Forms\Element;

/**
 * TextField
 *
 * @version v14
 * @since   v14
 */
class TextField extends Input
{
    protected $autocomplete;
    protected $unique;

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
     * Enables javascript autocompletion from the supplied set of values.
     * @param   string|array  $value
     * @return  self
     */
    public function autocomplete($value = '')
    {
        $this->autocomplete = (is_array($value))? $value : array($value);
        $this->setAttribute('autocomplete', 'on');

        return $this;
    }

    public function isUnique($ajaxURL, $data = array())
    {
        $label = $this->row->getElement('label'.$this->getName());
        $fieldName = (!empty($label))? $label->getLabel() : ucfirst($this->getName());

        $this->unique = array(
            'fieldName'    => $fieldName,
            'ajaxURL'      => $ajaxURL,
            'ajaxData'     => array_replace(array('fieldName' => $this->getName()), $data),
            'alertSuccess' => sprintf(__('%1$s available'), $fieldName),
            'alertFailure' => sprintf(__('%1$s already in use'), $fieldName),
            'alertError'   => __('An error has occurred.'),
        );

        return $this;
    }

    /**
     * Adds uniqueness text to the label description (if not already present)
     * @return string|bool
     */
    public function getLabelContext($label)
    {
        if (!empty($this->unique)) {
            return __('Must be unique.');
        }

        return false;
    }

    /**
     * Gets the HTML output for this form element.
     * @return  string
     */
    protected function getElement()
    {
        $output = '<input type="text" '.$this->getAttributeString().'>';

        if (!empty($this->autocomplete)) {
            $source = implode(',', array_map(function ($str) { return sprintf('"%s"', $str); }, $this->autocomplete));
            $output .= '<script type="text/javascript">';
            $output .= '$("#'.$this->getID().'").autocomplete({source: ['.$source.']});';
            $output .= '</script>';
        }

        if (!empty($this->unique)) {
            $output .= '<script type="text/javascript">
                $("#'.$this->getID().'").gibbonUniquenessCheck('.json_encode($this->unique).');
            </script>';
        }

        return $output;
    }
}
