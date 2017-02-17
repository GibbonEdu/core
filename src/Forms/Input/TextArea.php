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
 * TextArea
 *
 * @version v14
 * @since   v14
 */
class TextArea extends Element
{
    protected $rows = 4;
    protected $maxLength;

    public function setRows($count)
    {
        $this->rows = $count;
    }

    public function maxLength($value = '')
    {
        $this->maxLength = $value;

        $this->addValidation('Validate.Length', 'maximum: '.$this->maxLength);

        return $this;
    }

    protected function getElement()
    {

        $output = '<textarea class="'.$this->class.'" id="'.$this->name.'" name="'.$this->name.'" rows="'.$this->rows.'"';

        if (!empty($this->maxLength)) {
            $output .= ' maxlength="'.$this->maxLength.'"';
        }
        $output .= '>';

        $output .= $this->value;
        $output .= '</textarea>';

        return $output;
    }
}
