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
 * TextField
 *
 * @version v14
 * @since   v14
 */
class Number extends TextField
{
    protected $min;
    protected $max;
    protected $decimalPlaces = 0;
    protected $onlyInteger = true;
    protected $spinner = false;

    /**
     * Define a minimum for this numeric value.
     * @param   int|float  $value
     * @return  self
     */
    public function minimum($value)
    {
        $this->min = $value;
        return $this;
    }

    /**
     * Define a maximum for this numeric value.
     * @param   int|float  $value
     * @return  self
     */
    public function maximum($value)
    {
        $this->max = $value;
        return $this;
    }

    /**
     * Define a required number of decimal places (max) for this numeric value.
     * @param   int  $value
     * @return  self
     */
    public function decimalPlaces($value)
    {
        $this->decimalPlaces = intval($value);
        $this->onlyInteger = !($this->decimalPlaces > 0);
        return $this;
    }

    public function onlyInteger($value)
    {
        $this->onlyInteger = $value;
        return $this;
    }
    
    public function spinner($value)
    {
        $this->spinner = $value;
        return $this;
    }

    /**
     * Gets the HTML output for this form element.
     * @return  string
     */
    protected function getElement()
    {

        $validateParams = array();
        if (isset($this->min)) {
            $validateParams[] = 'minimum: '.$this->min;
        }
        if (!empty($this->max)) {
            $validateParams[] = 'maximum: '.$this->max;
        }

        if ($this->onlyInteger) {
            $validateParams[] = 'onlyInteger: true';
        }
        
        

        $this->addValidation('Validate.Numericality', implode(', ', $validateParams));

        if (!empty($this->decimalPlaces) && $this->decimalPlaces > 0) {
            $this->addValidation('Validate.Format', 'pattern: /^[0-9\-]+(\.[0-9]{1,'.$this->decimalPlaces.'})?$/, failureMessage: "'.sprintf(__('Must be in format %1$s'), str_pad('0.', $this->decimalPlaces+2, '0')).'"');
        }
        if ($this->spinner) {
            $output = '<div class="input-box border-0 standardWidth">';
            $output .= '<div class="inline-button border border-r-0 rounded-l-sm text-base text-gray-600" style="height: 36px;" onclick="decrement(this)">-</div>';
            $output .='<input type="text" class="number inline-block standardWidth w-9/12 " '.$this->getAttributeString().' style="border-width: 1px !important; border-radius: 0 !important;">';
            $output .= '<div class="inline-button border border-l-0 rounded-r-sm text-base text-gray-600" style="border-left: 0px; height: 36px;" onclick="increment(this)">+</div>';
            $output .= '</div>';
            $output .= '<script type="text/javascript">
                function increment(self) {
                    $(".number", $(self).parent()).val( function(i, oldval) {
                        $(this).trigger("keyup");
                        return ++oldval;
                    });
                }
                function decrement(self) {
                    $(".number", $(self).parent()).val( function(i, oldval) {
                        $(this).trigger("keyup");
                        return --oldval;
                    });
                }
            </script>';
        } else {
            $output = '<input type="text" '.$this->getAttributeString().'>';
        }
        return $output;
    }
}
