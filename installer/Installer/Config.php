<?php

namespace Gibbon\Install;

class Config
{
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
     * Set if the demo data should be installed along
     *
     * @param boolean $flag
     *   True if wanted to install demo data. False if not.
     *
     * @return self
     */
    public function setFlagDemoData(bool $flag)
    {
        $this->flagDemoData = $flag;
    }

    /**
     * Check if the demo data flag is manually set.
     *
     * @return boolean
     */
    public function hasFlagDemoData()
    {
        return $this->flagDemoData !== null;
    }

    /**
     * Get the current demo data installation flag. Default false.
     *
     * @return boolean|null
     */
    public function getFlagDemoData()
    {
        return $this->flagDemoData;
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
