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

namespace Gibbon ;

/**
 * @deprecated v16
 * Database Connection Class
 *
 * @version	v13
 * @since	v12
 */
class sqlConnection
{
    /**
     * PDO Object
     */
    protected	$pdo = NULL ;

    /**
     * Connection Success
     */
    private		$success = false ;

    /**
     * Query Success
     */
    private		$querySuccess = false ;

    /**
     * PDOStatement
     */
    private		$result = NULL ;

    /**
     * array
     */
    private		$data = array() ;

    /**
     * String
     */
    private		$query = NULL ;

    /**
     * String
     */
    private		$error = NULL ;

    /**
     * Construct
     *
     * @param	string	error Message
     *
     * @return	Object	PDO Connection
     */
    public function __construct( $message = null )
    {
        // Test for Config file.
        if (file_exists(dirname(__FILE__). '/../../config.php') && filesize(dirname(__FILE__). '/../../config.php') > 0) {
            include dirname(__FILE__). '/../../config.php';
        } else {
            return NULL;
        }

        $databasePort = (isset($databasePort))? $databasePort : null;

        return $this->generateConnection($databaseServer, $databaseName, $databaseUsername, $databasePassword, $databasePort, $message);
    }

    /**
     * generate Connection
     *
     * @param	string	Server Address:port
     * @param	string	Database Name
     * @param	string	User Name
     * @param	string	Password
     * @param	string	error Message
     *
     * @return	Object	PDO Connection
     */
     private function generateConnection($databaseServer, $databaseName, $databaseUsername, $databasePassword, $databasePort = NULL, $message = NULL)
     {
        $this->pdo = NULL;
        $this->success = false;
        try {
            $dns = "mysql:host=$databaseServer;";
            $dns .= (!empty($databasePort))? "port=$databasePort;" : '';
            $dns .= (!empty($databaseName))? "dbname=$databaseName;" : '';
            $dns .= "charset=utf8";

            $this->pdo = new \PDO($dns, $databaseUsername, $databasePassword );
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
            $this->setSQLMode();
            $this->success = true;
        } catch(\PDOException $e) {
            $this->error = $e->getMessage();
            trigger_error(($message !== NULL)? $message : $this->error, E_USER_WARNING);
        }

        return $this;
    }

    /**
     * Get connection. Required for backwards compatibilty in Gibbon.
     *
     * @return	Object PDO COnnection
     */
    public function getConnection()
    {
        return $this->pdo;
    }

    /**
     * Get Connection Success
     *
     * @return	Object PDO COnnection
     */
    public function getSuccess()
    {
        return $this->success;
    }

    /**
     * Get Query Success
     *
     * @return	Object PDO COnnection
     */
    public function getQuerySuccess()
    {
        return $this->querySuccess;
    }

    /**
     * Execute Query
     *
     * @param	array	Data Information
     * @param	string	SQL Query
     * @param	string	Error
     *
     * @return	Object	PDO Result
     */
    public function executeQuery($data, $query, $error = NULL)
    {
        $this->querySuccess = false;
        $this->data = $data;
        $this->query = $query;

        try {
            $this->result = $this->getConnection()->prepare($query);
            $this->result->execute($data);
            $this->querySuccess = true;
        } catch(\PDOException $e) {
            $this->error = $e->getMessage();
            trigger_error(($error !== NULL)? str_replace('{message}', $this->error, $error) : $this->error, E_USER_WARNING);
        }

        return $this->result ;
    }

    /**
     * Get Result
     *
     * @return	Object	PDOStatement
     */
    public function getResult()
    {
        return $this->result ;
    }

    /**
     * Get Version
     *
     * @return	string	Version
     */
    public function getVersion()
    {
        return $this->pdo->query("SELECT VERSION()")->fetchColumn();
    }

    /**
     * Get Collation
     *
     * @return	string	Collation
     */
    public function getCollation()
    {
        return $this->pdo->query("SELECT COLLATION('gibbon')")->fetchColumn();
    }

    /**
     * Install Bypass
     *
     * @param	string	Server Address:port
     * @param	string	Database Name
     * @param	string	User Name
     * @param	string	Password
     * @param	string	error Message
     *
     * @return	Object	PDO Connection
     */
    public function installBypass($databaseServer, $databaseName, $databaseUsername, $databasePassword, $message = null)
    {
        $databaseNameClean = $this->escIdentifier($databaseName);
        $this->success = false;
        $this->pdo = null;
        
        $this->generateConnection($databaseServer, '', $databaseUsername, $databasePassword);

        if (!is_null($this->pdo)) {
            $this->executeQuery(array(), "CREATE DATABASE IF NOT EXISTS {$databaseNameClean} DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_general_ci");

            if ($this->querySuccess) {
                $this->executeQuery(array(), "USE {$databaseNameClean}");
                $this->success = true;
            }
        }
        return $this;
    }

    /**
     * Escape an SQL identifier such as a table or database name with backticks.
     * @param string $value
     * @return string
     */
    public function escIdentifier($value)
    {
        return "`".str_replace("`","``",$value)."`";
    }

    /**
     * Set SQL Mode
     */
    private function setSQLMode()
    {
        $version = $this->getVersion();
        if ($version > '5.7')  //Default for 5.7.x is STRICT_ALL_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ZERO_DATE,NO_ZERO_IN_DATE,NO_AUTO_CREATE_USER
            $result = $this->pdo->prepare("SET SESSION `sql_mode` = ''");
        elseif ($version > '5.6')  // Default for 5.6.x is NO_ENGINE_SUBSTITUTION
            $result = $this->pdo->prepare("SET SESSION `sql_mode` = ''");
        else // Default for < 5.6 is ''
            $result = $this->pdo->prepare("SET SESSION `sql_mode` = ''");
        $result->execute(array());
    }
}

?>
