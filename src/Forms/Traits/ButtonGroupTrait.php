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

namespace Gibbon\Forms\Traits;

/**
 * Allows for fields to act as a button group (left, middle, center)
 *
 * @version v28
 * @since   v28
 */
trait ButtonGroupTrait
{
    protected $group;

    /**
     * Create a button group by setting the alignment of this button.
     *
     * @param string $value     One of: left, middle, right
     * @return self
     */
    public function groupAlign($value)
    {
        $this->group = $value;
        return $this;
    }

    public function groupAlignBoth(bool $leftAlign, bool $rightAlign)
    {
        return (!$leftAlign && !$rightAlign)
            ? $this->groupAlign('middle')
            : $this->groupAlign($leftAlign ? 'left' : ($rightAlign ? 'right' : ''));
    }

    public function getGroupClass()
    {
        if ($this->group == 'left') return 'rounded-l-md -mr-px';
        elseif ($this->group == 'right') return 'rounded-r-md -ml-px';
        elseif ($this->group == 'middle') return 'rounded-none';
        else return 'rounded-md';
    }

    public function getGroupAlign()
    {
        return $this->group;
    }

}
