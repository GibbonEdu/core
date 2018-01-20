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
use Gibbon\Forms\Traits\BasicAttributesTrait;

/**
 * Element
 *
 * @version v14
 * @since   v14
 */
class Element implements OutputableInterface
{
    use BasicAttributesTrait;

    protected $content;
    protected $appended;
    protected $prepended;
    protected $before;
    protected $after;

    /**
     * Create a generic form element that only holds content.
     * @param  string  $content
     */
    public function __construct($content = '')
    {
        $this->content = $content;
    }

    /**
     * Set the content of the element, replaces existing content.
     * @param  string  $value
     * @return self
     */
    public function setContent($value)
    {
        $this->content = $value;
        return $this;
    }

    /**
     * Add a string to the beginning of the current content.
     * @param  string  $value
     * @return self
     */
    public function prepend($value)
    {
        $this->prepended .= $value;
        return $this;
    }

    /**
     * Add a string to the end of the current content.
     * @param  string  $value
     * @return self
     */
    public function append($value)
    {
        $this->appended .= $value;
        return $this;
    }

    /**
     * Add strings before and after to wrap the current content.
     * @param  string  $value
     * @return self
     */
    public function wrap($before, $after)
    {
        $this->before = $before . $this->before;
        $this->after = $this->after . $after;

        return $this;
    }

    /**
     * Get the HTML output of the content element.
     * @return  string
     */
    public function getOutput()
    {
        return $this->before.$this->prepended.$this->getElement().$this->appended.$this->after;
    }

    /**
     * Get the content text of the element.
     * @return  string
     */
    protected function getElement()
    {
        return $this->content;
    }
}
