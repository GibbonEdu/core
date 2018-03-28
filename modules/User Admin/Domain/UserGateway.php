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

namespace Gibbon\UserAdmin\Domain;

use Gibbon\Domain\Gateway;

/**
 * User Gateway
 *
 * Provides a data access layer for the gibbonPerson table.
 *
 * @version v16
 * @since   v16
 */
class UserGateway extends Gateway
{
    protected static $tableName = 'gibbonPerson';
    protected static $primaryKey = 'gibbonPersonID';

    public function queryAllUsers($filters)
    {
        $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonPerson.surname, gibbonPerson.preferredName, gibbonPerson.username, 
                gibbonPerson.image_240, gibbonPerson.status, CONCAT(gibbonPerson.surname, ', ', gibbonPerson.preferredName) as fullName, gibbonRole.name as primaryRole 
                FROM gibbonPerson 
                LEFT JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID)";

        $filters->defineFilter('role:student',  __('Role').': '.__('Student'),      "gibbonRole.category = 'Student'")
                ->defineFilter('role:parent',   __('Role').': '.__('Parent'),       "gibbonRole.category = 'Parent'")
                ->defineFilter('role:staff',    __('Role').': '.__('Staff'),        "gibbonRole.category = 'Staff'")
                ->defineFilter('is:full',       __('Status').': '.__('Full'),       "gibbonPerson.status = 'Full'")
                ->defineFilter('is:left',       __('Status').': '.__('Left'),       "gibbonPerson.status = 'Left'")
                ->defineFilter('is:expected',   __('Status').': '.__('Expected'),   "gibbonPerson.status = 'Expected'")
                ->defineFilter('date:starting', __('Before Start Date'),            "(dateStart IS NOT NULL AND dateStart >= :today)", ['today' => date('Y-m-d')])
                ->defineFilter('date:ended',    __('Past End Date'),                "(dateEnd IS NOT NULL AND dateEnd <= :today)", ['today' => date('Y-m-d')]);

        return $this->doFilteredQuery($filters, $sql);
    }
}