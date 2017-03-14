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
 * PhoneNumber
 *
 * @version v14
 * @since   v14
 */
class PhoneNumber extends Input
{
    protected $countryCodes = array();

    public function setCountryCodes($countryCodes)
    {
        $this->countryCodes = (!empty($countryCodes) && is_array($countryCodes))? $countryCodes : array();

        return $this;
    }

    protected function getElement()
    {
        $output = '';

        $output .= '<div class="column inline right phoneNumber">';

        $output .= '<div><select name="'.$this->getName().'Type">';
            $output .= '<option value="Mobile">'.__('Mobile').'</option>';
            $output .= '<option value="Home">'.__('Home').'</option>';
            $output .= '<option value="Work">'.__('Work').'</option>';
            $output .= '<option value="Fax">'.__('Fax').'</option>';
            $output .= '<option value="Pager">'.__('Pager').'</option>';
            $output .= '<option value="Other">'.__('Other').'</option>';
        $output .= '</select></div>';

        $output .= '<div><select name="'.$this->getName().'CountryCode">';
        $output .= '<option value=""> </option>';
        foreach ($this->countryCodes as $countryCodes) {
            $output .= '<option value="'.$countryCodes['iddCountryCode'].'">'.$countryCodes['iddCountryCode'].' - '.__($countryCodes['printable_name']).'</option>';
        }
        $output .= '</select></div>';

        $output .= '<div><input type="text" '.$this->getAttributeString().'></div>';

        $output .= '</div>';
        return $output;
    }
}
