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

    public function maxLength($value = '')
    {
        if (!empty($value)) {
            $this->setAttribute('maxlength', $value);
            $this->addValidation('Validate.Length', 'maximum: '.$value);
        }

        return $this;
    }

    public function placeholder($value = '')
    {
        $this->setAttribute('placeholder', $value);

        return $this;
    }

    public function autocomplete($value = '')
    {
        $this->autocomplete = (is_array($value))? $value : array($value);
        $this->setAttribute('autocomplete', 'on');

        return $this;
    }


    protected function getElement()
    {
        $output = '<input type="text" '.$this->getAttributeString().'>';

        if (!empty($this->autocomplete)) {
            $source = implode(',', array_map(function ($str) { return sprintf('"%s"', $str); }, $this->autocomplete));
            $output .= '<script type="text/javascript">';
            $output .= '$("#'.$this->getID().'").autocomplete({source: ['.$source.']});';
            $output .= '</script>';
        }

        return $output;
    }
}
