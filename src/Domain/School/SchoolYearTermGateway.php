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

namespace Gibbon\Domain\School;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * School Year Term Gateway
 *
 * @version v17
 * @since   v17
 */
class SchoolYearTermGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonSchoolYearTerm';

    public function querySchoolYearTerms(QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonSchoolYearTerm.gibbonSchoolYearTermID',
                'gibbonSchoolYear.gibbonSchoolYearID',
                'gibbonSchoolYearTerm.name',
                'gibbonSchoolYearTerm.nameShort',
                'gibbonSchoolYearTerm.sequenceNumber',
                'gibbonSchoolYear.sequenceNumber AS schoolYearSequence',
                'gibbonSchoolYearTerm.firstDay',
                'gibbonSchoolYearTerm.lastDay',
                'gibbonSchoolYear.name AS schoolYearName',
                "(CASE WHEN NOW() BETWEEN gibbonSchoolYearTerm.firstDay AND gibbonSchoolYearTerm.lastDay THEN 'Current' ELSE '' END) as status"
            ])
            ->innerJoin('gibbonSchoolYear', 'gibbonSchoolYear.gibbonSchoolYearID=gibbonSchoolYearTerm.gibbonSchoolYearID');

        return $this->runQuery($query, $criteria);
    }
}
