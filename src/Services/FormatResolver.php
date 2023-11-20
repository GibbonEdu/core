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

namespace Gibbon\Services;

/**
 * The resolver allows modules to add their own formatters, which can be accessed with Format::newMethod
 * I can also return a formatter as a closure with the using() method, which can be provided to classes
 * such as the DataTable that loop over and format array data.
 * 
 * @version v16
 * @since   v16
 */
trait FormatResolver
{
    protected static $formatters;

    /**
     * Add a callable as an available format during runtime. Useful for additional modules.
     *
     * @param string $method
     * @param callable $callable
     */
    public static function addFormatter($method, callable $callable)
    {
        static::$formatters[$method] = $callable;
    }

    /**
     * Get the callable function or method for a format by name.
     *
     * @param string $method
     * @return callable
     */
    public static function getFormatter($method)
    {
        if (isset(static::$formatters[$method])) {
            return static::$formatters[$method];
        }

        if (method_exists(static::class, $method)) {
            return [static::class, $method];
        }
        
        throw new \InvalidArgumentException(sprintf('Unknown formatter "%s"', $method));
    }

    /**
     * Returns a callable function that can be used to format a bulk array of data.
     * 
     * The callable returned takes a single array of data and returns the formatted string.
     *
     * @param string $method
     * @param array $param
     * @return callable
     */
    public static function using($method, $param = null)
    {
        $callable = static::getFormatter($method);
        $params = !is_null($param)? (is_array($param)? $param : array($param)) : false;

        return function ($data) use ($callable, $params) {
            if ($params && is_array($data)) {
                $args = array_map(function($key) use (&$data) {
                    return is_string($key) && array_key_exists(strval($key), $data)? $data[$key] : $key;
                }, $params);
                return call_user_func_array($callable, $args);
            } else {
                return $callable($data);
            }
        };
    }

    /**
     * Formats an array of data into key => value pairs by applying a format method to each returned value.
     *
     * @param array $data
     * @param string $key
     * @param string $method
     * @param array $param
     * @return array
     */
    public static function keyValue($data, $key, $method, $param = null) 
    {
        $values = array();
        $callable = is_callable($method)? $method : static::using($method, $param);

        foreach ($data as $row) {
            if (!isset($row[$key])) continue;
            $values[$row[$key]] = $callable($row);
        }

        return $values;
    }

    /**
     * Calls a format method by name, allowing pre-defined formatters to be used.
     *
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic($method, $arguments = array())
    {
        return call_user_func_array(static::getFormatter($method), $arguments);
    }
}
