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

/**
 * A utility class for simple templates written in pure PHP. Template files can
 * be placed next to classes, with paths provided as a class namespace.
 */
class Component
{
    /**
     * @var string
     */
    private static $path = '';

    /**
     * @var array
     */
    private static $environment = [];

    /**
     * Provide a template path and set of environment variables for rendered templates.
     * @param string $path
     * @param array $environment
     */
    public static function withEnvironment(string $path = '', array $environment = [])
    {
        static::$path = rtrim($path, '/').'/';
        static::$environment = $environment;
    }

    /**
     * @param string $view      The full namespace of the class component to include
     * @param array $context
     * @return string
     * @throws \Exception
     */
    public static function render(string $view, array $context = []): string
    {
        static::$path = empty(static::$path) ? realpath(__DIR__.'/../').'/' : static::$path;

        $view = str_replace(['Gibbon\\', '\\', '..'], ['', '/', ''], ltrim($view, '/'));

        if (!file_exists($file = static::$path.$view.'.template.php')) {
            throw new \Exception(sprintf('The component %s could not be found.', $file));
        }

        extract(static::safeExtract(array_merge($context, static::$environment)));

        ob_start();

        include($file);

        return ob_get_clean();
    }

    private static function safeExtract($array)
    {
        $safeKeys = array_map(function ($key) {
            return preg_replace('/[^a-zA-Z0-9]/', '_', $key);
        }, array_keys($array));

        return array_combine($safeKeys, array_values($array));
    }
}
