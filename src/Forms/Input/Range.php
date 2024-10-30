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

use Gibbon\Forms\Element;
use Gibbon\View\Component;

/**
 * Range Slider
 *
 * @version v17
 * @since   v17
 */
class Range extends Input
{
    /**
     * Create an HTML range slider.
     * @param  string  $name
     */
    public function __construct($name, $min, $max, $step = 1)
    {
        parent::__construct($name);

        $this->setAttribute('min', $min);
        $this->setAttribute('max', $max);
        $this->setAttribute('step', $step);
    }

    /**
     * Gets the HTML output for this form element.
     * @return  string
     */
    protected function getElement()
    {
        return Component::render(Range::class, $this->getAttributeArray() + []);
    }
}
