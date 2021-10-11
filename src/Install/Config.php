<?php

namespace Gibbon\Install;

class Config
{
    /**
     * Gibbon installation guid.
     *
     * @var string
     */
    private $guid = '';

    /**
     * Hostname of database server.
     *
     * @var string|null
     */
    private $databaseServer = null;

    /**
     * Database name.
     *
     * @var string|null
     */
    private $databaseName = null;

    /**
     * Database server username.
     *
     * @var string|null
     */
    private $databaseUsername = null;

    /**
     * Raw database password for the user.
     *
     * @var string|null
     */
    private $databasePassword = null;

    /**
     * Should the demo data be installed along.
     *
     * @var boolean|null
     */
    private $flagDemoData = null;

    /**
     * The locale code for the installation.
     *
     * @var boolean
     */
    private $locale = 'en_GB';

    /**
     * Create config instance for given config file path.
     *
     * @param string $path
     *
     * @return Config The config instance with information from config file, includes
     *   database connection info and guid.
     */
    public static function fromFile(string $path): Config
    {
        if (!file_exists($path) || !is_file($path)) {
            throw new \Exception('Provided path does not exists or is not a file.');
        }
        if (!is_readable($path)) {
            throw new \Exception('Provided path is not readable.');
        }

        $config = (function () use ($path) {
            include($path);
            return compact('databaseServer', 'databaseUsername', 'databasePassword', 'databaseName', 'guid');
        })();

        return (new static())
            ->setDatabaseInfo(
                $config['databaseServer'],
                $config['databaseName'],
                $config['databaseUsername'],
                $config['databasePassword']
            )
            ->setGuid($config['guid']);
    }

    /**
     * Set the database related variables.
     *
     * @param string $databaseServer
     * @param string $databaseName
     * @param string $databaseUsername
     * @param string $databasePassword
     *
     * @return self
     */
    public function setDatabaseInfo(
        string $databaseServer,
        string $databaseName,
        string $databaseUsername,
        string $databasePassword = ''
    ): Config
    {
        $this->databaseServer = $databaseServer;
        $this->databaseName = $databaseName;
        $this->databaseUsername = $databaseUsername;
        $this->databasePassword = $databasePassword;
        return $this;
    }

    /**
     * Set guid to the given string, or random set the
     * guid.
     *
     * @param string $guid  Gibbon installation ID string.
     *                      Would generate a randomize guid
     *                      if not set or empty.
     *
     * @return self
     */
    public function setGuid(string $guid = ''): Config
    {
        $this->guid = $guid ?: static::randomGuid();
        return $this;
    }

    /**
     * Get the guid string for this config.
     *
     * @return string
     */
    public function getGuid(): string
    {
        return $this->guid;
    }

    /**
     * Generate a randomized uuid-like string for the installation.
     *
     * @return string Random guid string.
     */
    public static function randomGuid(): string
    {
        $charList = 'abcdefghijkmnopqrstuvwxyz023456789';
        $guid = '';
        for ($i = 0;$i < 36;++$i) {
            if ($i == 9 or $i == 14 or $i == 19 or $i == 24) {
                $guid .= '-';
            } else {
                $guid .= substr($charList, rand(1, strlen($charList)), 1);
            }
        }
        return $guid;
    }

    /**
     * Get an assoc array of all the config file required variables
     * for config file rendering or else.
     *
     * @return array Associated array of all config file variables, includes
     *               everything from getDatabaseInfo() and 'guid'.
     */
    public function getVars(): array
    {
        return $this->getDatabaseInfo() + [
            'guid' => $this->getGuid(),
        ];
    }

    /**
     * Get database related configurations.
     *
     * @return array
     *   An assoc array of database configurations. Include these keys:
     *   - databaseServer: The hostname of the database server.
     *   - databaseName: The database name.
     *   - databaseUsername: The username for database server login.
     *   - databasePassword: The raw password for database server login.
     */
    public function getDatabaseInfo(): array
    {
        return [
            'databaseServer' => $this->databaseServer,
            'databaseName' => $this->databaseName,
            'databaseUsername' => $this->databaseUsername,
            'databasePassword' => $this->databasePassword,
        ];
    }

    /**
     * Return the database name, if set. Or null, if not set.
     *
     * @return string|null
     */
    public function getDatabaseName(): ?string
    {
        return $this->databaseName;
    }

    /**
     * Check if all database related variables are set.
     *
     * @return boolean
     */
    public function hasDatabaseInfo(): bool
    {
        // Note: presume all attributes are only settible by
        // the method, which only accept string values.
        return !empty($this->databaseServer)
            && !empty($this->databaseName)
            && !empty($this->databaseUsername)
            && !is_null($this->databasePassword);
    }

    /**
     * Validate database related variables.
     *
     * TODO: use exception raise instead of boolean return.
     *
     * @return boolean
     */
    public function validateDatbaseInfo(): bool
    {
        // Check config values for ' " \ / chars which will cause errors in config.php
        $pattern = '/[\'"\/\\\\]/';
        if (
            preg_match($pattern, $this->databaseServer) == true
            || preg_match($pattern, $this->databaseName) == true
            || preg_match($pattern, $this->databaseUsername) == true
        ) {
            return false;
        }
        return true;
    }

    /**
     * Set the locale code for the installation.
     *
     * @param boolean $flag
     *   True if wanted to install demo data. False if not.
     *
     * @return self
     */
    public function setLocale(string $locale)
    {
        $this->locale = $locale;
        return $this;
    }

    /**
     * Get the locale code for the installation.
     *
     * @return string
     */
    public function getLocale(): string
    {
        return $this->locale;
    }
}
