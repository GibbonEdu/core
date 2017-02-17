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

    public function checked($value)
    {
        $this->value = $value;
        return $this;
    }

    protected function getIsChecked($value)
    {
        return (!empty($value) && $value == $this->value )? 'checked' : '';
    }

    protected function getElement()
    {
        $output = '';

        if (!empty($this->getOptions()) && is_array($this->getOptions())) {
            foreach ($this->getOptions() as $value => $label) {
                $output .= '<label title="'.$this->name.'" for="'.$this->name.'">'.__($label).'</label> ';
                $output .= '<input type="radio" class="'.$this->class.'" name="'.$this->name.'" value="'.$value.'" '.$this->getIsChecked($value).'><br/>';
            }
        }

        return $output;
    }
}
