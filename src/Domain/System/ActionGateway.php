<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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

namespace Gibbon\Domain\System;

use Gibbon\Contracts\Database\Connection;
use Gibbon\Contracts\Services\Session;
use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * @version v25
 * @since   v16
 */
class ActionGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonAction';
    private static $primaryKey = 'gibbonActionID';

    private static $searchableColumns = ['name'];

    /**
     * Session instance.
     *
     * @var Session
     */
    private $session;

    /**
     * Create a new gateway instance using the supplied database connection.
     *
     * @param Connection $db
     * @param Session    $session
     */
    public function __construct(
        Connection $db,
        Session $session
    )
    {
        parent::__construct($db);
        $this->session = $session;
    }

    /**
     * Query for actions.
     *
     * @version  v16
     * @since    v16
     *
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryActions(QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                '*'
            ]);

        return $this->runQuery($query, $criteria);
    }

    /**
     * Get fast finder actions.
     *
     * @version  v16
     * @since    v16
     *
     * @param int $gibbonRoleIDCurrent
     *
     * @return array Actions
     */
    public function getFastFinderActions($gibbonRoleIDCurrent)
    {
        $data = ['gibbonRoleID' => $gibbonRoleIDCurrent];
        $sql = "SELECT DISTINCT concat(gibbonModule.name, '/', gibbonAction.entryURL) AS id, SUBSTRING_INDEX(gibbonAction.name, '_', 1) AS name, gibbonModule.type, gibbonModule.name AS module
                FROM gibbonModule
                JOIN gibbonAction ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID)
                JOIN gibbonPermission ON (gibbonPermission.gibbonActionID=gibbonAction.gibbonActionID)
                WHERE active='Y'
                AND menuShow='Y'
                AND gibbonPermission.gibbonRoleID=:gibbonRoleID
                ORDER BY name";

        $actions = $this->db()->select($sql, $data)->fetchAll();

        foreach ($actions as $index => $action) {
            $actions[$index]['name'] = __($action['name']);
        }

        $actions[] = ['name' => ''];

        return $actions;
    }

    /**
     * Insert permission by action.
     *
     * @version  v16
     * @since    v16
     *
     * @param int $gibbonActionID
     * @param int $gibbonRoleID
     *
     * @return int Last insert ID.
     */
    public function insertPermissionByAction($gibbonActionID, $gibbonRoleID)
    {
        $data = ['gibbonActionID' => $gibbonActionID, 'gibbonRoleID' => $gibbonRoleID];
        $sql = "INSERT INTO gibbonPermission SET gibbonActionID=:gibbonActionID, gibbonRoleID=:gibbonRoleID";

        return $this->db()->insert($sql, $data);
    }

    /**
     * Delete permission by action.
     *
     * @version  v16
     * @since    v16
     *
     * @param int $gibbonActionID
     *
     * @return int Affected row.
     */
    public function deletePermissionByAction($gibbonActionID)
    {
        $data = array('gibbonActionID' => $gibbonActionID);
        $sql = "DELETE FROM gibbonPermission WHERE gibbonActionID=:gibbonActionID";

        return $this->db()->delete($sql, $data);
    }

    /**
     * Looks at the grouped actions accessible to the user in the current
     * module and returns the highest.
     *
     * @since   v25
     * @version v25
     *
     * @param string $address  Part of the address string to search
     *
     * @return string|false  The name of the action, or false if none is found.
     */
    public function getHighestGrouped(string $address)
    {
        if (empty($this->session->get('gibbonRoleIDCurrent'))) {
            return false;
        }

        $sql = 'SELECT
            gibbonAction.name
            FROM
            gibbonAction
            INNER JOIN gibbonModule ON (gibbonModule.gibbonModuleID=gibbonAction.gibbonModuleID)
            INNER JOIN gibbonPermission ON (gibbonAction.gibbonActionID=gibbonPermission.gibbonActionID)
            INNER JOIN gibbonRole ON (gibbonPermission.gibbonRoleID=gibbonRole.gibbonRoleID)
            WHERE
            gibbonAction.URLList LIKE :actionName AND
            gibbonPermission.gibbonRoleID=:gibbonRoleID AND
            gibbonModule.name=:moduleName
            ORDER BY gibbonAction.precedence DESC, gibbonAction.gibbonActionID
        ';

        $result = $this->db()->select($sql, [
            'actionName' => '%'.getActionName($address).'%',
            'gibbonRoleID' => $this->session->get('gibbonRoleIDCurrent'),
            'moduleName' => getModuleName($address),
        ]);

        return $result->isNotEmpty()
            ? $result->fetchColumn()
            : false;
    }
}
