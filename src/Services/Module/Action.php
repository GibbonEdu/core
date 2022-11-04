<?php

namespace Gibbon\Services\Module;

class Action
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
     * Action name of the action.
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
     * @return Action
     */
    public static function fromRoute(string $module, string $routePath, string $actionName = ''): Action
    {
        $instance = new Action();
        $instance->module = $module;
        $instance->routePath = $routePath;
        $instance->actionName = $actionName;
        return $instance;
    }

    /**
     * Create a capability instance out of the legacy path name
     * of a module action.
     *
     * @return Action
     */
    public static function fromLegacyPath(string $path): Action
    {
        return Action::fromRoute(
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
