<?php

namespace Gibbon\Install;

use Gibbon\Install\Config;
use Twig\Environment;

class Installer
{

    /**
     * Twig template engine to use for config file rendering.
     *
     * @var \Twig\Environment
     */
    protected $templateEngine;

    /**
     * A PDO connection to database server.
     *
     * @var \PDO
     */
    protected $connection;

    /**
     * Constructor
     *
     * @param string      $installPath     The system path for installing Gibbon.
     * @param Environment $templateEngine  The template engine to generate config file from.
     */
    public function __construct(
        Environment $templateEngine
    ) {
        $this->templateEngine = $templateEngine;
    }

    /**
     * Generate configuration file from twig template.
     *
     * @param Config $config    The config object to generate file from.
     * @param string $path      The full path (includes filename) to generate.
     * @param string $template  The template file to use.
     *
     * @return self
     */
    public function createConfigFile(
        Context $context,
        Config $config,
        string $template = 'installer/config.twig.html'
    )
    {
        $contents = $this->templateEngine->render(
            $template,
            static::processConfigVars($config->getVars())
        );

        // Write config contents
        $fp = fopen($context->getConfigPath(), 'wb');
        fwrite($fp, $contents);
        fclose($fp);

        if (!file_exists($context->getConfigPath())) { //Something went wrong, config.php could not be created.
            throw new \Exception(__('../config.php could not be created.'));
        }
        return $this;
    }

    /**
     * Set the internal connection for database operations.
     *
     * @param \PDO $connection
     *
     * @return self
     */
    public function setConnection(\PDO $connection): Installer
    {
        $this->connection = $connection;
        return $this;
    }

    /**
     * Create a user from data in the given assoc array.
     *
     * @param array $user
     *
     * @return bool  True on success or fail on failure.
     *
     * @throws \PDOException On error if PDO::ERRMODE_EXCEPTION option is set to true
     *                       in the instance's \PDO connection.
     */
    public function createUser(array $user): bool
    {
        // TODO: add some default values to $user in case any field(s) is / are missed.
        $statement = $this->connection->prepare('INSERT INTO gibbonPerson SET
            gibbonPersonID=1,
            title=:title,
            surname=:surname,
            firstName=:firstName,
            preferredName=:preferredName,
            officialName=:officialName,
            username=:username,
            password="",
            passwordStrong=:passwordStrong,
            passwordStrongSalt=:passwordStrongSalt,
            status=:status,
            canLogin=:canLogin,
            passwordForceReset=:passwordForceReset,
            gibbonRoleIDPrimary=:gibbonRoleIDPrimary,
            gibbonRoleIDAll=:gibbonRoleIDAll,
            email=:email'
        );
        return $statement->execute($user);
    }

    /**
     * Set a user of given gibbonPersonID as staff.
     *
     * @param int $gibbonPersonID  The ID of the user.
     * @param string $type         Optional. The type of user. Default 'Teaching'.
     */
    public function setPersonAsStaff(int $gibbonPersonID, string $type = 'Teaching')
    {
        $statement = $this->connection->prepare('INSERT INTO gibbonStaff SET gibbonPersonID=:gibbonPersonID, type=:type');
        return $statement->execute([
            'gibbonPersonID' => $gibbonPersonID,
            'type' => $type,
        ]);
    }

    /**
     * Process config variables into string literals stored in string.
     *
     * @param array variables
     *      An array of config variables to be passed into config
     *      file template.
     *
     * @return array
     *      The variables forced to be string type and properly quoted.
     */
    public static function processConfigVars(array $variables)
    {
        $variables += [
            'databaseServer' => '',
            'databaseUsername' => '',
            'databasePassword' => '',
            'databaseName' => '',
            'guid' => '',
        ];
        return array_map(function ($value) {
            return var_export((string) $value, true); // force render into string literals
        }, $variables);
    }
}
