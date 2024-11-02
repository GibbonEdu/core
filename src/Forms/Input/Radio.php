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

use Gibbon\Forms\Traits\MultipleOptionsTrait;
use Gibbon\View\Component;

/**
 * Radio
 *
 * @version v14
 * @since   v14
 */
class Radio extends Input
{
    use MultipleOptionsTrait;

    protected $inline = false;
    protected $align = 'left';

    public function __construct($name)
    {
        $this->setID(''); // Cannot share an ID across multiple Radio inputs
        $this->setName($name);
    }

    /**
     * Set the value of the radio element.
     * @param   mixed  $value
     * @return  self
     */
    public function checked($value)
    {
        $this->setValue(trim($value));
        return $this;
    }

    /**
     * Sets multiple radio elements to display horizontally.
     * @param   bool    $value
     * @return  self
     */
    public function inline($value = true)
    {
        $this->inline = $value;
        $this->addClass('right');
        return $this;
    }

    /**
     * Aligns the list options to the right edge.
     * @return  self
     */
    public function alignRight()
    {
        $this->align = 'right';
        return $this;
    }

    /**
     * Aligns the list options to the left edge.
     * @return  self
     */
    public function alignLeft()
    {
        $this->align = 'left';
        return $this;
    }

    /**
     * Aligns the list options to the center.
     * @return  self
     */
    public function alignCenter()
    {
        $this->align = 'center';
        $this->addClass('text-center');
        return $this;
    }

    /**
     * Dead-end stub for interface: LiveValidation does not support Radio elements.
     * @param  string  $type
     * @param  string  $params
     */
    public function addValidation($type, $params = '')
    {
        return $this;
    }

    /**
     * Return true if the passed value matches the current radio element value.
     * @param   mixed  $value
     * @return  bool
     */
    protected function getIsChecked($value)
    {
        return (!is_null($this->getValue()) && $value == $this->getValue());
    }

    /**
     * Gets the HTML output for this form element.
     * @return  string
     */
    protected function getElement()
    {
        if (!empty($this->getOptions()) && is_array($this->getOptions())) {
            // Select the first option by default for required Radio elements with no checked value set
            if ($this->getRequired() && is_null($this->getValue())) {
                $firstOption = key($this->getOptions());
                $this->checked($firstOption);
            }
        }

        return Component::render(Radio::class, $this->getAttributeArray() + [
            'options'      => $this->getOptions(),
            'totalOptions' => $this->getOptionCount(),
            'inline'       => $this->inline,
            'align'        => $this->align,
            'count'        => 0,
        ]);
    }
}
