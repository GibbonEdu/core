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

namespace Gibbon\View;

use Twig\Environment;

/**
 * Base class for Views.
 *
 * @version  v17
 * @since    v17
 */
class View implements \ArrayAccess
{
    protected $templateEngine;

    protected $data = [];

    /**
     * Create a new view.
     *
     * @param $templateEngine
     */
    public function __construct(Environment $templateEngine = null)
    {
        // Do some duck typing, since Twig does not have a common Interface.
        if (!is_null($templateEngine) && !method_exists($templateEngine, 'render')) {
            throw new \InvalidArgumentException("The template engine passed into a View constructor must implement a render() method.");
        }

        $this->templateEngine = $templateEngine;
    }

    /**
     * Add a piece of data to the view.
     *
     * @param  string|array  $key
     * @param  mixed   $value
     */
    public function addData($key, $value = null)
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }
    }

    /**
     * Include a PHP file in a protected scope, and returns the output-buffered
     * contents as a string. The data array is extracted into individual
     * variables in the current scope.
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
     * Render a given template using the template engine + provided data
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
     * Render the view with the given template and return the result as a string.
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

    /**
     * Determine if a piece of data is bound.
     *
     * @param  string  $key
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($key) : bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Get a piece of bound data to the view.
     *
     * @param  string  $key
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($key)
    {
        return $this->data[$key];
    }

    /**
     * Set a piece of data on the view.
     *
     * @param  string  $key
     * @param  mixed   $value
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * Unset a piece of data from the view.
     *
     * @param  string  $key
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($key)
    {
        unset($this->data[$key]);
    }
}
