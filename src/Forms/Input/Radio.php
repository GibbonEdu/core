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

    public function __construct($name)
    {
        $this->setID('');
        $this->setName($name);
    }

    public function checked($value)
    {
        $this->setValue($value);
        return $this;
    }

    protected function getIsChecked($value)
    {
        return (!empty($value) && $value == $this->getValue());
    }

    protected function getElement()
    {
        $output = '';

        if (!empty($this->getOptions()) && is_array($this->getOptions())) {
            foreach ($this->getOptions() as $value => $label) {
                if (!empty($label)) {
                    $output .= '<label title="'.__($label).'">'.__($label).'</label> ';
                }

                $this->setAttribute('checked', $this->getIsChecked($value));

                $output .= '<input type="radio" value="'.$value.'" '.$this->getAttributeString().'><br/>';
            }
        }

        return $output;
    }
}
