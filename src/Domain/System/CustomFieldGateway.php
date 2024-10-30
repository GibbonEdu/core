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
                'gibbonCustomFieldID', 'context', 'heading', 'name', 'type', 'active', 'activePersonStudent', 'activePersonParent', 'activePersonStaff', 'activePersonOther', 'required'
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
            ->where("active='Y'");

        if (is_array($context)) {
            $context = implode(',', $context);
            $query->where('FIND_IN_SET(context, :context)', ['context' => $context]);
        } else {
            $query->where('context=:context', ['context' => $context]);
        } 

        if (stripos($context, 'User') !== false) {
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

        $query->orderBy(["(context = 'User') DESC", 'context', 'sequenceNumber', 'name']);

        return $this->runSelect($query);
    }

    public function selectCustomFieldsWithFileUpload()
    {
        $sql = "SELECT context as groupBy, gibbonCustomFieldID as value, name FROM gibbonCustomField WHERE (context='User' OR context='Staff' OR context='Individual Needs' OR context='Medical Form') AND (type = 'file' OR type = 'image') ORDER BY context = 'User' DESC, context, sequenceNumber, name";

        return $this->db()->select($sql);
    }

    public function selectCustomFieldsContexts()
    {
        $sql = "SELECT context FROM gibbonCustomField GROUP BY context ORDER BY context";

        return $this->db()->select($sql);
    }

    public function getCustomFieldDataByUser($gibbonCustomFieldID, $gibbonPersonID)
    {
        $customField = $this->getByID($gibbonCustomFieldID, ['context']);

        if (empty($customField)) {
            return '';
        }
        
        switch ($customField['context']) {
            case 'Staff':
                $sql = "SELECT gibbonStaff.fields FROM gibbonPerson JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID";
                break;

            case 'Individual Needs':
                $sql = "SELECT gibbonIN.fields FROM gibbonPerson JOIN gibbonIN ON (gibbonIN.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID";
                break;

            case 'Medical Form':
                $sql = "SELECT gibbonPersonMedical.fields FROM gibbonPerson JOIN gibbonPersonMedical ON (gibbonPersonMedical.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID";
                break;

            default:
            case 'User':
                $sql = "SELECT gibbonPerson.fields FROM gibbonPerson WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID";
                break;
        }
        
        $record = $this->db()->select($sql, ['gibbonPersonID' => $gibbonPersonID])->fetch();
        $fields = json_decode($record['fields'] ?? '', true);

        return !empty($fields) ? $fields : [];
    }

    public function updateCustomFieldDataByUser($gibbonCustomFieldID, $gibbonPersonID, $value)
    {
        $customField = $this->getByID($gibbonCustomFieldID, ['context']);

        if (empty($customField)) {
            return '';
        }
        
        switch ($customField['context']) {
            case 'Staff':
                $sql = "INSERT INTO gibbonStaff (`gibbonPersonID`, `fields`) VALUES (:gibbonPersonID, :fields) ON DUPLICATE KEY UPDATE fields=:fields";
                break;

            case 'Individual Needs':
                $sql = "INSERT INTO gibbonIN (`gibbonPersonID`, `fields`) VALUES (:gibbonPersonID, :fields) ON DUPLICATE KEY UPDATE fields=:fields";
                break;

            case 'Medical Form':
                $gibbonPersonMedicalID = $this->db()->selectOne("SELECT gibbonPersonMedicalID FROM gibbonPersonMedical WHERE gibbonPersonID=:gibbonPersonID", ['gibbonPersonID' => $gibbonPersonID]);

                if (!empty($gibbonPersonMedicalID)) {
                    $sql = "UPDATE gibbonPersonMedical SET gibbonPersonMedical.fields=:fields WHERE gibbonPersonMedical.gibbonPersonID=:gibbonPersonID";
                } else {
                    $sql = "INSERT INTO gibbonPersonMedical (`gibbonPersonID`, `fields`) VALUES (:gibbonPersonID, :fields)";
                }
                
                break;

            default:
            case 'User':
                $sql = "UPDATE gibbonPerson SET gibbonPerson.fields=:fields WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID";
                break;
        }
        
        return $this->db()->statement($sql, ['gibbonPersonID' => $gibbonPersonID, 'fields' => json_encode($value)]);
    }
}
