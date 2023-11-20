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

/**
 * Time
 *
 * Interface for jQuery-timepicker http://jonthornton.github.io/jquery-timepicker/
 *
 * @version v14
 * @since   v14
 */
class Time extends TextField
{
    protected $format = 'H:i'; // Default to 24 hour clock
    protected $min;
    protected $max;
    protected $chained;
    protected $showDuration;

    /**
     * Set the format to output time values (default 'H:i').
     * @param  string  $format
     */
    public function setFormat($format)
    {
        $this->format = $format;
        return $this;
    }

    /**
     * Define a minimum for this time value.
     * @param   string  $value
     * @return  self
     */
    public function minimum($value)
    {
        $this->min = $value;
        return $this;
    }

    /**
     * Define a maximum for this time value.
     * @param   string  $value
     * @return  self
     */
    public function maximum($value)
    {
        $this->max = $value;
        return $this;
    }

    /**
     * Provide the ID of another time input to connect the input values.
     * @param   string  $chained
     * @return  self
     */
    public function chainedTo($chained, $showDuration = true)
    {
        $this->chained = $chained;
        $this->showDuration = $showDuration;
        
        return $this;
    }

    /**
     * Adds time format to the label description
     * @return string|bool
     */
    public function getLabelContext($label)
    {
        if (stristr($label->getDescription(), 'Format') === false) {
            return __('Format: hh:mm (24hr)');
        }

        return false;
    }

    /**
     * Gets the HTML output for this form element.
     * @return  string
     */
    protected function getElement()
    {
        $this->addValidation(
            'Validate.Format',
            'pattern: /^(0[0-9]|[1][0-9]|2[0-3])[:](0[0-9]|[1-5][0-9])/i, failureMessage: "Use hh:mm"'
        );

        $jsonData = [
            'scrollDefault' => 'now',
            'timeFormat' => $this->format,
            'minTime' => $this->min,
            'maxTime' => $this->max,
        ];

        $output = '';
        $output = '<input type="text" '.$this->getAttributeString().' maxlength="5">';

        $output .= '<script type="text/javascript">';
        $output .= '$("#'.$this->getID().'").timepicker('.json_encode($jsonData).');';
        if (!empty($this->chained)) {
            // On change, update this time and set duration
            $output .= '$("#'.$this->chained.'").on("changeTime", function() {';
            $output .= 'if ($("#'.$this->getID().'").val() == "") $("#'.$this->getID().'").val($(this).val());';
            $output .= '$("#'.$this->getID().'").timepicker({ "minTime": $(this).val(), "timeFormat" : "'.$this->format.'", "showDuration" : "'.$this->showDuration.'"});';
            $output .= '});';
        }
        $output .= '</script>';

        return $output;
    }
}
