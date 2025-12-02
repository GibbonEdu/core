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

use Gibbon\View\Component;
use Gibbon\Forms\Traits\MultipleOptionsTrait;

/**
 * ToggleButton
 *
 * @version v31
 * @since   v31
 */
class ToggleButton extends Input
{
    use MultipleOptionsTrait;

    /**
     * Create a toggle input with a default value.
     * @param  string  $name
     */
    public function __construct($name, $default = '0')
    {
        $this->setName($name);
        $this->setID($name);
        $this->setValue($default);
    }

    /**
     * Sets the state of the toggle input.
     * @param   mixed  $value
     * @return  self
     */
    public function selected($value)
    {
        return $this->setValue($value);
    }

    /**
     * Gets the HTML output for this form element.
     * @return  string
     */
    protected function getElement()
    {
        $selected = !empty($this->getValue()) ? $this->getValue() : current($this->options);
        return Component::render(ToggleButton::class, $this->getAttributeArray() + [
            'options' => str_replace('"', "'", json_encode($this->getOptions() ?? [], JSON_FORCE_OBJECT)),
            'optionCount' => $this->getOptionCount(),
            'selected' => $selected,
        ]);
    }
}
