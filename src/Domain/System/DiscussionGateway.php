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

use Gibbon\Domain\QueryableGateway;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\Traits\TableAware;

/**
 * Discussion Gateway
 *
 * @version v19
 * @since   v19
 */
class DiscussionGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonDiscussion';
    private static $primaryKey = 'gibbonDiscussionID';

    public function selectDiscussionByContext($foreignTable, $foreignTableID, $type = null, $order = "ASC")
    {
        $order = ($order == 'ASC' || $order == 'DESC') ? $order : 'ASC';

        $query = $this
            ->newSelect()
            ->cols(['gibbonDiscussion.*', 'gibbonPerson.title', 'gibbonPerson.surname', 'gibbonPerson.preferredName', 'gibbonPerson.image_240', 'gibbonPerson.username', 'gibbonPerson.email'])
            ->from($this->getTableName())
            ->innerJoin('gibbonPerson', 'gibbonDiscussion.gibbonPersonID=gibbonPerson.gibbonPersonID')
            ->where('gibbonDiscussion.foreignTable = :foreignTable')
            ->bindValue('foreignTable', $foreignTable)
            ->where('gibbonDiscussion.foreignTableID = :foreignTableID')
            ->bindValue('foreignTableID', $foreignTableID)
            ->orderBy(['gibbonDiscussion.timestamp '.$order]);

        if (!empty($type)) {
            $query->where('gibbonDiscussion.type = :type')
                ->bindValue('type', $type);
        }

        return $this->runSelect($query);
    }
}
