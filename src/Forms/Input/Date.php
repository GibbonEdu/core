<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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

use DateTime;
use Gibbon\Services\Format;

/**
 * Date
 *
 * @version v14
 * @since   v14
 */
class Date extends TextField
{
    protected $min;
    protected $max;
    protected $from;
    protected $to;

    /**
     * Overload the base loadFrom method to handle converting date formats.
     * @param   array  &$data
     * @return  self
     */
    public function loadFrom(&$data)
    {
        $name = str_replace('[]', '', $this->getName());

        if (!empty($data[$name]) && $data[$name] != '0000-00-00') {
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
        $this->setAttribute('value', Format::date($value));

        return $this;
    }

    /**
     * Adds date format to the label description (if not already present)
     * @return string|bool
     */
    public function getLabelContext($label)
    {
        global $session;

        if (stristr($label->getDescription(), 'Format') === false) {
            return __('Format').': '.$session->get('i18n')['dateFormat'];
        }

        return false;
    }

    /**
     * Define a minimum for this date. Accepts YYYY-MM-DD strings as well as an
     * integer for relative date values eg: -20. See DatePicker docs:
     * https://api.jqueryui.com/datepicker/#option-minDate
     * @param   string|int  $value
     * @return  self
     */
    public function minimum($value)
    {
        $this->min = $value;
        return $this;
    }

    /**
     * Define a maximum for this date. Accepts YYYY-MM-DD strings as well as an
     * integer for relative date values eg: 20. See DatePicker docs:
     * https://api.jqueryui.com/datepicker/#option-maxDate
     * @param   string|int  $value
     * @return  self
     */
    public function maximum($value)
    {
        $this->max = $value;
        return $this;
    }

    /**
     * Provide the ID of another date input to connect the input values in a date range.
     * Chaining a value TO another date range will set the upper limit to that date's value.
     * @param   string  $value
     * @return  self
     */
    public function chainedTo($value)
    {
        $this->to = $value;

        return $this;
    }

    /**
     * Provide the ID of another date input to connect the input values in a date range.
     * Chaining a value FROM another date range will set the lower limit to that date's value.
     * @param   string  $value
     * @return  self
     */
    public function chainedFrom($value)
    {
        $this->from = $value;

        return $this;
    }

    /**
     * Gets the HTML output for this form element.
     * @return  string
     */
    protected function getElement()
    {
        global $session;

        $validationFormat = '';
        $dateFormat = $session->get('i18n')['dateFormat'];
        $dateFormatRegex = $session->get('i18n')['dateFormatRegEx'];

        $this->setAttribute('autocomplete', 'off');

        if ($dateFormatRegex == '') {
            $validationFormat .= "pattern: /^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i";
        } else {
            $validationFormat .= 'pattern: '.$dateFormatRegex;
        }

        if ($dateFormat == '') {
            $validationFormat .= ', failureMessage: "Use dd/mm/yyyy"';
        } else {
            $validationFormat .= ', failureMessage: "Use '.$dateFormat.'"';
        }

        $this->addValidation('Validate.Format', $validationFormat);

        $today = Format::date(date('Y-m-d'));

        $output = '<input type="text" '.$this->getAttributeString().' maxlength="10">';

        $minDate = $maxDate = 'null';
        $onSelect = 'function(){$(this).blur();}';

        if ($this->from) {
            $onSelect = 'function() {
                '.$this->from.'.datepicker( "option", "maxDate", getDate(this) );
                $(this).blur();
            }';
        }
        if ($this->to) {
            $onSelect = 'function() {
                '.$this->to.'.datepicker( "option", "minDate", getDate(this) );
                if ($("#'.$this->to.'").val() == "") {
                    '.$this->to.'.datepicker( "setDate", getDate(this) );
                }
                $(this).blur();
            }';
        }

        if ($this->min) {
            $minDate = is_string($this->min)
                ? 'new Date("'.$this->min.'")'
                : $this->min;
        }
        if ($this->max) {
            $maxDate = is_string($this->max)
                ? 'new Date("'.$this->max.'")'
                : $this->max;
        }

        $output .= '<script type="text/javascript">';
        $output .= '$(function() { '.$this->getID().' = $("#'.$this->getID().'").datepicker({onSelect: '.$onSelect.', onClose: function(){$(this).change();}, minDate: '.$minDate.', maxDate: '.$maxDate.' }); });';

        if ($this->to || $this->from) {
            $output .= 'function getDate(element) {
                try {
                  return $.datepicker.parseDate("'.substr($dateFormat, 0, 8).'", element.value);
                } catch( error ) {
                  return null;
                }
            }';
        }

        $output .= '</script>';

        return $output;
    }
}
