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
     * Installation path.
     *
     * @var string
     */
    private $installPath = '';

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
            function_exists('apache_get_modules') ? apache_get_modules() : null,
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

    /**
     * Validate if the config file path is valid.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function validateConfigPath(): void
    {
        if (file_exists($this->getConfigPath())) {
            if (filesize($this->getConfigPath()) > 0) {
                throw new \Exception(__('The file config.php already exists in the root folder and is not empty.'));
            } elseif (!is_writable($this->getConfigPath())) {
                throw new \Exception(__('The file config.php already exists in the root folder and is not writable.'));
            }
        } elseif (!is_writable($this->getInstallPath())) {
            throw new \Exception(__('The directory containing the Gibbon files is not currently writable. Unable to create config.php.'));
        }
    }

    /**
     * Get the path to the supposed location of the config file.
     * Will return the file path even if it is not there.
     *
     * @return string
     */
    public function getConfigPath(): string
    {
        return $this->getPath('config.php');
    }

    /**
     * Get the actual system path based on the installation path.
     *
     * @param string $path  The path to a resource relative to installation path.
     *
     * @return string The actual system path of it.
     */
    public function getPath($path): string
    {
        return implode('/', [
            $this->getInstallPath(),
            trim($path, '/')
        ]);
    }

    /**
     * Set the installation path for the installation.
     *
     * @param string $path
     *
     * @return self
     */
    public function setInstallPath(string $path): Context
    {
        $this->installPath = $path;
        return $this;
    }

    /**
     * Get the installation path for the installation.
     *
     * @return string
     */
    public function getInstallPath(): string
    {
        return $this->installPath;
    }

}
