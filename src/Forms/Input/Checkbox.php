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

use Gibbon\Forms\Traits\MultipleOptionsTrait;

/**
 * Checkbox
 *
 * @version v14
 * @since   v14
 */
class Checkbox extends Input
{
    use MultipleOptionsTrait;

    protected $description;
    protected $checked = array();
    protected $checkall = false;

    /**
     * Create a checkpox input with a default value of on when checked.
     * @param  string  $name
     */
    public function __construct($name)
    {
        $this->setName($name);
        $this->setID($name);
        $this->setValue('on');
    }

    /**
     * Sets an inline label next to the checkbox input.
     * @param   string  $value
     * @return  self
     */
    public function description($value = '')
    {
        $this->description = $value;
        return $this;
    }

    /**
     * Set a value or array of values that are currently checked.
     * @param   string  $values
     * @return  self
     */
    public function checked($values)
    {
        if ($values === 1 || $values === true) $values = 'on';

        if (is_array($values)) {
            foreach ($values as $key => $value) {
                $this->checked[trim($key)] = (!is_array($value))? trim($value) : $value;
            }
        } else {
            $this->checked = array(trim($values));
        }

        return $this;
    }

    /**
     * Adds a checkall box to the top of the checkbox list, pass a label in otherwise defaults to All / None.
     * @param   string  $label
     * @return  self
     */
    public function addCheckAllNone($label = '')
    {
        if (empty($label)) $label = __('All').' / '.__('None');

        $this->checkall = $label;
        return $this;
    }

    /**
     * Return true if the passed value matches the current checkbox element value(s).
     * @param   mixed  $value
     * @return  bool
     */
    protected function getIsChecked($value)
    {
        if (empty($value) || empty($this->checked)) {
            return '';
        }

        return (in_array($value, $this->checked))? 'checked' : '';
    }

    /**
     * Gets the HTML output for this form element.
     * @return  string
     */
    protected function getElement()
    {
        $output = '';

        $this->options = (!empty($this->getOptions()))? $this->getOptions() : array($this->getValue() => $this->description);
        $name = (count($this->options)>1 && stripos($this->getName(), '[]') === false)? $this->getName().'[]' : $this->getName();

        if (!empty($this->options) && is_array($this->options)) {
            $identifier = preg_replace('/[^a-zA-Z0-9]/', '', $this->getID());

            $output .= '<fieldset id="'.$this->getID().'" style="border: 0px;">';
            if (!empty($this->checkall)) {
                $checked = (count($this->options) == count($this->checked))? 'checked' : '';
                $output .= '<label for="checkall'.$identifier.'">'.$this->checkall.'</label> ';
                $output .= '<input id="checkall'.$identifier.'" class="checkall" type="checkbox" '.$checked.'><br/>';
            }

            $count = 0;
            foreach ($this->options as $value => $label) {
                $this->setName($name);
                $this->setID($identifier.'-'.$count);
                $this->setAttribute('checked', $this->getIsChecked($value));
                if ($value != 'on') $this->setValue($value);

                $output .= '<label for="'.$this->getID().'">'.$label.'</label> ';
                $output .= '<input type="checkbox" '.$this->getAttributeString().'><br/>';

                $count++;
            }
            $output .= '</fieldset>';
        }

        return $output;
    }
}
