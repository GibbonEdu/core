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

namespace Gibbon\Domain\School;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * School Year Special Day Gateway
 *
 * @version v25
 * @since   v25
 */
class DaysOfWeekGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonDaysOfWeek';
    private static $primaryKey = 'gibbonDaysOfWeekID';

    public function selectSchoolWeekdays()
    {
        $sql = "SELECT * FROM gibbonDaysOfWeek WHERE schoolDay='Y' ORDER BY sequenceNumber";

        return $this->db()->select($sql);
    }

    public function getDayOfWeekByDate($date)
    {
        $data = ['dayOfWeek' => date('l', strtotime($date))];
        $sql = "SELECT * FROM gibbonDaysOfWeek WHERE name=:dayOfWeek";

        return $this->db()->selectOne($sql, $data);
    }

    
}
