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

namespace Gibbon\Domain\System;

use Gibbon\View\AssetBundle;

/**
 * Gibbon Module Model.
 *
 * @version v17
 * @since   v17
 */
class Module
{
    protected $gibbonModuleID;
    protected $name;
    protected $version;
    protected $entryURL;

    protected $stylesheets;
    protected $scripts;

    public function __construct(array $params = [])
    {
        // Merge constructor params into class properties
        foreach ($params as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }

        $this->stylesheets = new AssetBundle();
        $this->scripts = new AssetBundle();

        $this->stylesheets->add(
            'module',
            'modules/'.$this->name.'/css/module.css',
            ['version' => $this->version]
        );
        $this->scripts->add(
            'module',
            'modules/'.$this->name.'/js/module.js',
            ['version' => $this->version, 'context' => 'head']
        );
    }

    /**
     * Allow read-only access of model properties.
     *
     * @param string $name
     * @return mixed
     */
    public function __get(string $name)
    {
        return isset($this->$name) ? $this->$name : null;
    }

    /**
     * Check if a model property exists.
     *
     * @param string $name
     * @return mixed
     */
    public function __isset(string $name)
    {
        return isset($this->$name);
    }

    /**
     * Get the gibbonModuleID
     *
     * @return string
     */
    public function getID()
    {
        return $this->gibbonModuleID;
    }

    /**
     * Get the module name, used in the folder path and database record.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Given a class fullpath string, returns the speculative
     * full path to the class file.
     *
     * All class path should follow this pattern:
     *   `Gibbon\ModuleName\SubNamespaceIfApplicable\Class`
     *
     * The `ModuleName` portion will be processed. All capitalized
     * word in the module name will be separated by space. I.E.
     * `ModuleName` will become `Module Name` for directory name.
     *
     * @param string $class Full namespaced class name.
     *
     * @return string|null  The full file path, or null if the
     *                      namespaced class name does not match
     *                      the pattern of a correct Gibbon module
     *                      class. Note that, if returned, the path
     *                      is only speculative. The module or file
     *                      might not exists.
     */
    public static function getAutoloadFilepath(string $class)
    {
        if (preg_match('/^Gibbon\\\\Module\\\\(.+?)\\\\(.+)$/', $class, $matches)) {
            list($all, $module, $subclass) = $matches;
            $basePath = realpath(__DIR__ . '/../../../modules');
            $modulePath = trim(implode(' ', preg_split('/(?=[A-Z])/', $module)), ' ');
            $classPath = implode(DIRECTORY_SEPARATOR, explode('\\', $subclass));
            return $basePath .
                DIRECTORY_SEPARATOR . $modulePath .
                DIRECTORY_SEPARATOR . 'src' .
                DIRECTORY_SEPARATOR . $classPath . '.php';
        }
        return null;
    }

    /**
     * Given a full namspaced class name, this function
     * will try to automatically load the class file.
     *
     * @param string $class
     *
     * @return void
     */
    public static function autoload(string $class)
    {
        if (($path = static::getAutoloadFilepath($class)) !== null) {
            include_once $path;
        }
        return;
    }
}
