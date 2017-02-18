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

    public function __construct($content)
    {
        $this->content = $content;
    }

    public function prepend($value)
    {
        $this->prepended .= __($value);
        return $this;
    }

    public function append($value)
    {
        $this->appended .= __($value);
        return $this;
    }

    public function getOutput()
    {
        return $this->prepended.$this->getElement().$this->appended;
    }

    protected function getElement()
    {
        return __($this->content);
    }

    /**
     * getAttributeOutput
     *
     * Flattens an array of $name => $value pairs into an HTML attribues string name="value". Omits empty values and handles booleans.
     * @version  v14
     * @since    v14
     * @param    [type]  $attributes
     * @return   [type]
     */
    protected function getAttributeOutput($attributes)
    {
        $output = implode(' ', array_map(
            function ($key) use ($attributes) {
                if (is_bool($attributes[$key])) {
                    return $attributes[$key]?$key:'';
                }
                if (!empty($attributes[$key])) {
                    return $key.'="'.$attributes[$key].'"';
                }
                return '';
            },
            array_keys($attributes)
        ));

        return $output;
    }
}
