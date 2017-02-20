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

namespace Gibbon\Forms\Layout;

use Gibbon\Forms\OutputableInterface;
use Gibbon\Forms\FormFactoryInterface;

/**
 * Column
 *
 * @version v14
 * @since   v14
 */
class Column extends Row implements OutputableInterface
{
    protected $class = 'column';

    public function __construct(FormFactoryInterface $factory, $id = '')
    {
        $this->setClass('column');
        parent::__construct($factory, $id);
    }

    public function getOutput()
    {
        $output = '';

        foreach ($this->getElements() as $element) {
            $output .= '<div>';
            $output .= $element->getOutput();
            $output .= '</div>';
        }

        return $output;
    }
}
