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

/**
 * Activity Student Gateway
 *
 * @version v27
 * @since   v27
 */
class ActivityStudentGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonActivityStudent';
    private static $primaryKey = 'gibbonActivityStudentID';

    private static $searchableColumns = [];

    // $data = array('gibbonActivityID' => $gibbonActivityID, 'today' => date('Y-m-d'), 'statusCheck' => ($enrolment == 'Competitive'? 'Pending' : 'Waiting List'));
    //     $sql = "SELECT gibbonActivityStudent.*, surname, preferredName, gibbonFormGroup.nameShort as formGroupNameShort
    //             FROM gibbonActivityStudent
    //             JOIN gibbonPerson ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID)
    //             LEFT JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=(SELECT gibbonSchoolYearID FROM gibbonSchoolYear WHERE status='Current'))
    //             LEFT JOIN gibbonFormGroup ON (gibbonFormGroup.gibbonFormGroupID=gibbonStudentEnrolment.gibbonFormGroupID)
    //             WHERE gibbonActivityID=:gibbonActivityID
    //             AND NOT gibbonActivityStudent.status=:statusCheck
    //             AND gibbonPerson.status='Full'
    //             AND (dateStart IS NULL OR dateStart<=:today) AND (dateEnd IS NULL OR dateEnd>=:today)
    //             ORDER BY gibbonActivityStudent.status, timestamp";
}
