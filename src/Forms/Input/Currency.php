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
 * Date
 *
 * @version v14
 * @since   v14
 */
class Currency extends Number
{
    protected $decimalPlaces = 2;
    protected $onlyInteger = false;

    /**
     * Adds currency format to the label description (if not already present)
     * @return string|bool
     */
    public function getLabelContext($label)
    {
        global $guid;

        if (stristr($label->getDescription(), 'In ') === false) {
            return sprintf(__('In %1$s.'), $_SESSION[$guid]['currency']);
        }

        return false;
    }
}
