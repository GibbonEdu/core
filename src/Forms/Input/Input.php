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

use Gibbon\Forms\Layout\Element;
use Gibbon\Forms\ValidatableInterface;
use Gibbon\Forms\Traits\InputAttributesTrait;

/**
 * Input
 *
 * @version v14
 * @since   v14
 */
abstract class Input extends Element implements ValidatableInterface
{
    use InputAttributesTrait;

    protected $validation = array();

    public function __construct($name)
    {
        $this->setID($name);
        $this->setName($name);
        $this->setClass('standardWidth');
    }

    public function addValidation($type, $params = '')
    {
        $this->validation[$type] = $params;
        return $this;
    }

    public function getValidation()
    {
        $output = '';

        if ($this->getRequired() == true || !empty($this->validation)) {
            $output .= 'var '.$this->getName().'Validate=new LiveValidation(\''.$this->getName().'\'); '."\r";

            if ($this->getRequired() == true) {
                $output .= $this->getName().'Validate.add(Validate.Presence); '."\r";
            }

            if (!empty($this->validation) && is_array($this->validation)) {
                foreach ($this->validation as $type => $params) {
                    $output .= $this->getName().'Validate.add('.$type.', {'.$params.' } ); '."\r";
                }
            }
        }

        return $output;
    }
}
