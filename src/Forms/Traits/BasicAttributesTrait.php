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
 * Basic HTML Attributes (id, class)
 *
 * @version v14
 * @since   v14
 */
trait BasicAttributesTrait
{
    private $attributes = [];
    private $attributeDefaults = ['id' => '', 'name' => '', 'class' => '', 'disabled' => '', 'readonly' => ''];

    /**
     * Set the id attribute.
     * @param  string  $id
     * @return self
     */
    public function setID($id = '')
    {
        $id = str_replace(['[',']'], '', $id);
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
        return $this->getAttribute('class') ?? '';
    }

    /**
     * Set a data-* attribute for passing values to scripts.
     * @param  string $name
     * @param  mixed  $data
     * @return self
     */
    public function addData($name, $data = true, $encode = false)
    {
        if ($encode || is_array($data)) $data = json_encode($data);
        $this->setAttribute('data-'.$name, $data);

        return $this;
    }

    /**
     * Gets a data-* attribute value by name.
     * @return  string $name
     */
    public function getData($name, $decode = false)
    {
        $data = $this->getAttribute('data-'.$name);

        return ($decode)? json_decode($data) : $data;
    }

    public function isInstanceOf($instance)
    {
        return $this instanceof $instance;
    }

    /**
     * Add a $key => $value pair to the attributes collection.
     * @param  string  $key
     * @param  mixed   $value
     */
    public function setAttribute($key, $value = '')
    {
        $this->attributes[$key] = $value;
        return $this;
    }

    /**
     * Get the value of an attribute by the provided key.
     * @param   string  $key
     * @return  mixed
     */
    public function getAttribute($key)
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * Get the internal collection of attributes.
     * @return  array
     */
    public function getAttributeArray()
    {
        return array_merge($this->attributeDefaults, ['attributes' => $this->getAttributeString(false, 'class')], $this->attributes);
    }

    /**
     * Flattens an array of $name => $value pairs into an HTML attribues string name="value". Omits empty values and handles booleans.
     * @param   array|string  $filter  Return a filtered subset of attributes by name.
     * @return  string
     */
    public function getAttributeString($filter = '', $exclude = '')
    {
        $attributes = $this->attributes;
        if (!empty($filter)) {
            $filter = is_string($filter)? explode(',', $filter) : $filter;
            $attributes = array_intersect_key($attributes, array_flip($filter));
        }

        if (!empty($exclude) && isset($attributes[$exclude])) {
            $exclude = is_string($exclude)? explode(',', $exclude) : $exclude;
            $attributes = array_diff_key($attributes, array_flip($exclude));
        }

        $output = implode(' ', array_map(
            function ($key) use ($attributes) {
                if (is_bool($attributes[$key])) {
                    return $attributes[$key]? $key : '';
                }
                if (isset($attributes[$key])) {
                    return $attributes[$key] != ''
                        ? $key.'="'.htmlPrep($attributes[$key]).'"'
                        : $key;
                }
                return '';
            },
            array_keys($attributes)
        ));

        return $output;
    }
}
