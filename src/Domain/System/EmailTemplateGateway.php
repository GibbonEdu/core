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
                'gibbonEmailTemplateID', 'templateName', 'moduleName', 'gibbonModule.type as moduleType',
            ])
            ->from($this->getTableName())
            ->innerJoin('gibbonModule', 'gibbonModule.name=gibbonEmailTemplate.moduleName')
            ->where("gibbonModule.active='Y'");

        return $this->runQuery($query, $criteria);
    }
}
