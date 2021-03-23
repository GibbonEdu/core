<?php

namespace Gibbon\Install;

/**
 * Describes an installation context. Includes all environment
 * information. Along with some method to access and check apache
 * and php modules compatibility.
 */
class Context
{

    /**
     * An array of installed Apache module names. Or null if not in Apache
     * environment.
     *
     * @var array|null
     */
    private $installedApacheModules = [];

    /**
     * An array of installed php extension names.
     *
     * @var array|null
     */
    private $installedPhpExtensions = [];

    /**
     * Class constructor.
     *
     * @param array|null $installedApacheModules
     *   An array of the installed apache module in the environment.
     *
     * @param array $installedPhpExtensions
     *   An array of the installed php extensions in the environment.
     */
    public function __construct(
        ?array $installedApacheModules,
        array $installedPhpExtensions
    )
    {
        $this->installedApacheModules = $installedApacheModules;
        $this->installedPhpExtensions = $installedPhpExtensions;
    }

    public static function fromEnvironment(): Context
    {
        return new Context(
            function_exists('apache_get_version') ? apache_get_version() : null,
            function_exists('get_loaded_extensions') ? get_loaded_extensions() : []
        );
    }

    /**
     * Check apache modules existance.
     *
     * @param string[] $modules
     *   A list of module names to be checked.
     * @param bool $passIfNotApache
     *   Returns an empty array if the environment is not an
     *   Apache server. Default true.
     *
     * @throws \Exception
     *   If the installation is not hosted with Apache web server and
     *   the flag $passIfNotApache is set to false.
     *
     * @return bool[]
     *   An associated array of bool value of whether module is
     *   installed or not.
     */
    public function checkApacheModules(array $modules, bool $passIfNotApache = true)
    {
        if ($this->installedApacheModules === null) {
            if ($passIfNotApache) {
                return [];
            }
            throw new \Exception('The installation is not hosted with Apache web server.');
        }

        // construct a list of required modules checked.
        $checks = [];
        foreach ($modules as $module) {
            $checks[$module] = in_array($module, $this->installedApacheModules);
        }

        return $checks;
    }

    /**
     * Check php extensions existance.
     *
     * @param string[] $extensions
     *   A list of extensions names to be checked.
     *
     * @return bool[]
     *   An associated array of bool value of whether module is
     *   installed or not.
     */
    public function checkPhpExtensions(array $extensions)
    {
        // construct a list of required modules checked.
        $checks = [];
        foreach ($extensions as $extension) {
            $checks[$extension] = in_array($extension, $this->installedPhpExtensions);
        }
        return $checks;
    }
}
