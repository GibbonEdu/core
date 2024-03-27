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

namespace Gibbon\Domain\Activities;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;
use Gibbon\Services\Format;

/**
 * Activity Gateway
 *
 * @version v27
 * @since   v27
 */
class ActivityAttendanceGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonActivityAttendance';
    private static $primaryKey = 'gibbonActivityAttendanceID';

    private static $searchableColumns = [];

    public function selectStudentAttendanceByActivity($gibbonActivityID) {

        $data = ['gibbonActivityID' => $gibbonActivityID];

        $sql = 'SELECT gibbonActivityAttendance.date, gibbonActivityAttendance.timestampTaken, gibbonActivityAttendance.attendance, gibbonPerson.preferredName, gibbonPerson.surname 
        FROM gibbonActivityAttendance, gibbonPerson 
        WHERE gibbonActivityAttendance.gibbonPersonIDTaker=gibbonPerson.gibbonPersonID 
        AND gibbonActivityAttendance.gibbonActivityID=:gibbonActivityID';

        return $this->db()->select($sql, $data);

    }
   
    public function selectActivityAttendanceByActivity ($gibbonActivityID, $date) {
        $data = ['gibbonActivityID' => $gibbonActivityID, 'date' => $date];

        $sql = 'SELECT gibbonActivityAttendanceID FROM gibbonActivityAttendance WHERE gibbonActivityID=:gibbonActivityID AND date=:date';
        
        return $this->db()->select($sql, $data);
    }

}
