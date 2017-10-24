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
 * Button
 *
 * @version v14
 * @since   v14
 */
class Button extends Input
{
    private $name;
    private $id;
    private $onclick;

    public function __construct($name, $onClick)
    {
        $this->setName($name);
        $this->onClick($onClick);
        $this->setElementAttributes();
    }

    public function onClick($value)
    {
        $this->onclick = $value;
        $this->setElementAttributes();
        return $this;
    }

    public function setName($name = '')
    {
        $this->id = $name;
        $this->name = $name;
        $this->setElementAttributes();
    }

    public function getName()
    {
        return $this->name;
    }

    protected function setElementAttributes()
    {
        $this->setAttribute('value', $this->getName());
        $this->setAttribute('id', $this->getName());
        $this->setAttribute('onClick', $this->onclick);
    }

    protected function getElement()
    {
        $output = '<input type="button" '.$this->getAttributeString().'>';

        return $output;
    }
}
