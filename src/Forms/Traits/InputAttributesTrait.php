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

namespace Gibbon\Forms\Traits;

/**
 * Basic HTML Input Attributes (name, type, value, required)
 *
 * @version v14
 * @since   v14
 */
trait InputAttributesTrait
{
    protected $required;

    /**
     * Set the input's name attribute.
     * @param  string  $name
     * @return self
     */
    public function setName($name = '')
    {
        $this->setAttribute('name', $name);
        return $this;
    }

    /**
     * Gets the input's name attribute.
     * @return  string
     */
    public function getName()
    {
        return $this->getAttribute('name');
    }

    /**
     * Set the input's value.
     * @param  string  $value
     * @return self
     */
    public function setValue($value = '')
    {
        $this->setAttribute('value', $value);
        return $this;
    }

    /**
     * Gets the input's value.
     * @return  mixed
     */
    public function getValue()
    {
        return $this->getAttribute('value');
    }

    /**
     * Sets the input's value if the name matches a key in the provided data set.
     * @param   array  &$data
     */
    public function loadFrom(&$data)
    {
        $name = str_replace('[]', '', $this->getName());

        if (!empty($data[$name])) {
            $value = $data[$name];

            if (method_exists($this, 'selected')) {
                $this->selected($value);
            } else if (method_exists($this, 'checked')) {
                $this->checked($value);
            } else {
                $this->setAttribute('value', $value);
            }
        }
    }

    /**
     * Sets the input's array value from a CSV string if the name matches a key in the provided data set.
     * @param   array  &$data
     */
    public function loadFromCSV(&$data)
    {
        $name = str_replace('[]', '', $this->getName());

        if (!empty($data[$name])) {
            $data[$name] = explode(',', $data[$name]);
        }

        $this->loadFrom($data);
    }

    /**
     * Set the input's size attribute.
     * @param  string|int  $size
     * @return self
     */
    public function setSize($size = '')
    {
        $this->setAttribute('size', $size);
        return $this;
    }

    /**
     * Gets the input's size attribute.
     * @return  string|int
     */
    public function getSize()
    {
        return $this->getAttribute('size');
    }

    /**
     * Set the input to disabled.
     * @param   bool    $value
     * @return  self
     */
    public function isDisabled($disabled = true)
    {
        $this->setDisabled('disabled', $disabled);
        return $this;
    }

    /**
     * Set the input's disabled attribute.
     * @param  bool  $disabled
     * @return self
     */
    public function setDisabled($disabled)
    {
        $this->setAttribute('disabled', $disabled);
        return $this;
    }

    /**
     * Gets the input's disabled attribute.
     * @return  bool
     */
    public function getDisabled()
    {
        return $this->getAttribute('disabled');
    }

    /**
     * Set the input to required.
     * @param   bool    $value
     * @return  self
     */
    public function isRequired($required = true)
    {
        return $this->setRequired($required);
    }

    /**
     * Set if the input is required.
     * @param  bool  $required
     * @return self
     */
    public function setRequired($required)
    {
        $this->required = $required;
        return $this;
    }

    /**
     * Gets the input's required attribute.
     * @return  bool
     */
    public function getRequired()
    {
        return $this->required;
    }

    /**
     * Set the input to readonly.
     * @param   bool    $value
     * @return  self
     */
    public function readonly($value = true)
    {
        return $this->setReadonly($value);
    }

    /**
     * Set the input's readonly attribute.
     * @param  string  $value
     * @return self
     */
    public function setReadonly($value)
    {
        $this->setAttribute('readonly', $value);

        return $this;
    }

    /**
     * Gets the input's readonly attribute.
     * @return  bool
     */
    public function getReadonly()
    {
        return $this->getAttribute('readonly');
    }
}
