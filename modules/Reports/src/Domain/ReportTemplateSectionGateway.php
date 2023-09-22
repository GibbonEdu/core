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

class ReportTemplateSectionGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonReportTemplateSection';
    private static $primaryKey = 'gibbonReportTemplateSectionID';
    private static $searchableColumns = ['gibbonReportTemplateSection.name'];
    
    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function querySectionsByType(QueryCriteria $criteria, $gibbonReportTemplateID, $type = '')
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->from($this->getTableName())
            ->cols(['gibbonReportTemplateSection.gibbonReportTemplateSectionID', 'gibbonReportTemplateSection.name', 'gibbonReportTemplateSection.type', 'gibbonReportPrototypeSection.templateFile', 'gibbonReportPrototypeSection.dataSources', 'gibbonReportPrototypeSection.fonts', 'gibbonReportTemplateSection.flags', 'gibbonReportTemplateSection.page', 'gibbonReportTemplateSection.templateParams', 'gibbonReportTemplateSection.config'])
            ->leftJoin('gibbonReportPrototypeSection', 'gibbonReportPrototypeSection.gibbonReportPrototypeSectionID=gibbonReportTemplateSection.gibbonReportPrototypeSectionID')
            ->where('gibbonReportTemplateSection.gibbonReportTemplateID=:gibbonReportTemplateID')
            ->bindValue('gibbonReportTemplateID', $gibbonReportTemplateID);

        if (!empty($type)) {
            $query->where('gibbonReportTemplateSection.type=:type')
                  ->bindValue('type', $type);
        }

        return $this->runQuery($query, $criteria);
    }
}
