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

namespace Gibbon\View;

/**
 * Base class for Views.
 *
 * @version  v17
 * @since    v17
 */
abstract class View implements \ArrayAccess, \IteratorAggregate, \Countable
{
    protected $templateEngine;

    protected $content = [];
    protected $data = [];

    /**
     * Create a new page from a variable set of constructor params.
     *
     * @param array $params Essential parameters for building a page.
     */
    public function __construct($templateEngine = null)
    {
        $this->templateEngine = $templateEngine;
    }

    /**
     * Writes a string to the page's internal content property.
     *
     * @param string $value
     */
    public function write(string $value)
    {
        $this->content[] = $value;
    }

    /**
     * Writes the output buffered result from a PHP script to the page's content.
     *
     * @param string $filepath
     * @param array $data
     */
    public function writeFromFile(string $filepath, array $data = [])
    {
        $this->write($this->fetchFromFile($filepath, $data));
    }

    /**
     * Writes a rendered template file to the page's content.
     *
     * @param string $template
     * @param array $data
     */
    public function writeFromTemplate(string $template, array $data = [])
    {
        $this->write($this->fetchFromTemplate($template, $data));
    }

    /**
     * Includes a PHP file in a protected scope, and returns the
     * output-buffered contents as a string.
     *
     * @param string $filepath
     * @param array  $data
     * @return string
     */
    public function fetchFromFile(string $filepath, array $data = []) : string
    {
        if (!is_file($filepath)) {
            return '';
        }

        // Extracts the array of data into individual variables in the current scope.
        extract($data);

        try {
            ob_start();
            $included = include $filepath;
            $output = ob_get_clean() . (is_string($included)? $included : '');
        } catch (\Exception $e) {
            $output = '';
            ob_end_clean();
            throw $e;
        }

        return $output;
    }

    /**
     * Renders a given template using the template engine + provided data
     * and returns the result as a string.
     *
     * @param string $template
     * @param array  $data
     * @return string
     */
    public function fetchFromTemplate(string $template, array $data = []) : string
    {
        return $this->templateEngine->render($template, $data);
    }

    /**
     * Render the entire page with the given template and return the result as a string.
     *
     * @param string $template
     * @param array $data
     * @return string
     */
    public function render(string $template, array $data = []) : string
    {
        $data = array_merge($this->data, $data);

        return $this->templateEngine->render($template, $data);
    }

    public function add(array $data = [])
    {
        $this->data = array_merge($this->data, $data);
    }

    /********************************************************************************
     * ArrayAccess interface
     *******************************************************************************/
    /**
     * Does this collection have a given key?
     *
     * @param  string $key The data key
     *
     * @return bool
     */
    public function offsetExists($key)
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Get collection item for key
     *
     * @param string $key The data key
     *
     * @return mixed The key's value, or the default value
     */
    public function offsetGet($key)
    {
        return $this->data[$key];
    }

    /**
     * Set collection item
     *
     * @param string $key   The data key
     * @param mixed  $value The data value
     */
    public function offsetSet($key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * Remove item from collection
     *
     * @param string $key The data key
     */
    public function offsetUnset($key)
    {
        unset($this->data[$key]);
    }

    /********************************************************************************
     * Countable interface
     *******************************************************************************/
    /**
     * Get number of items in collection
     *
     * @return int
     */
    public function count()
    {
        return count($this->data);
    }

    /********************************************************************************
     * IteratorAggregate interface
     *******************************************************************************/
    /**
     * Get collection iterator
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }
}
