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
 *
 * User: craig
 * Date: 27/11/2018
 * Time: 22:22
 */
namespace Gibbon\Services;

use Gibbon\Database\Connection;

class SecurityManager
{
    /**
     * @var Connection|null
     */
    private $connection;

    /**
     * @return Connection|null
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * @param Connection $connection
     * @return SecurityManager
     */
    public function setConnection(Connection $connection): SecurityManager
    {
        $this->connection = $connection;
        return $this;
    }

    /**
     * SecurityManager constructor.
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->setConnection($connection);
    }

    /**
     * @var SettingManager|null
     */
    private $settingManager;

    /**
     * @return SettingManager|null
     */
    public function getSettingManager(): SettingManager
    {
        if (empty($this->settingManager))
            $this->settingManager = new SettingManager($this->getConnection());
        return $this->settingManager;
    }

    /**
     * @param null $ip
     * @return bool
     */
    public function isIPToBeIgnored($ip = null): bool
    {
        if (is_null($ip))
            $ip = $_SERVER['REMOTE_ADDR'];
        foreach(array_merge(['127.0.0.1'],$this->getSettingManager()->getSettingByScopeAsArray('System', 'ipWhiteList')) as $range)
        {
            if (strpos($ip, $range) === 0)
                return true;
            if ($this->ipInRange($ip, $range))
                return true;
        }
        return false;
    }

    /**
     * @param $ip
     * @param $range
     * @return bool
     */
    private function ipInRange( $ip, $range ) {
        $ipRegex = '#^((25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)(\.|$)){4}$#';
        if ( preg_match($ipRegex, $range) ) {
            $range .= '/32';
        }
        // $range is in IP/CIDR format eg 127.0.0.1/24
        list( $range, $netmask ) = explode( '/', $range, 2 );
        $range_decimal = ip2long( $range );
        $ip_decimal = ip2long( $ip );
        $wildcard_decimal = pow( 2, ( 32 - $netmask ) ) - 1;
        $netmask_decimal = ~ $wildcard_decimal;
        return ( ( $ip_decimal & $netmask_decimal ) == ( $range_decimal & $netmask_decimal ) );
    }
}