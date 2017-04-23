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
 * TextArea
 *
 * @version v14
 * @since   v14
 */
class TextArea extends Input
{
    protected $maxLength;

    public function __construct($name)
    {
        $this->setRows(6);
        parent::__construct($name);
    }

    public function setRows($count)
    {
        $this->setAttribute('rows', $count);

        return $this;
    }

    public function maxLength($value = '')
    {
        if (!empty($value)) {
            $this->setAttribute('maxlength', $value);
            $this->addValidation('Validate.Length', 'maximum: '.$value);
        }

        return $this;
    }

    protected function getElement()
    {
        // Unset the value attribute, textarea doesn't use them
        $text = $this->getValue();
        $this->setValue('');

        $output = '<textarea '.$this->getAttributeString().'>';
        $output .= htmlentities($text, ENT_QUOTES, 'UTF-8');
        $output .= '</textarea>';

        return $output;
    }
}
