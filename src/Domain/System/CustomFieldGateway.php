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

use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;
use Gibbon\Domain\Traits\TableAware;

/**
 * @version v22
 * @since   v22
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
                'gibbonCustomFieldID', 'context', 'heading', 'name', 'type', 'active', 'activePersonStudent', 'activePersonParent', 'activePersonStaff', 'activePersonOther'
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

    public function selectCustomFields($context, $params = [])
    {
        $query = $this
            ->newSelect()
            ->cols(["(CASE WHEN heading <> '' THEN heading ELSE 'Other Information' END) as groupBy", 'gibbonCustomField.*'])
            ->from('gibbonCustomField')
            ->where("active='Y'")
            ->where('context=:context')
            ->bindValue('context', $context);

        if ($context == 'User') {
            // Handle role category flags as ORs
            $query->where(function ($query) use (&$params) {
                if ($params['student'] ?? false) {
                    $query->orWhere('activePersonStudent=:student', ['student' => $params['student']]);
                }
                if ($params['staff'] ?? false) {
                    $query->orWhere('activePersonStaff=:staff', ['staff' => $params['staff']]);
                }
                if ($params['parent'] ?? false) {
                    $query->orWhere('activePersonParent=:parent', ['parent' => $params['parent']]);
                }
                if ($params['other'] ?? false) {
                    $query->orWhere('activePersonOther=:other', ['other' => $params['other']]);
                }
            });
        }

        // Handle additional flags as ANDs
        if ($params['applicationForm'] ?? false) {
            $query->where('activeApplicationForm=:applicationForm', ['applicationForm' => $params['applicationForm']]);
        }
        if ($params['dataUpdater'] ?? false) {
            $query->where('activeDataUpdater=:dataUpdater', ['dataUpdater' => $params['dataUpdater']]);
        }
        if ($params['publicRegistration'] ?? false) {
            $query->where('activePublicRegistration=:publicRegistration', ['publicRegistration' => $params['publicRegistration']]);
        }
        if ($params['hideHidden'] ?? false) {
            $query->where('hidden=:hidden', ['hidden' => 'N']);
        }

        // Handle getting fields that match or do not match specific headings
        if ($params['withHeading'] ?? false) {
            $headings = is_array($params['withHeading']) ? implode(',', $params['withHeading']) : $params['withHeading'];
            $query->where('FIND_IN_SET(heading, :headings)', ['headings' => $headings]);
        }
        if ($params['withoutHeading'] ?? false) {
            $headings = is_array($params['withoutHeading']) ? implode(',', $params['withoutHeading']) : $params['withoutHeading'];
            $query->where('NOT FIND_IN_SET(heading, :headings)', ['headings' => $headings]);
        }

        $query->orderBy(['sequenceNumber', 'name']);

        return $this->runSelect($query);
    }
}
