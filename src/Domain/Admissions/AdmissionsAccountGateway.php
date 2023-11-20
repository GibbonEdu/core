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

namespace Gibbon\Domain\Admissions;

use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;
use Gibbon\Domain\Traits\TableAware;

/**
 * Admissions Account
 *
 * @version v24
 * @since   v24
 */
class AdmissionsAccountGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonAdmissionsAccount';
    private static $primaryKey = 'gibbonAdmissionsAccountID';

    private static $searchableColumns = ['gibbonAdmissionsAccount.email', 'gibbonPerson.surname', 'gibbonPerson.preferredName', 'gibbonRole.name', 'gibbonFamily.name'];

    public function queryAdmissionsAccounts(QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->cols([
                'gibbonAdmissionsAccount.gibbonAdmissionsAccountID',
                'gibbonAdmissionsAccount.email',
                'gibbonAdmissionsAccount.timestampCreated',
                'gibbonAdmissionsAccount.timestampActive',
                'gibbonPerson.gibbonPersonID',
                'gibbonPerson.title',
                'gibbonPerson.surname',
                'gibbonPerson.preferredName',
                'gibbonRole.name as roleName',
                'gibbonFamily.name as familyName',
                'gibbonFamily.gibbonFamilyID as gibbonFamilyID',
                "(COUNT(DISTINCT gibbonAdmissionsApplicationID)) as applicationCount",
                "(COUNT(DISTINCT gibbonFormSubmissionID)) as formCount",
                '(CASE WHEN gibbonPerson.gibbonPersonID IS NULL THEN 1 ELSE 0 END) as sortOrder',
            ])
            ->from($this->getTableName())
            ->leftJoin('gibbonAdmissionsApplication', 'gibbonAdmissionsApplication.foreignTable="gibbonAdmissionsAccount" AND gibbonAdmissionsApplication.foreignTableID=gibbonAdmissionsAccount.gibbonAdmissionsAccountID')
            ->leftJoin('gibbonFormSubmission', 'gibbonFormSubmission.foreignTable="gibbonAdmissionsAccount" AND gibbonFormSubmission.foreignTableID=gibbonAdmissionsAccount.gibbonAdmissionsAccountID')
            ->leftJoin('gibbonPerson', 'gibbonAdmissionsAccount.gibbonPersonID=gibbonPerson.gibbonPersonID')
            ->leftJoin('gibbonRole', 'gibbonRole.gibbonRoleID=gibbonPerson.gibbonRoleIDPrimary')
            ->leftJoin('gibbonFamily', 'gibbonAdmissionsAccount.gibbonFamilyID=gibbonFamily.gibbonFamilyID')
            ->groupBy(['gibbonAdmissionsAccount.gibbonAdmissionsAccountID']);

        return $this->runQuery($query, $criteria);
    }

    public function getAccountByEmail($email)
    {
        $data = ['email' => $email];
        $sql = "SELECT * FROM gibbonAdmissionsAccount WHERE email=:email";

        return $this->db()->selectOne($sql, $data);
    }

    public function getAccountByAccessID($accessID)
    {
        $data = ['accessID' => $accessID];
        $sql = "SELECT * FROM gibbonAdmissionsAccount WHERE accessID=:accessID";

        return $this->db()->selectOne($sql, $data);
    }

    public function getAccountByPerson($gibbonPersonID)
    {
        $data = ['gibbonPersonID' => $gibbonPersonID];
        $sql = "SELECT * FROM gibbonAdmissionsAccount WHERE gibbonPersonID=:gibbonPersonID";

        return $this->db()->selectOne($sql, $data);
    }

    public function getAccountByAccessToken($accessID, $accessToken)
    {
        $data = ['accessID' => $accessID, 'accessToken' => $accessToken];
        $sql = "SELECT * FROM gibbonAdmissionsAccount WHERE accessID=:accessID AND accessToken=:accessToken AND CURRENT_TIMESTAMP() <= timestampTokenExpire";

        return $this->db()->selectOne($sql, $data);
    }

    public function getUniqueAccessID($salt)
    {
        do {
            $accessID = hash('sha256', microtime().$salt);
            $checkID = $this->selectBy(['accessID' => $accessID])->fetch();
        } while (!empty($checkID));

        return $accessID;
    }

    public function getUniqueAccessToken($salt)
    {
        do {
            $accessToken = hash('sha256', microtime().$salt);
            $checkToken = $this->selectBy(['accessToken' => $accessToken])->fetch();
        } while (!empty($checkToken));

        return substr($accessToken, 0, 32);
    }
}
