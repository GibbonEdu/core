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
    protected $value = array();

    public function description($value = '')
    {
        $this->description = $value;
        return $this;
    }

    public function checked($value)
    {
        $this->value = (is_array($value))? $value : array($value);

        return $this;
    }

    protected function getIsChecked($value)
    {
        if (empty($value) || empty($this->value)) {
            return '';
        }
        
        return (in_array($value, $this->value, true))? 'checked' : '';
    }

    protected function getElement()
    {
        $output = '';

        $this->options = (!empty($this->getOptions()))? $this->getOptions() : array($this->value => $this->description);

        if (!empty($this->options) && is_array($this->options)) {
            foreach ($this->options as $value => $label) {
                $output .= '<label title="'.$this->name.'" for="'.$this->name.'">'.__($label).'</label> ';
                $output .= '<input type="checkbox" class="'.$this->class.'" name="'.$this->name.'[]" value="'.$value.'" '.$this->getIsChecked($value).'><br/>';
            }
        }

        return $output;
    }
}
