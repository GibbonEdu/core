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

namespace Gibbon\Module\Reports\Domain;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

class ReportPrototypeSectionGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonReportPrototypeSection';
    private static $primaryKey = 'gibbonReportPrototypeSectionID';
    private static $searchableColumns = ['gibbonReportPrototypeSection.name'];
    
    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryPrototypes(QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->from($this->getTableName())
            ->cols(['gibbonReportPrototypeSection.gibbonReportPrototypeSectionID', 'name', 'type', 'category', 'templateFile', 'fonts' ]);

        return $this->runQuery($query, $criteria);
    }

    public function selectPrototypeSections($type)
    {
        $data = ['type' => $type];
        $sql = "SELECT category, gibbonReportPrototypeSectionID as value, name, icon
                FROM gibbonReportPrototypeSection 
                WHERE type=:type AND (types LIKE '%Head%' OR types LIKE '%Foot%' OR types LIKE '%Body%')
                ORDER BY type DESC, FIND_IN_SET(category, 'Headers & Footers,Miscellaneous'), category, name";

        return $this->db()->select($sql, $data);
    }

    public function selectPrototypeStylesheets()
    {
        $sql = "SELECT type, templateFile as value, name 
                FROM gibbonReportPrototypeSection 
                WHERE types LIKE '%Stylesheet%'
                ORDER BY type, name";

        return $this->db()->select($sql);
    }

    public function getPrototypeSectionByID($gibbonReportPrototypeSectionID)
    {
        $data = ['gibbonReportPrototypeSectionID' => $gibbonReportPrototypeSectionID];
        $sql = "SELECT *
                FROM gibbonReportPrototypeSection 
                WHERE gibbonReportPrototypeSectionID=:gibbonReportPrototypeSectionID";

        return $this->db()->selectOne($sql, $data);
    }
}
