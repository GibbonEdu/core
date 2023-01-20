<?php

namespace Gibbon\Install;

use Gibbon\Contracts\Database\Connection;
use Gibbon\Database\MySqlConnector;
use Gibbon\Install\Config;
use Gibbon\Services\Format;
use Twig\Environment;

/**
 * Gibbon installer object / method collection. For writing installer
 * of Gibbon for different situation (e.g. web, cli).
 *
 * @version v23
 * @since   v23
 *
 */
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
     * @var \Gibbon\Contracts\Database\Connection $connection
     */
    protected $connection;

    /**
     * Constructor
     *
     * @version v23
     * @since   v23
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
     * Generate configuration file from twig template.
     *
     * @version v23
     * @since   v23
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
     * @version v23
     * @since   v23
     *
     * @param \Gibbon\Contracts\Database\Connection $connection
     *
     * @return self
     */
    public function setConnection(Connection $connection): Installer
    {
        $this->connection = $connection;
        return $this;
    }

    /**
     * Get the internal connection for database operations.
     *
     * @version v23
     * @since   v23
     *
     * @param \Gibbon\Contracts\Database\Connection $connection
     *
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * Get the internal connection for database operations.
     *
     * @version v23
     * @since   v23
     *
     * @return \PDO
     *
     * @throws \Exception If internal connection is not set.
     */
    public function getPDO(): \PDO
    {
        if (!isset($this->connection)) {
            throw new \Exception('Connection is not provided to the installer, yet.');
        }
        return $this->connection->getConnection();
    }

    /**
     * Create a user from data in the given assoc array.
     *
     * @version v23
     * @since   v23
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
        $statement = $this->getPDO()->prepare('INSERT INTO gibbonPerson SET
            gibbonPersonID=1,
            title=:title,
            surname=:surname,
            firstName=:firstName,
            preferredName=:preferredName,
            officialName=:officialName,
            username=:username,
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
     * @version v23
     * @since   v23
     *
     * @param int $gibbonPersonID  The ID of the user.
     * @param string $type         Optional. The type of user. Default 'Teaching'.
     */
    public function setPersonAsStaff(int $gibbonPersonID, string $type = 'Teaching')
    {
        $statement = $this->getPDO()->prepare('INSERT INTO gibbonStaff SET gibbonPersonID=:gibbonPersonID, type=:type');
        return $statement->execute([
            'gibbonPersonID' => $gibbonPersonID,
            'type' => $type,
        ]);
    }

    /**
     * Set a certain setting to the value.
     *
     * @version v23
     * @since   v23
     *
     * @param string $name             The name of the setting.
     * @param string $value            The value of the setting.
     * @param string $scope            Optional. The scope of the setting. Default 'System'.
     * @param boolean $throw_on_error  Throw exception when encountered one in database query. Default false.
     *
     * @return boolean  True on success, or false on failure.
     */
    public function setSetting(string $name, string $value, string $scope = 'System', bool $throw_on_error=false): bool {
        error_log("Installer: set {$name} in {$scope} to {$value}");

        if ($this->getSetting($name, $scope) === false) {
            // Settings not found.
            error_log("Installer: unable to find {$name} in {$scope}");
            return false;
        }
        if ($throw_on_error) {
            $statement = $this->getPDO()->prepare('UPDATE gibbonSetting SET value=:value WHERE scope=:scope AND name=:name');
            return $statement->execute([':scope' => $scope, ':name' => $name, ':value' => $value]);
        }
        try {
            $statement = $this->getPDO()->prepare('UPDATE gibbonSetting SET value=:value WHERE scope=:scope AND name=:name');
            return $statement->execute([':scope' => $scope, ':name' => $name, ':value' => $value]);
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * Get a certain setting by name.
     *
     * @version v23
     * @since   v23
     *
     * @param string $name         The name of the setting.
     * @param string $scope        Optional. The scope of the setting. Default 'System'.
     * @param boolean $return_row  Return whole setting data row instead of just value. Default false.
     *
     * @return string|array|false The value of the setting. Or an array of data row for the setting.
     *                            Or false if setting not found.
     */
    public function getSetting(string $name, string $scope = 'System', bool $return_row = false)
    {
        $statement = $this->getPDO()->prepare('SELECT * FROM gibbonSetting WHERE scope=:scope AND name=:name');
        $statement->execute(['scope' => $scope, 'name' => $name]);
        if ($statement->rowCount() != 1) {
            return false;
        }
        $row = $statement->fetch();
        return $return_row ? $row : $row['value'];
    }

    /**
     * Get the Gibbon person record by gibbonPersonID.
     *
     * @version v23
     * @since   v23
     *
     * @param int|string $id  The gibbonPersonID for the person.
     *
     * @return array|null  The row of gibbon person data, or null if there is no data.
     */
    public function getGibbonPerson($id)
    {
        $statement = $this->getPDO()->prepare('SELECT * FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID');
        $statement->execute(['gibbonPersonID' => $id]);
        if ($statement->rowCount() < 1) return null;
        return $statement->fetch();
    }

    /**
     * Get the default locale code from database.
     *
     * @version v23
     * @since   v23
     *
     * @return string The value of the system default locale.
     *                Or 'en_GB' if setting not found.
     */
    public function getDefaultLocale(): string
    {
        $statement = $this->getPDO()->prepare('SELECT code FROM gibboni18n WHERE systemDefault="Y"');
        $statement->execute();
        if ($statement->rowCount() < 1) {
            return 'en_GB';
        }
        $row = $statement->fetch();
        return $row['code'];
    }

    /**
     * Get password policy string messages.
     *
     * @return array
     */
    public function getPasswordPolicy(): array
    {
        $policies = [];

        $alpha = $this->getSetting('passwordPolicyAlpha');
        $numeric = $this->getSetting('passwordPolicyNumeric');
        $punctuation = $this->getSetting('passwordPolicyNonAlphaNumeric');
        $minLength = $this->getSetting('passwordPolicyMinLength');

        if ($alpha === false or $numeric === false or $punctuation === false or $minLength === false) {
            throw new \Exception(__('Internal Error: Password policy setting incorrect.'));
        } elseif ($alpha != 'N' or $numeric != 'N' or $punctuation != 'N' or $minLength >= 0) {
            if ($alpha == 'Y') {
                $policies[] = __('Contain at least one lowercase letter, and one uppercase letter.');
            }
            if ($numeric == 'Y') {
                $policies[] = __('Contain at least one number.');
            }
            if ($punctuation == 'Y') {
                $policies[] = __('Contain at least one non-alphanumeric character (e.g. a punctuation mark or space).');
            }
            if ($minLength >= 0) {
                $policies[] = sprintf(__('Must be at least %1$s characters in length.'), $minLength);
            }
        }
        return $policies;

    }

    /**
     * Process config variables into string literals stored in string.
     *
     * @version v23
     * @since   v23
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

    /**
     * Installer will initialize internal database
     * connection with the provided config data.
     *
     * @param \Gibbon\Install\Config $config
     *
     * @throws \Exception
     *
     * @return self
     */
    public function useConfigConnection(Config $config): Installer
    {
        // Check config values for ' " \ / chars which will cause errors in config.php
        if (!$config->validateDatbaseInfo()) {
            throw new \Exception(__('Your request failed because your inputs were invalid.'));
        }

        // Connect to database by raw config.
        if (!$config->hasDatabaseInfo()) {
            throw new \Exception(__('Your request failed because your inputs were incomplete.'));
        }

        $connection = static::connectByConfig($config);
        if (!$connection instanceof Connection) {
            throw new \Exception(__('Unexpected internal error. PDO is not an instance of Connection, and so the installer cannot proceed.'));
        }

        $this->setConnection($connection);
        return $this;
    }

    /**
     * Run installation according to the given context and config.
     *
     * @param \Gibbon\Install\Context $context  The installation context
     * @param string $defaultLocale             The locale code of system default.
     *
     * @return self
     *
     * @throws \Exception
     */
    public function install(Context $context, string $defaultLocale): Installer
    {
        // Get internal connection.
        $pdo = $this->getPDO();

        // Let's populate the database with the SQL queries from the file.
        $sql = $this->getInstallSql($context);
        $sql = static::removeSqlRemarks($sql);
        $queries = static::splitSql($sql);
        try {
            $this->runQueries($pdo, $queries);
        } catch (\PDOException $e) {
            throw new \Exception(__('Errors occurred in populating the database; empty your database, remove ../config.php and try again.'));
        }

        //Set default language
        try {
            $data = array('code' => $defaultLocale);
            $sql = "UPDATE gibboni18n SET systemDefault='Y' WHERE code=:code";
            $result = $pdo->prepare($sql);
            $result->execute($data);
        } catch (\PDOException $e) {
        }
        try {
            $data = array('code' => $defaultLocale);
            $sql = "UPDATE gibboni18n SET systemDefault='N' WHERE NOT code=:code";
            $result = $pdo->prepare($sql);
            $result->execute($data);
        } catch (\PDOException $e) {
        }

        return $this;
    }

    /**
     * Run demo data installation according to the given context.
     *
     * @param \Gibbon\Install\Context $context  The installation context
     *
     * @return self
     *
     * @throws \Exception
     */
    public function installDemoData(Context $context)
    {
        // Get internal connection.
        $pdo = $this->getPDO();

        // Try to install the demo data, report error but don't stop if any issues
        try {
            $sql = $this->getDemoSql($context);
            $sql = static::removeSqlRemarks($sql);
            $queries = static::splitSql($sql);
        } catch (\Exception $e) {
            echo Format::alert($e->getMessage() . ' ' . __('We will continue without demo data.'), 'warning');
        }

        try {
            $this->runQueries($pdo, $queries);
        } catch (\PDOException $e) {
            return false;
        }

        return true;
    }

    /**
     * Render the Gibbon services URL.
     *
     * @version v23
     * @since   v23
     *
     * @param string $service
     *    The unprefixed service path (after "/services/" and without .php).
     * @param array $details
     *    An array of all the variables to call the service for.
     *
     * @return string The URL string.
     */
    public static function gibbonServiceURL(string $service, array $details): string
    {
        return 'https://gibbonedu.org/services/' . $service . '.php?' . http_build_query($details);
    }

    /**
     * Remove remarks (e.g. comments) from SQL string.
     *
     * @version v23
     * @since   v23
     *
     * @param string &$sql  The SQL string to process. Will be emptied
     *                      after run to save memory.
     *
     * @return string The resulted SQL string.
     */
    public static function removeSqlRemarks(string &$sql): string
    {
        $lines = explode("\n", $sql);

        // save memory.
        $sql = '';

        $linecount = count($lines);
        $output = '';

        for ($i = 0; $i < $linecount; ++$i) {
            if (($i != ($linecount - 1)) || (strlen($lines[$i]) > 0)) {
                if (isset($lines[$i][0]) && $lines[$i][0] != '#' && $lines[$i][0] != '-') {
                    $output .= $lines[$i]."\n";
                } else {
                    $output .= "\n";
                }
                // Trading a bit of speed for lower mem. use here.
                $lines[$i] = '';
            }
        }

        return $output;
    }

    /**
     * Create database connection from config.
     *
     * @param \Gibbon\Install\Config $config
     *
     * @return \Gibbon\Contracts\Database\Connection
     */
    public static function connectByConfig(Config $config): Connection
    {
        // Establish db connection without database name
        try {
            $mysqlConnector = new MySqlConnector();
            $dbConfig = $config->getDatabaseInfo();
            if (isset($dbConfig['databaseName'])) unset($dbConfig['databaseName']);
            $connection = $mysqlConnector->connect($dbConfig, true);
        } catch (\Exception $e) {
            throw new \Exception(
                sprintf(__('A database connection could not be established. Please %1$stry again%2$s.'), "<a href='./install.php'>", '</a>') . '<br>' .
                __('Error details: {error_message}', ['error_message' => $e->getMessage()])
            );
        }

        // Check if the database exists, create if not
        try {
            $pdo = $connection->getConnection();
            if (!static::databaseExists($pdo, $config->getDatabaseName())) {
                static::createDatabase($pdo, $config->getDatabaseName());
            }
        } catch (\Exception $e) {
            throw new \Exception(
                __('Database "{name}" not found and unable to create one. Please manually create the database, or provide account that can create it.', [
                    'name' => $config->getDatabaseName(),
                ]) . '<br>' .
                __('Error details: {error_message}', ['error_message' => $e->getMessage()])
            );
        }

        $mysqlConnector->useDatabase($connection, $config->getDatabaseName());
        return $connection;
    }

    /**
     * Check if a database exists.
     *
     * @param string $name The database name to check.
     *
     * @return bool True if the database exists. False otherwise.
     */
    protected static function databaseExists(\PDO $pdo, string $name): bool
    {
        $stmt = $pdo->prepare('SHOW DATABASES LIKE :name');
        $stmt->execute([':name' => $name]);
        if (!$stmt->execute([':name' => $name])) {
            throw new \Exception('Internal error. Unable to check if database exists.');
        }
        return $stmt->rowCount() === 1;
    }

    /**
     * Create the database by the database name.
     *
     * @param string $name The database name to create.
     *
     * @return int|false The response for the exec for creating database.
     *
     * @throws \PDOException
     */
    protected static function createDatabase(\PDO $pdo, string $name)
    {
        $quoted_name = static::quoteDatabaseName($pdo, $name);
        return $pdo->exec("CREATE DATABASE `{$quoted_name}`");
    }

    /**
     * Quote the database name string with backtick
     * and sanitize the string.
     *
     * @param string $name
     * @return string
     */
    private static function quoteDatabaseName(\PDO $pdo, string $name): string
    {
        $quoted_name = $pdo->quote($name, \PDO::PARAM_STR);
        // convert single quote to backtick to return.
        return preg_replace('/^\'(.+?)\'$/', '$1', $quoted_name);
    }

    /**
     * Get the content of gibbon.sql
     *
     * @param \Gibbon\Install\Context $context
     *
     * @return string The SQL text.
     */
    public function getInstallSql(Context $context): string
    {
        // Let's read the SQL file for basic schema and data creation.
        if (!file_exists($context->getPath('gibbon.sql'))) {
            throw new \Exception(__('../gibbon.sql does not exist, and so the installer cannot proceed.'));
        }
        if (($sql = @fread(@fopen($context->getPath('gibbon.sql'), 'r'), @filesize($context->getPath('gibbon.sql')))) === false) {
            throw new \Exception(__('Unable to read ../gibbon.sql, and so the installer cannot proceed.'));
        }
        return $sql;
    }

    /**
     * Get the content of gibbon.sql
     *
     * @param \Gibbon\Install\Context $context
     *
     * @return string The SQL text.
     */
    public function getDemoSql(Context $context): string
    {
        // Let's read the SQL file for basic schema and data creation.
        if (!file_exists($context->getPath('gibbon_demo.sql'))) {
            throw new \Exception(__('../gibbon_demo.sql does not exist.'));
        }
        if (($sql = @fread(@fopen($context->getPath('gibbon_demo.sql'), 'r'), @filesize($context->getPath('gibbon_demo.sql')))) === false) {
            throw new \Exception(__('Unable to read ../gibbon_demo.sql.'));
        }
        return $sql;
    }

    /**
     * Execute queries providede by an iterable.
     *
     * @param \PDO $connection
     * @param iterable $queries
     *
     * @return integer Number of queries run.
     *
     * @throws \PDOException
     */
    public function runQueries(\PDO $connection, iterable $queries): int
    {
        $i = 1;
        foreach ($queries as $query) {
            ++$i;
            $connection->query($query);
        }
        return $i;
    }

    /**
     * Split a very long SQL string (with multiple statements) into iterable
     * of the individual SQL statements. Based on split_sql_file() of previous
     * Gibbon versions.
     *
     * Expects trim() to have already been run on query string $sql.
     *
     * @version v23
     * @since   v23
     *
     * @param string &$sql        The SQL string to process. Will be emptied
     *                            after run to save memory.
     * @param string $terminator  The terminator string at the end of each statement. Default ';'
     *
     * @return iterable
     */
    public static function splitSql(string &$sql, string $terminator = ';'): iterable
    {
        // Split up our string into "possible" SQL statements.
        $tokens = explode($terminator, $sql);

        // save memory.
        $sql = '';

        /**
         * @var string[]
         */
        $output = [];

        // we don't actually care about the matches preg gives us.
        $matches = array();

        // this is faster than calling count($oktens) every time thru the loop.
        $token_count = count($tokens);
        for ($i = 0; $i < $token_count; ++$i) {
            // Don't wanna add an empty string as the last thing in the array.
            if (($i != ($token_count - 1)) || (strlen($tokens[$i] > 0))) {
                // This is the total number of single quotes in the token.
                $total_quotes = preg_match_all("/'/", $tokens[$i], $matches);
                // Counts single quotes that are preceded by an odd number of backslashes,
                // which means they're escaped quotes.
                $escaped_quotes = preg_match_all("/(?<!\\\\)(\\\\\\\\)*\\\\'/", $tokens[$i], $matches);

                $unescaped_quotes = $total_quotes - $escaped_quotes;

                // If the number of unescaped quotes is even, then the delimiter did NOT occur inside a string literal.
                if (($unescaped_quotes % 2) == 0) {
                    // It's a complete sql statement.
                    $output[] = $tokens[$i];
                    // save memory.
                    $tokens[$i] = '';
                } else {
                    // incomplete sql statement. keep adding tokens until we have a complete one.
                    // $temp will hold what we have so far.
                    $temp = $tokens[$i].$terminator;
                    // save memory..
                    $tokens[$i] = '';

                    // Do we have a complete statement yet?
                    $complete_stmt = false;

                    for ($j = $i + 1; (!$complete_stmt && ($j < $token_count)); ++$j) {
                        // This is the total number of single quotes in the token.
                        $total_quotes = preg_match_all("/'/", $tokens[$j], $matches);
                        // Counts single quotes that are preceded by an odd number of backslashes,
                        // which means they're escaped quotes.
                        $escaped_quotes = preg_match_all("/(?<!\\\\)(\\\\\\\\)*\\\\'/", $tokens[$j], $matches);

                        $unescaped_quotes = $total_quotes - $escaped_quotes;

                        if (($unescaped_quotes % 2) == 1) {
                            // odd number of unescaped quotes. In combination with the previous incomplete
                        // statement(s), we now have a complete statement. (2 odds always make an even)
                        $output[] = $temp.$tokens[$j];

                        // save memory.
                        $tokens[$j] = '';
                            $temp = '';

                        // exit the loop.
                        $complete_stmt = true;
                        // make sure the outer loop continues at the right point.
                        $i = $j;
                        } else {
                            // even number of unescaped quotes. We still don't have a complete statement.
                        // (1 odd and 1 even always make an odd)
                        $temp .= $tokens[$j].$terminator;
                        // save memory.
                        $tokens[$j] = '';
                        }
                    } // for..
                } // else
            }
        }

        return $output;
    }
}
