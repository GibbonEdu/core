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

    public static function addFormatter($method, callable $callable)
    {
        static::$formatters[$method] = $callable;
    }

    public static function getFormatter($method)
    {
        if (method_exists(static::class, $method)) {
            return [static::class, $method];
        }

        if (isset(static::$formatters[$method])) {
            return static::$formatters[$method];
        }
        
        throw new \InvalidArgumentException(sprintf('Unknown formatter "%s"', $method));
    }

    public static function using($method, $param)
    {
        $callable = static::getFormatter($method);
        $params = is_array($param)? $param : array($param);

        return function ($data) use ($callable, $params) {
            $args = array_map(function($key) use (&$data) {
                return isset($data[$key])? $data[$key] : $key;
            }, $params);

            return $callable(...$args);
        };
    }

    public static function __callStatic($method, $arguments = array())
    {
        return call_user_func_array(static::getFormatter($method), $arguments);
    }
}
