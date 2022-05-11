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

    private static $searchableColumns = ['email'];

    public function queryAdmissionsAccounts(QueryCriteria $criteria)
    {
        $query = $this
            ->newQuery()
            ->distinct()
            ->cols([
                'gibbonAdmissionsAccount.gibbonAdmissionsAccountID',
                'gibbonAdmissionsAccount.email',
                'gibbonAdmissionsAccount.timestampCreated',
                "(COUNT(CASE WHEN gibbonForm.type='Application' THEN gibbonFormSubmissionID END)) as applicationCount",
                "(COUNT(CASE WHEN gibbonForm.type<>'Application' THEN gibbonFormSubmissionID END)) as formCount"
            ])
            ->from($this->getTableName())
            ->leftJoin('gibbonFormSubmission', 'gibbonFormSubmission.foreignTable="gibbonAdmissionsAccount" AND gibbonFormSubmission.foreignTableID=gibbonAdmissionsAccount.gibbonAdmissionsAccountID')
            ->leftJoin('gibbonForm', 'gibbonFormSubmission.gibbonFormID=gibbonForm.gibbonFormID')
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
