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

namespace Gibbon\Auth\Access;

class Resource
{
    /**
     * Relevant module name of the capability.
     *
     * @var string
     */
    protected $module = '';

    /**
     * Route path of the module.
     *
     * @var string
     */
    protected $routePath = '';

    /**
     * Name of an action on the resource, if any.
     *
     * @var string
     */
    protected $actionName = '';

    /**
     * Create a capability instance out of route parameters.
     *
     * @param string $module     Relevant module name of the capability.
     * @param string $routePath  Route path of the module.
     * @param string $actionName Optional action name on the routePath. Default: ''.
     *
     * @return Resource
     */
    public static function fromRoute(string $module, string $routePath, string $actionName = ''): Resource
    {
        $instance = new Resource();
        $instance->module = $module;
        $instance->routePath = $routePath;
        $instance->actionName = $actionName;

        return $instance;
    }

    /**
     * Create a capability instance out of the legacy path name
     * of a module action.
     *
     * @return Resource
     */
    public static function fromLegacyPath(string $path): Resource
    {
        return Resource::fromRoute(
            static::parseModuleName($path),
            static::parseRoutePath($path)
        );
    }

    /**
     * Get the module name from the address.
     *
     * From the original "getModuleName" function.
     *
     * @param string $path
     *
     * @return string The parsed module name.
     */
    private static function parseModuleName(string $path): string
    {
        return substr(substr($path, 9), 0, strpos(substr($path, 9), '/'));
    }

    /**
     * Get the legacy route path from the address.
     *
     * From the original "getActionName" function.
     *
     * @param string $path
     *
     * @return string The parsed action name
     */
    private static function parseRoutePath(string $path): string
    {
        return substr(substr($path, (10 + strlen(static::parseModuleName($path)))), 0, -4);
    }

    /**
     * Get the module name of the capability.
     *
     * @return string
     */
    public function getModule(): string
    {
        return $this->module;
    }

    /**
     * Get the action string of the capability.
     *
     * @return string
     */
    public function getRoutePath(): string
    {
        return $this->routePath;
    }

    /**
     * Get the action string of the capability.
     *
     * @return string
     */
    public function getActionName(): string
    {
        return $this->actionName;
    }

    /**
     * Get the action string a '.php' suffix for backward compatibility.
     *
     * @return string
     */
    public function getLegacyRoutePath(): string
    {
        return $this->routePath . '.php';
    }

    /**
     * Convert the capability to legacy address path.
     *
     * @return string
     */
    public function toLegacyPath(): string
    {
        return '/modules/' . $this->getModule() . '/' . $this->getLegacyRoutePath();
    }
}
