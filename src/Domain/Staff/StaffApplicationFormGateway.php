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

namespace Gibbon\Domain\Staff;

use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;
use Gibbon\Domain\ScrubbableGateway;
use Gibbon\Domain\Traits\Scrubbable;
use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\Traits\ScrubByTimestamp;

/**
 * StaffApplicationForm Gateway
 *
 * @version v16
 * @since   v16
 */
class StaffApplicationFormGateway extends QueryableGateway implements ScrubbableGateway
{
    use TableAware;
    use Scrubbable;
    use ScrubByTimestamp;

    private static $tableName = 'gibbonStaffApplicationForm';
    private static $primaryKey = 'gibbonStaffApplicationFormID';

    private static $searchableColumns = ['gibbonStaffApplicationFormID', 'gibbonStaffApplicationForm.preferredName', 'gibbonStaffApplicationForm.surname', 'gibbonPerson.preferredName', 'gibbonPerson.surname', 'gibbonStaffJobOpening.jobTitle'];
    
    private static $scrubbableKey = 'timestamp';
    private static $scrubbableColumns = ['gender' => null, 'dob' => null, 'email' => null, 'homeAddress' => null, 'homeAddressDistrict' => null, 'homeAddressCountry' => null, 'phone1Type' => null, 'phone1CountryCode' => null, 'phone1' => null, 'countryOfBirth' => null, 'citizenship1' => null, 'citizenship1Passport' => null, 'nationalIDCardNumber' => null, 'residencyStatus' => null, 'visaExpiryDate' => null, 'languageFirst' => null, 'languageSecond' => null, 'languageThird' => null, 'notes' => '', 'questions' => '', 'fields' => '', 'referenceEmail1' => '', 'referenceEmail2' => ''];

    /**
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryApplications(QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonStaffApplicationForm.gibbonStaffApplicationFormID', 'gibbonStaffApplicationForm.status', 'gibbonStaffApplicationForm.priority', 'gibbonStaffApplicationForm.timestamp', 'milestones', 'gibbonStaffJobOpening.jobTitle', 'gibbonStaffApplicationForm.gibbonPersonID', 'gibbonStaffApplicationForm.surname as applicationSurname', 'gibbonStaffApplicationForm.preferredName as applicationPreferredName', 'gibbonPerson.surname', 'gibbonPerson.preferredName'
            ])
            ->innerJoin('gibbonStaffJobOpening', 'gibbonStaffApplicationForm.gibbonStaffJobOpeningID=gibbonStaffJobOpening.gibbonStaffJobOpeningID')
            ->leftJoin('gibbonPerson', 'gibbonStaffApplicationForm.gibbonPersonID=gibbonPerson.gibbonPersonID');

        return $this->runQuery($query, $criteria);
    }
}
