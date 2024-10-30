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

namespace Gibbon\Domain\User;

use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;
use Gibbon\Domain\ScrubbableGateway;
use Gibbon\Domain\Traits\Scrubbable;
use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\Traits\ScrubByPerson;
use Gibbon\Domain\Traits\SharedUserLogic;

/**
 * User Gateway
 *
 * @version v16
 * @since   v16
 */
class UserGateway extends QueryableGateway implements ScrubbableGateway
{
    use TableAware;
    use SharedUserLogic;
    use Scrubbable;
    use ScrubByPerson;

    private static $tableName = 'gibbonPerson';
    private static $primaryKey = 'gibbonPersonID';

    private static $searchableColumns = ['preferredName', 'firstName', 'surname', 'username', 'studentID', 'email', 'emailAlternate', 'phone1', 'phone2', 'phone3', 'phone4', 'vehicleRegistration', 'gibbonRole.name'];

    private static $scrubbableKey = false;
    private static $scrubbableColumns = ['passwordStrong' => 'randomString', 'passwordStrongSalt' => 'randomString', 'address1' => '', 'address1District' => '', 'address1Country' => '', 'address2' => '', 'address2District' => '', 'address2Country' => '', 'phone1Type' => '', 'phone1CountryCode' => '', 'phone1' => '', 'phone3Type' => '', 'phone3CountryCode' => '', 'phone3' => '', 'phone2Type' => '', 'phone2CountryCode' => '', 'phone2' => '', 'phone4Type' => '', 'phone4CountryCode' => '', 'phone4' => '', 'website' => '', 'languageFirst' => '', 'languageSecond' => '', 'languageThird' => '', 'countryOfBirth' => '',  'ethnicity' => '', 'religion' => '', 'profession' => '', 'employer' => '', 'jobTitle' => '', 'emergency1Name' => '', 'emergency1Number1' => '', 'emergency1Number2' => '', 'emergency1Relationship' => '', 'emergency2Name' => '', 'emergency2Number1' => '', 'emergency2Number2' => '', 'emergency2Relationship' => '', 'transport' => '', 'transportNotes' => '', 'calendarFeedPersonal' => '', 'lockerNumber' => '', 'vehicleRegistration' => '', 'personalBackground' => '', 'studentAgreements' =>null, 'fields' => ''];

    private static $safeUserFields = ['gibbonPersonID', 'username', 'surname', 'firstName', 'preferredName', 'officialName', 'email', 'emailAlternate', 'website', 'gender', 'status', 'image_240', 'lastTimestamp', 'messengerLastRead', 'calendarFeedPersonal', 'viewCalendarSchool', 'viewCalendarPersonal', 'viewCalendarSpaceBooking', 'dateStart', 'personalBackground', 'gibboni18nIDPersonal', 'googleAPIRefreshToken', 'microsoftAPIRefreshToken', 'genericAPIRefreshToken', 'receiveNotificationEmails', 'mfaToken' , 'cookieConsent', 'gibbonHouseID', 'passwordForceReset'];

    /**
     * Queries the list of users for the Manage Users page.
     *
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryAllUsers(QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonPerson.gibbonPersonID', 'gibbonPerson.surname', 'gibbonPerson.preferredName', 'gibbonPerson.username',
                'gibbonPerson.image_240', 'gibbonPerson.status', 'gibbonRole.name as primaryRole'
            ])
            ->leftJoin('gibbonRole', 'gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID');

        $criteria->addFilterRules($this->getSharedUserFilterRules());

        return $this->runQuery($query, $criteria);
    }

    /**
     * Gets basic user and role fields required for login.
     *
     * @param string $username
     * @return Result
     */
    public function selectLoginDetailsByUsername($username)
    {
        $data = ['username' => $username];
        $sql = "SELECT 
                    gibbonPerson.gibbonPersonID,
                    gibbonPerson.username,
                    gibbonPerson.passwordStrong,
                    gibbonPerson.passwordStrongSalt,
                    gibbonPerson.gibbonRoleIDPrimary,
                    gibbonPerson.gibbonRoleIDAll,
                    gibbonPerson.canLogin,
                    gibbonPerson.failCount,
                    gibbonRole.futureYearsLogin,
                    gibbonRole.pastYearsLogin,
                    gibbonRole.name as roleName,
                    gibbonRole.category as roleCategory,
                    gibbonPerson.gibboni18nIDPersonal,
                    gibbonPerson.gibbonThemeIDPersonal
                FROM gibbonPerson 
                LEFT JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) 
                WHERE (
                    (username=:username OR (LOCATE('@', :username)>0 AND email=:username)) 
                    AND status='Full' 
                )";

        return $this->db()->select($sql, $data);
    }

    /**
     * Gets a set of fields to populate the session data, excluding unsafe fields such as passwords.
     *
     * @param string $gibbonPersonID
     * @return Result
     */
    public function getSafeUserData($gibbonPersonID)
    {
        $user = $this->getByID($gibbonPersonID);
        return array_intersect_key($user, array_flip(self::$safeUserFields));
    }

    /**
     * Returns basic user details, including name and user photo, and also returns 
     * form group information if this user is a student.
     *
     * @param string $gibbonPersonID
     * @return array
     */
    public function getUserDetails($gibbonPersonID, $gibbonSchoolYearID)
    {
        $data = ['gibbonPersonID' => $gibbonPersonID, 'gibbonSchoolYearID' => $gibbonSchoolYearID];
        $sql = "SELECT gibbonPerson.gibbonPersonID, title, surname, preferredName, email, image_240, gender, dateStart, dateEnd, gibbonStudentEnrolment.gibbonStudentEnrolmentID, gibbonStudentEnrolment.gibbonSchoolYearID, gibbonYearGroup.gibbonYearGroupID, gibbonYearGroup.nameShort AS yearGroup, gibbonYearGroup.name AS yearGroupName, gibbonFormGroup.gibbonFormGroupID, gibbonFormGroup.nameShort AS formGroup, gibbonFormGroup.name AS formGroupName, gibbonRole.category as roleCategory
                FROM gibbonPerson
                JOIN gibbonRole ON (gibbonRole.gibbonRoleID=gibbonPerson.gibbonRoleIDPrimary)
                LEFT JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID)
                LEFT JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID)
                LEFT JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID)
                WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID";

        return $this->db()->selectOne($sql, $data);
    }

    /**
     * Selects the family info for a subset of users. Primarily used to join family data to the queryAllUsers results.
     *
     * @param string|array $gibbonPersonIDList
     * @return Result
     */
    public function selectFamilyDetailsByPersonID($gibbonPersonIDList)
    {
        $idList = is_array($gibbonPersonIDList) ? implode(',', $gibbonPersonIDList) : $gibbonPersonIDList;
        $data = array('idList' => $idList);
        $sql = "(
            SELECT LPAD(gibbonFamilyAdult.gibbonPersonID, 10, '0'), gibbonFamilyAdult.gibbonFamilyID, 'adult' AS role, gibbonFamily.name, (SELECT gibbonFamilyChild.gibbonPersonID FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID ORDER BY gibbonPerson.dob DESC LIMIT 1) as gibbonPersonIDStudent
            FROM gibbonFamily
            JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID)
            WHERE FIND_IN_SET(gibbonFamilyAdult.gibbonPersonID, :idList)
        ) UNION (
            SELECT LPAD(gibbonFamilyChild.gibbonPersonID, 10, '0'), gibbonFamilyChild.gibbonFamilyID, 'child' AS role, gibbonFamily.name, gibbonFamilyChild.gibbonPersonID as gibbonPersonIDStudent
            FROM gibbonFamily
            JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID)
            WHERE FIND_IN_SET(gibbonFamilyChild.gibbonPersonID, :idList)
        ) ORDER BY gibbonFamilyID";

        return $this->db()->select($sql, $data);
    }

    public function selectUserNamesByStatus($status = 'Full', $category = null)
    {
        $data = array('statusList' => is_array($status) ? implode(',', $status) : $status );
        $sql = "SELECT gibbonPersonID, surname, preferredName, status, dateStart, dateEnd, username, lastTimestamp, gibbonRole.category as roleCategory
                FROM gibbonPerson
                JOIN gibbonRole ON (gibbonRole.gibbonRoleID=gibbonPerson.gibbonRoleIDPrimary)
                WHERE FIND_IN_SET(gibbonPerson.status, :statusList)";

        if (!is_null($category)) {
            $data['category'] = $category;
            $sql .= " AND gibbonRole.category=:category";
        }

        $sql .= " ORDER BY surname, preferredName";

        return $this->db()->select($sql, $data);
    }

    public function selectNotificationDetailsByPerson($gibbonPersonID)
    {
        $gibbonPersonIDList = is_array($gibbonPersonID)? $gibbonPersonID : [$gibbonPersonID];

        $data = ['gibbonPersonIDList' => implode(',', $gibbonPersonIDList)];
        $sql = "SELECT gibbonPerson.gibbonPersonID as groupBy, gibbonPerson.gibbonPersonID, title, surname, preferredName, gibbonPerson.status, image_240, username, email, phone1, phone1CountryCode, phone1Type, gibbonRole.category as roleCategory, gibbonStaff.jobTitle, gibbonStaff.type
                FROM gibbonPerson
                JOIN gibbonRole ON (gibbonRole.gibbonRoleID=gibbonPerson.gibbonRoleIDPrimary)
                LEFT JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID)
                WHERE FIND_IN_SET(gibbonPerson.gibbonPersonID, :gibbonPersonIDList)
                ORDER BY FIND_IN_SET(gibbonPerson.gibbonPersonID, :gibbonPersonIDList), surname, preferredName";

        return $this->db()->select($sql, $data);
    }

    public function addRoleToUser($gibbonPersonID, $gibbonRoleID)
    {
        $data = ['gibbonPersonID' => $gibbonPersonID, 'gibbonRoleID' => $gibbonRoleID];
        $sql = "UPDATE gibbonPerson SET gibbonRoleIDAll=concat(gibbonRoleIDAll, ',', :gibbonRoleID) WHERE gibbonPersonID=:gibbonPersonID AND gibbonRoleIDAll NOT LIKE CONCAT('%', :gibbonRoleID, '%')";

        return $this->db()->affectingStatement($sql, $data);
    }

    public function removeRoleFromUser($gibbonPersonID, $gibbonRoleID)
    {
        $data = ['gibbonPersonID' => $gibbonPersonID, 'gibbonRoleID' => $gibbonRoleID];
        $sql = "UPDATE gibbonPerson SET gibbonRoleIDAll=REPLACE(REPLACE(gibbonRoleIDAll, :gibbonRoleID, ''), ',,', '') WHERE gibbonPersonID=:gibbonPersonID AND gibbonRoleIDAll LIKE CONCAT('%', :gibbonRoleID, '%')";

        return $this->db()->update($sql, $data);
    }
}
