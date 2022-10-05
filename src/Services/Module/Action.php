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
     * Action of the capability for the module.
     *
     * @var string
     */
    protected $action = '';

    /**
     * Constructor.
     *
     * @param string $module  Relevant module name of the capability.
     * @param string $action  Action of the capability for the module.
     */
    public function __construct(string $module, string $action)
    {
        $this->module = $module;
        $this->action = $action;
    }

    /**
     * Create a capability instance out of the legacy path name
     * of a module action.
     *
     * @return Action
     */
    public static function fromLegacyPath(string $path): Action
    {
        return new Action(
            static::getModuleName($path),
            static::getActionName($path)
        );
    }

    /**
     * Get the module name from the address
     *
     * @param string $path
     *
     * @return string The parsed module name.
     */
    private static function getModuleName(string $path): string
    {
        return substr(substr($path, 9), 0, strpos(substr($path, 9), '/'));
    }

    /**
     * Get the action name from the address
     *
     * @param string $path
     *
     * @return string The parsed action name
     */
    private static function getActionName(string $path): string
    {
        return substr(substr($path, (10 + strlen(static::getModuleName($path)))), 0, -4);
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
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * Get the action string a '.php' suffix for backward compatibility.
     *
     * @return string
     */
    public function getLegacyAction(): string
    {
        return $this->action . '.php';
    }

    /**
     * Convert the capability to legacy address path.
     *
     * @return string
     */
    public function toLegacyPath(): string
    {
        return '/modules/' . $this->getModule() . '/' . $this->getLegacyAction();
    }
}
