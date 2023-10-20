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

use Gibbon\Forms\Traits\InputAttributesTrait;
use Gibbon\Forms\Layout\Element;

/**
 * Button
 *
 * @version v14
 * @since   v14
 */
class Button extends Element
{
    use InputAttributesTrait;
    
    private $onclick;

    public function __construct($name, $onClick)
    {
        $this->setName($name);
        $this->onClick($onClick);
        $this->setValue($name);
        $this->setID($name);
        $this->addClass('button');
    }

    public function onClick($value)
    {
        $this->setAttribute('onClick', $value);
        return $this;
    }

    protected function getElement()
    {
        $output = '<button type="button" '.$this->getAttributeString().'>'.$this->getValue().'</button>';
        return $output;
    }
}
