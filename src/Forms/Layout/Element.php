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

    public function __construct($content = '')
    {
        $this->content = $content;
    }

    public function setContent($value)
    {
        $this->content = $value;
        return $this;
    }

    public function prepend($value)
    {
        $this->prepended .= $value;
        return $this;
    }

    public function append($value)
    {
        $this->appended .= $value;
        return $this;
    }

    public function wrap($before, $after)
    {
        $this->prepend($before);
        $this->append($after);

        return $this;
    }

    public function getOutput()
    {
        return $this->prepended.$this->getElement().$this->appended;
    }

    protected function getElement()
    {
        return $this->content;
    }
}
