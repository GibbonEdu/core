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
class Radio extends Input
{
    use MultipleOptionsTrait;

    protected $inline = false;
    protected $align = 'right';

    public function __construct($name)
    {
        $this->setID(''); // Cannot share an ID across multiple Radio inputs
        $this->setName($name);
    }

    /**
     * Set the value of the radio element.
     * @param   mixed  $value
     * @return  self
     */
    public function checked($value)
    {
        $this->setValue(trim($value));
        return $this;
    }

    /**
     * Sets multiple radio elements to display horizontally.
     * @param   bool    $value
     * @return  self
     */
    public function inline($value = true)
    {
        $this->inline = $value;
        $this->addClass('right');
        return $this;
    }

    /**
     * Aligns the list options to the right edge.
     * @return  self
     */
    public function alignRight()
    {
        $this->align = 'right';
        return $this;
    }

    /**
     * Aligns the list options to the left edge.
     * @return  self
     */
    public function alignLeft()
    {
        $this->align = 'left';
        return $this;
    }

    /**
     * Dead-end stub for interface: LiveValidation does not support Radio elements.
     * @param  string  $type
     * @param  string  $params
     */
    public function addValidation($type, $params = '')
    {
        return $this;
    }

    /**
     * Return true if the passed value matches the current radio element value.
     * @param   mixed  $value
     * @return  bool
     */
    protected function getIsChecked($value)
    {
        return (!is_null($this->getValue()) && $value == $this->getValue());
    }

    /**
     * Gets the HTML output for this form element.
     * @return  string
     */
    protected function getElement()
    {
        $output = '';

        if (!empty($this->getOptions()) && is_array($this->getOptions())) {

            // Select the first option by default for required Radio elements with no checked value set
            if ($this->getRequired() && is_null($this->getValue())) {
                $firstOption = key($this->getOptions());
                $this->checked($firstOption);
            }

            $this->addClass('flex-none');

            $count = 0;
            $totalOptions = count($this->getOptions());
            foreach ($this->getOptions() as $value => $label) {
                $this->setAttribute('checked', $this->getIsChecked($value));

                if ($this->inline) {
                    $output .= '&nbsp;&nbsp;<input type="radio" value="'.$value.'" id="'.$this->getName().$count.'" '.$this->getAttributeString().'>&nbsp;';
                    $output .= '<label for="'.$this->getName().$count.'">'.$label.'</label>';
                } elseif ($this->align == 'left') {
                    $output .= '<div class="flex text-left '.($totalOptions > 1 ? 'my-2' : 'my-px').'">';
                    $output .= '<input type="radio" value="'.$value.'" id="'.$this->getName().$count.'" '.$this->getAttributeString().'>';
                    $output .= '<label class="leading-compact ml-2" for="'.$this->getName().$count.'">'.$label.'</label><br/>';
                    $output .= '</div>';
                } else {
                    $output .= '<div class="flex justify-end text-right '.($totalOptions > 1 ? 'my-2' : 'my-px').'">';
                    $output .= '<label class="leading-compact mr-1" for="'.$this->getName().$count.'">'.$label.'</label>&nbsp;';
                    $output .= '<input type="radio" value="'.$value.'" id="'.$this->getName().$count.'" '.$this->getAttributeString().'><br/>';
                    $output .= '</div>';
                }
                $count++;
            }
        }

        return $output;
    }
}
