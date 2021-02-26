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

namespace Gibbon\Domain\System;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * @version v16
 * @since   v16
 */
class CustomFieldGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonCustomField';
    private static $primaryKey = 'gibbonCustomFieldID';

    private static $searchableColumns = ['name'];
    
    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryCustomFields(QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonCustomFieldID', 'context', 'name', 'type', 'active', 'activePersonStudent', 'activePersonParent', 'activePersonStaff', 'activePersonOther'
            ]);
        
        $criteria->addFilterRules([
            'context' => function ($query, $context) {
                return $query
                    ->where('gibbonCustomField.context = :context')
                    ->bindValue('context', ucwords($context));
            },
            'active' => function ($query, $active) {
                return $query
                    ->where('gibbonCustomField.active = :active')
                    ->bindValue('active', ucfirst($active));
            },
            'role' => function ($query, $roleCategory) {
                $field = 'activePersonStudent';
                switch ($roleCategory) {
                    case 'student': $field = 'activePersonStudent'; break;
                    case 'parent':  $field = 'activePersonParent'; break;
                    case 'staff':   $field = 'activePersonStaff'; break;
                    case 'other':   $field = 'activePersonOther'; break;
                }
                return $query->where('gibbonCustomField.`'.$field.'` = 1');
            },
        ]);

        return $this->runQuery($query, $criteria);
    }
}
