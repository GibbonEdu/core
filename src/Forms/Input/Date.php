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
    /**
     * Overload the base loadFrom method to handle converting date formats.
     * @param   array  &$data
     * @return  self
     */
    public function loadFrom(&$data)
    {
        $name = str_replace('[]', '', $this->getName());

        if (!empty($data[$name])) {
            $this->setDateFromValue($data[$name]);
        }

        return $this;
    }

    /**
     * Set the input value by converting a YYYY-MM-DD format back to localized value.
     * @param  string  $value
     * @return  self
     */
    public function setDateFromValue($value)
    {
        global $guid;

        $this->setAttribute('value', dateConvertBack($guid, $value));

        return $this;
    }

    /**
     * Adds date format to the label description (if not already present)
     * @return string|bool
     */
    public function getLabelContext()
    {
        global $guid;

        if ($label = $this->getLabel()) {
            if (stristr($label->getDescription(), 'Format') === false) {
                return __('Format').': '.$_SESSION[$guid]['i18n']['dateFormat'];
            }
        }

        return false;
    }

    /**
     * Gets the HTML output for this form element.
     * @return  string
     */
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

        $today = dateConvertBack($guid, date('Y-m-d'));

        $output = '<input type="text" '.$this->getAttributeString().' maxlength="10">';

        $output .= '<script type="text/javascript">';
        $output .= '$(function() {  $( "#'.$this->getID().'" ).datepicker({onSelect: function(){$(this).blur(); onClose: $(this).change();} });  })';
        $output .= '</script>';

        return $output;
    }
}
