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

    public function setName($name = '')
    {
        $this->setAttribute('name', $name);
        return $this;
    }

    public function getName()
    {
        return $this->getAttribute('name');
    }

    public function setValue($value = '')
    {
        $this->setAttribute('value', $value);
        return $this;
    }

    public function getValue()
    {
        return $this->getAttribute('value');
    }

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

    public function loadFromCSV(&$data)
    {
        $name = str_replace('[]', '', $this->getName());

        if (!empty($data[$name])) {
            $data[$name] = explode(',', $data[$name]);
        }

        $this->loadFrom($data);
    }

    public function setSize($size = '')
    {
        $this->setAttribute('size', $size);
        return $this;
    }

    public function getSize()
    {
        return $this->getAttribute('size');
    }

    public function isDisabled($disabled = true)
    {
        $this->setDisabled('disabled', $disabled);
        return $this;
    }

    public function setDisabled($disabled)
    {
        $this->setAttribute('disabled', $disabled);
        return $this;
    }

    public function getDisabled()
    {
        return $this->getAttribute('disabled');
    }

    public function isRequired($required = true)
    {
        return $this->setRequired($required);
    }

    public function setRequired($required)
    {
        $this->required = $required;
        return $this;
    }

    public function getRequired()
    {
        return $this->required;
    }
}
