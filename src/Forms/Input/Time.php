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

use Gibbon\View\Component;
use Gibbon\Forms\Traits\ButtonGroupTrait;

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
    use ButtonGroupTrait;
    
    protected $clock = '24';
    protected $min;
    protected $max;
    protected $dateID;
    protected $chainedTo;
    protected $chainedFrom;
    protected $showDuration;

    /**
     * Create an HTML form input.
     * @param  string  $name
     */
    public function __construct($name)
    {
        global $session;

        parent::__construct($name);

        // Update the time format based on system settings
        $timeFormatPHP = $session->get('timeFormatPHP', 'H:i');
        $this->clock = $timeFormatPHP == 'H:i' ? '24' : '12';

        $this->setAttribute('type', 'time');
    }

    /**
     * Overload the base loadFrom method to handle converting time formats.
     * @param   array  &$data
     * @return  self
     */
    public function loadFrom(&$data)
    {
        $name = str_replace('[]', '', $this->getName());

        if (!empty($data[$name])) {
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
        if (is_string($value) && strlen($value) == 19) {
            $value = substr($value, 11);
        }

        $this->setAttribute('value', $value);
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
        $this->setAttribute('min', $value);
        
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
        $this->setAttribute('max', $value);

        return $this;
    }

    /**
     * Provide the ID of another time input to connect the input values.
     * @param   string  $chained
     * @return  self
     */
    public function chainedTo($chainedTo, $showDuration = true)
    {
        $this->chainedTo = $chainedTo;
        
        return $this;
    }

    /**
     * Provide the ID of another time input to connect the input values.
     * @param   string  $chained
     * @return  self
     */
    public function chainedFrom($chainedFrom, $showDuration = true)
    {
        $this->chainedFrom = $chainedFrom;
        $this->showDuration = $showDuration;
        
        return $this;
    }

    /**
     * Provide the ID of a date input to connect to the Period selector.
     * @param   string  $dateID
     * @return  self
     */
    public function connectDate($dateID)
    {
        $this->dateID = $dateID;
        
        return $this;
    }

    /**
     * Adds time format to the label description
     * @return string|bool
     */
    public function getLabelContext($label)
    {
        if (stristr($label->getDescription(), 'Format') === false) {
            return $this->clock == '12'
                ? __('Format: h:mm am/pm')
                : __('Format: hh:mm (24hr)');
        }

        return false;
    }

    /**
     * Gets the HTML output for this form element.
     * @return  string
     */
    protected function getElement()
    {
        return Component::render(Time::class, $this->getAttributeArray() + [
            'groupClass'  => $this->getGroupClass(),
            'unique'      => $this->unique ? json_encode($this->unique) : '',
            'minimum'     => $this->min ?? '00:00',
            'maximum'     => $this->max ?? '23:59',
            'chainedTo'   => $this->chainedTo,
            'chainedFrom' => $this->chainedFrom,
            'date'        => $this->dateID,
            'value'       => $this->getValue(),
            'clock'       => $this->clock,
        ]);
    }
}
