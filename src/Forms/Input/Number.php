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

/**
 * TextField
 *
 * @version v14
 * @since   v14
 */
class Number extends TextField
{
    protected $min;
    protected $max;
    protected $decimal;

    public function minimum($value)
    {
        $this->min = $value;
        return $this;
    }

    public function maximum($value)
    {
        $this->max = $value;
        return $this;
    }

    public function decimalPlaces($value)
    {
        $this->decimal = $value;
        return $this;
    }

    public function getOutput()
    {

        $validateParams = array();
        if (isset($this->min)) {
            $validateParams[] = 'minimum: '.$this->min;
        }
        if (!empty($this->max)) {
            $validateParams[] = 'maximum: '.$this->max;
        }

        $this->addValidation('Validate.Numericality', implode(', ', $validateParams));

        if (!empty($this->decimal) && $this->decimal > 0) {
            $this->addValidation('Validate.Format', 'pattern: /^[0-9]+\.([0-9]{'.$this->decimal.'})+$/, failureMessage: "'.sprintf(__('Must be in format %1$s'), str_pad('0.', $this->decimal+2, '0')).'"');
        }

        $output = '<input type="text" class="'.$this->class.'" id="'.$this->name.'" name="'.$this->name.'" value="'.$this->value.'"';

        if (!empty($this->maxLength)) {
            $output .= ' maxlength="'.$this->maxLength.'"';
        }

        if (!empty($this->placeholder)) {
            $output .= ' placeholder="'.$this->placeholder.'"';
        }

        $output .= '>';

        return $output;
    }
}
