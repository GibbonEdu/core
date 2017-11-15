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

use Gibbon\Forms\FormFactoryInterface;

/**
 * PhoneNumber
 *
 * @version v14
 * @since   v14
 */
class PhoneNumber extends Input
{
    protected $column;

    protected $phoneType;
    protected $phoneCodes;
    protected $phoneNumber;

    protected $countryCodes = array();

    /**
     * Create a number input that holds an internal Column object of phoneType, phoneCodes, and phoneNumber inputs.
     * @param  FormFactoryInterface  &$factory
     * @param  string                $name
     * @param  array                 $countryCodes
     */
    public function __construct(FormFactoryInterface &$factory, $name, $countryCodes = array())
    {
        $this->setName($name);
        $this->setClass('column inline right phoneNumber');

        $types = array(
            'Mobile' => __('Mobile'),
            'Home'   => __('Home'),
            'Work'   => __('Work'),
            'Fax'    => __('Fax'),
            'Pager'  => __('Pager'),
            'Other'  => __('Other'),
        );

        // Create an internal column to hold the set of phone number fields
        $this->column = $factory->createColumn();

        $this->phoneType = $this->column->addSelect($name.'Type')->fromArray($types)->placeholder();
        $this->phoneCodes = $this->column->addSelect($name.'CountryCode')->fromArray($countryCodes)->placeholder();
        $this->phoneNumber = $this->column->addTextField($name);
    }

    /**
     * Set an array of possible country codes.
     * @param  array  $countryCodes
     * @return self
     */
    public function setCountryCodeOptions($countryCodes)
    {
        $this->phoneCodes->fromArray($countryCodes);

        return $this;
    }

    /**
     * Set the phone number.
     * @param  array  $value
     * @return self
     */
    public function setValue($value = '')
    {
        $this->phoneNumber->setValue($value);
        return $this;
    }

    /**
     * Gets the current phone number value.
     * @return  string
     */
    public function getValue()
    {
        return $this->phoneNumber->getValue();
    }

    /**
     * Pass an array of $key => $value pairs into the internal Column object.
     * @param   string  &$data
     * @return  object Column
     */
    public function loadFrom(&$data)
    {
        return $this->column->loadFrom($data);
    }

    /**
     * Get the validation output from the internal Column object.
     * @return  string
     */
    public function getValidationOutput()
    {
        return $this->column->getValidationOutput();
    }

    /**
     * Gets the HTML output for this form element.
     * @return  string
     */
    protected function getElement()
    {
        // Pass any specific attributes along to the phone number field
        $this->phoneType->setRequired($this->getRequired());
        $this->phoneCodes->setRequired($this->getRequired());
        $this->phoneNumber->setRequired($this->getRequired());

        $this->phoneNumber->setSize($this->getSize());
        $this->phoneNumber->setDisabled($this->getDisabled());

        $output = '';
        $output .= $this->phoneNumber->getOutput();
        $output .= $this->phoneCodes->getOutput();
        $output .= $this->phoneType->getOutput();

        return $output;
    }
}
