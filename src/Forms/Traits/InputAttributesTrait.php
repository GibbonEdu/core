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
 * Basic HTML Attributes (id, class)
 *
 * @version v14
 * @since   v14
 */
trait InputAttributesTrait
{
    protected $name = '';
    protected $type = '';
    protected $value = '';

    protected $required = false;

    public function setName($name = '')
    {
        $this->name = $name;
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setType($type = '')
    {
        $this->type = $type;
        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setValue($value = '')
    {
        $this->value = $value;
        return $this;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function isRequired($required = true)
    {
        $this->setRequired($required);
        return $this;
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
