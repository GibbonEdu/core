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

namespace Gibbon\Domain\Attendance;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * @version v31
 * @since   v31
 */
class AttendanceLogFormGroupGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonAttendanceLogFormGroup';
    private static $primaryKey = 'gibbonAttendanceLogFormGroupID';

    private static $searchableColumns = [''];

    public function getFormGroupAttendanceByDate($gibbonFormGroupID, $date)
    {
        $data = ['gibbonFormGroupID' => $gibbonFormGroupID, 'date' => $date.'%'];
        $sql = 'SELECT * FROM gibbonAttendanceLogFormGroup INNER JOIN gibbonPerson ON gibbonAttendanceLogFormGroup.gibbonPersonIDTaker=gibbonPerson.gibbonPersonID WHERE gibbonFormGroupID=:gibbonFormGroupID AND gibbonAttendanceLogFormGroup.date LIKE :date ORDER BY timestampTaken';

        return $this->db()->select($sql, $data);
    }

    public function selectFormGroupAttendanceByDateRange($dateStart, $dateEnd)
    {
        $data = ['dateStart' => $dateStart, 'dateEnd' => $dateEnd];
        $sql = 'SELECT date, nameShort, gibbonAttendanceLogFormGroup.gibbonFormGroupID, UNIX_TIMESTAMP(timestampTaken) as timestamp, timestampTaken, gibbonPersonIDTaker FROM gibbonAttendanceLogFormGroup JOIN gibbonFormGroup ON (gibbonFormGroup.gibbonFormGroupID=gibbonAttendanceLogFormGroup.gibbonFormGroupID) WHERE date>=:dateStart AND date<=:dateEnd ORDER BY date';
        
        return $this->db()->select($sql, $data);
    }
}
