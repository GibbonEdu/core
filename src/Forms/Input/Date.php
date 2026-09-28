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
use Gibbon\View\Component;
use Gibbon\Forms\Traits\ButtonGroupTrait;

/**
 * Date
 *
 * @version v14
 * @since   v14
 */
class Date extends TextField
{
    use ButtonGroupTrait;
    
    protected $min;
    protected $max;
    protected $from;
    protected $to;
    protected $todayButton = false;
    protected $dayOffsetButtons = [];

    /**
     * Overload the base loadFrom method to handle converting date formats.
     * @param   array  &$data
     * @return  self
     */
    public function loadFrom(&$data)
    {
        $name = str_replace('[]', '', $this->getName());

        if (!empty($data[$name]) && $data[$name] != '0000-00-00') {
            $this->setValue($data[$name]);
        }

        return $this;
    }

    /**
     * Set the input's value.
     * @param  string  $value
     * @return $this
     */
    public function setValue($value = '')
    {
        if (is_string($value) && stripos($value, '/') !== false) {
            $value = Format::dateConvert($value);
        }

        if (is_string($value) && strlen($value) == 19) {
            $value = substr($value, 0, 10);
        }

        $this->setAttribute('value', $value);
        return $this;
    }

    /**
     * @deprecated v28
     * @param  string  $value
     * @return  self
     */
    public function setDateFromValue($value)
    {
        return $this->setValue($value);
    }

    /**
     * Adds date format to the label description (if not already present)
     * @return string|bool
     */
    public function getLabelContext($label)
    {
        return false;
    }

    /**
     * Define a minimum for this date. Accepts YYYY-MM-DD strings
     * @param   string|int  $value
     * @return  self
     */
    public function minimum($value)
    {
        $this->setAttribute('min', $value);
        $this->min = $value;
        return $this;
    }

    /**
     * Define a maximum for this date. Accepts YYYY-MM-DD strings
     * @param   string|int  $value
     * @return  self
     */
    public function maximum($value)
    {
        $this->setAttribute('max', $value);
        $this->max = $value;
        return $this;
    }

    /**
     * Add a button that sets this field to today's date.
     *
     * @return self
     */
    public function addTodayButton()
    {
        $this->todayButton = true;
        return $this;
    }

    /**
     * Add quick-select buttons that add the given number of days to the current value.
     *
     * @param int[] $days
     * @return self
     */
    public function addDayOffsetButtons(array $days = [1, 7])
    {
        $this->dayOffsetButtons = array_values(array_filter(array_map('intval', $days)));
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
        $this->setAttribute('autocomplete', 'off');

        return Component::render(Date::class, $this->getAttributeArray() + [
            'outerClass' => $this->getOuterClass(),
            'groupClass' => $this->getGroupClass(),
            'todayButton' => $this->todayButton,
            'dayOffsetButtons' => $this->dayOffsetButtons,
            'readonly' => $this->getReadonly(),
        ]);
    }
}
