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
 * Date
 *
 * @version v14
 * @since   v14
 */
class Date extends TextField
{
    protected function getElement()
    {

        global $guid;

        $validationFormat = '';

        if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
            $validationFormat .= "pattern: /^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i";
        } else {
            $validationFormat .= 'pattern: '.$_SESSION[$guid]['i18n']['dateFormatRegEx'];
        }

        if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
            $validationFormat .= ', failureMessage: "Use dd/mm/yyyy"';
        } else {
            $validationFormat .= ', failureMessage: "Use '.$_SESSION[$guid]['i18n']['dateFormat'].'"';
        }

        $this->addValidation('Validate.Format', $validationFormat);

        $output = '<input type="text" class="'.$this->class.'" id="'.$this->id.'" name="'.$this->name.'" value="'.$this->value.'"';

        if (!empty($this->placeholder)) {
            $output .= ' placeholder="'.$this->placeholder.'"';
        }

        $output .= '>';

        $output .= '<script type="text/javascript">';
        $output .= '$(function() { $( "#'.$this->name.'" ).datepicker(); })';
        $output .= '</script>';

        return $output;
    }
}
