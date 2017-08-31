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

namespace Gibbon\Forms\Traits;

/**
 * Basic HTML Attributes (id, class)
 *
 * @version v14
 * @since   v14
 */
trait BasicAttributesTrait
{
    private $attributes = array();

    /**
     * Set the id attribute.
     * @param  string  $id
     * @return self
     */
    public function setID($id = '')
    {
        $this->setAttribute('id', $id);
        return $this;
    }

    /**
     * Gets the id attribute.
     * @return  string
     */
    public function getID()
    {
        return $this->getAttribute('id');
    }

    /**
     * Set the title attribute.
     * @param  string  $title
     * @return self
     */
    public function setTitle($title = '')
    {
        $this->setAttribute('title', $title);
        return $this;
    }

    /**
     * Gets the title attribute.
     * @return  string
     */
    public function getTitle()
    {
        return $this->getAttribute('title');
    }

    /**
     * Set the class attribute. Replaces any existing classes.
     * @param  string  $class
     * @return self
     */
    public function setClass($class = '')
    {
        $this->setAttribute('class', $class);
        return $this;
    }

    /**
     * Add a class to the element's class atrribute.
     * @param  string  $class
     */
    public function addClass($class = '')
    {
        $class = (!empty($this->getClass()))? $this->getClass().' '.$class : $class;
        $this->setAttribute('class', $class);
        return $this;
    }

    /**
     * Remove a class from the element's class atrribute.
     * @param  string  $class
     */
    public function removeClass($class = '')
    {
        $class = (!empty($this->getClass()))? str_replace($class, '', $this->getClass()) : '';
        $this->setAttribute('class', $class);
        return $this;
    }

    /**
     * Gets the class attribute.
     * @return  string
     */
    public function getClass()
    {
        return $this->getAttribute('class');
    }

    /**
     * Add a $key => $value pair to the attributes collection.
     * @param  string  $key
     * @param  mixed   $value
     */
    protected function setAttribute($key, $value)
    {
        $this->attributes[$key] = $value;
        return $this;
    }

    /**
     * Get the value of an attribute by the provided key.
     * @param   string  $key
     * @return  mixed
     */
    protected function getAttribute($key)
    {
        return (isset($this->attributes[$key]))? $this->attributes[$key] : null;
    }

    /**
     * Get the internal collection of attributes.
     * @return  array
     */
    protected function getAttributeArray()
    {
        return $this->attributes;
    }

    /**
     * Flattens an array of $name => $value pairs into an HTML attribues string name="value". Omits empty values and handles booleans.
     * @return  string
     */
    public function getAttributeString()
    {
        $attributes = $this->getAttributeArray();

        $output = implode(' ', array_map(
            function ($key) use ($attributes) {
                if (is_bool($attributes[$key])) {
                    return $attributes[$key]? $key : '';
                }
                if (isset($attributes[$key]) && $attributes[$key] != '') {
                    return $key.'="'.$attributes[$key].'"';
                }
                return '';
            },
            array_keys($attributes)
        ));

        return $output;
    }
}
