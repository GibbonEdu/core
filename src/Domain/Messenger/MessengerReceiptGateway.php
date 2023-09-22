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

namespace Gibbon\Domain\Messenger;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;

/**
 * MessengerReceiptGateway
 *
 * @version v25
 * @since   v25
 */
class MessengerReceiptGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonMessengerReceipt';
    private static $primaryKey = 'gibbonMessengerReceiptID';
    private static $searchableColumns = [];
    
    /**
     * Queries the list of messages for the Manage Messages page, optionally filtered for the current user.
     *
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryMessageRecipients(QueryCriteria $criteria, $gibbonMessengerID, $gibbonSchoolYearID)
    {
        $query = $this
            ->newQuery()
            ->cols([
                'gibbonMessenger.gibbonMessengerID', 'gibbonMessenger.status', 'gibbonPerson.title', 'gibbonPerson.surname', 'gibbonPerson.preferredName', 'gibbonPerson.email', 'gibbonPerson.phone1', 'gibbonRole.category as role', 'gibbonMessengerReceipt.gibbonMessengerReceiptID', 'gibbonMessengerReceipt.targetType', 'gibbonMessengerReceipt.contactType', 'gibbonMessengerReceipt.contactDetail', 'gibbonFormGroup.name as formGroup', 'gibbonMessengerReceipt.sent'
            ])
            ->from($this->getTableName())
            ->innerJoin('gibbonMessenger', 'gibbonMessenger.gibbonMessengerID=gibbonMessengerReceipt.gibbonMessengerID')
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonMessengerReceipt.gibbonPersonID')
            ->innerJoin('gibbonRole', 'gibbonRole.gibbonRoleID=gibbonPerson.gibbonRoleIDPrimary')
            ->leftJoin('gibbonStudentEnrolment', 'gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->leftJoin('gibbonFormGroup', 'gibbonFormGroup.gibbonFormGroupID=gibbonStudentEnrolment.gibbonFormGroupID')
            ->where('gibbonMessenger.gibbonMessengerID=:gibbonMessengerID')
            ->bindValue('gibbonMessengerID', $gibbonMessengerID)
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        return $this->runQuery($query, $criteria);
    }

    public function selectMessageRecipientList($gibbonMessengerID)
    {
        $data = ['gibbonMessengerID' => $gibbonMessengerID];
        $sql = "SELECT * FROM gibbonMessengerReceipt WHERE gibbonMessengerID=:gibbonMessengerID";

        return $this->db()->select($sql, $data);
    }

    public function deleteRecipientsByID($gibbonMessengerID, $recipientList)
    {
        $recipientList = is_array($recipientList)? implode(',', $recipientList) : $recipientList;

        // Delete individual targets
        $data = ['gibbonMessengerID' => $gibbonMessengerID, 'recipientList' => $recipientList];
        $sql = "DELETE gibbonMessengerTarget FROM gibbonMessengerTarget
                JOIN gibbonMessengerReceipt ON (gibbonMessengerReceipt.gibbonMessengerID=gibbonMessengerTarget.gibbonMessengerID AND gibbonMessengerReceipt.targetType=gibbonMessengerTarget.type COLLATE utf8_general_ci AND gibbonMessengerReceipt.gibbonPersonID=gibbonMessengerTarget.id)
                WHERE gibbonMessengerTarget.gibbonMessengerID=:gibbonMessengerID 
                AND gibbonMessengerTarget.type='Individuals'
                AND FIND_IN_SET(gibbonMessengerReceipt.gibbonMessengerReceiptID, :recipientList)";

        $this->db()->delete($sql, $data);

        // Delete recipients
        $data = ['gibbonMessengerID' => $gibbonMessengerID, 'recipientList' => $recipientList];
        $sql = "DELETE FROM gibbonMessengerReceipt WHERE gibbonMessengerID=:gibbonMessengerID AND FIND_IN_SET(gibbonMessengerReceiptID, :recipientList)";

        return $this->db()->delete($sql, $data);
    }
}
