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
 * Email Template Gateway
 *
 * @version v21
 * @since   v21
 */
class EmailTemplateGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonEmailTemplate';
    private static $primaryKey = 'gibbonEmailTemplateID';

    private static $searchableColumns = [];

    public function queryEmailTemplates(QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->cols([ 
                'gibbonEmailTemplateID', 'gibbonEmailTemplate.type', 'templateType', 'templateName', 'moduleName', 'gibbonModule.type as moduleType',
            ])
            ->from($this->getTableName())
            ->innerJoin('gibbonModule', 'gibbonModule.name=gibbonEmailTemplate.moduleName')
            ->where("gibbonModule.active='Y'");

        return $this->runQuery($query, $criteria);
    }

    public function selectTemplatesByModule($moduleName, $templateType = null)
    {
        $query = $this
            ->newSelect()
            ->cols([ 
                'gibbonEmailTemplateID', 'templateType', 'templateName',
            ])
            ->from($this->getTableName())
            ->where('gibbonEmailTemplate.moduleName=:moduleName')
            ->bindValue('moduleName', $moduleName);

        if (!empty($templateType)) {
            $query->where('gibbonEmailTemplate.templateType LIKE :templateType')
                  ->bindValue('templateType', $templateType);
        }

        return $this->runSelect($query);
    }

    public function selectAvailableTemplatesByType($moduleName, $templateType)
    {
        $query = $this
            ->newSelect()
            ->cols([ 
                'gibbonEmailTemplateID as value', 'templateName as name',
            ])
            ->from($this->getTableName())
            ->where('gibbonEmailTemplate.moduleName=:moduleName')
            ->bindValue('moduleName', $moduleName)
            ->where('gibbonEmailTemplate.templateType LIKE :templateType')
            ->bindValue('templateType', $templateType);

        return $this->runSelect($query);
    }
}
