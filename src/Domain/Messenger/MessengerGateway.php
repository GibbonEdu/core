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

use Gibbon\Contracts\Database\Connection;
use Gibbon\Contracts\Services\Session;
use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;
use Gibbon\Domain\User\RoleGateway;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Data\Validator;

/**
 * MessengerGateway
 *
 * @version v19
 * @since   v19
 */
class MessengerGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'gibbonMessenger';
    private static $primaryKey = 'gibbonMessengerID';
    private static $searchableColumns = ['gibbonMessenger.subject', 'gibbonMessenger.body'];


    /**
     * @var Session
     */
    private $session;

    /**
     * @var RoleGateway
     */
    private $roleGateway;

    /**
     * @var Validator
     */
    private $validator;

    public function __construct(
        Connection $db,
        Session $session,
        Validator $validator,
        RoleGateway $roleGateway
    )
    {
        parent::__construct($db);
        $this->session = $session;
        $this->roleGateway = $roleGateway;
        $this->validator = $validator;
    }

    /**
     * Queries the list of messages for the Manage Messages page, optionally filtered for the current user.
     *
     * @param QueryCriteria $criteria
     * @return DataSet
     */
    public function queryMessages(QueryCriteria $criteria, $gibbonSchoolYearID, $gibbonPersonID = null)
    {
        $query = $this
            ->newQuery()
            ->from($this->getTableName())
            ->cols([
                'gibbonMessenger.gibbonMessengerID', 'gibbonMessenger.subject', 'gibbonMessenger.timestamp', 'gibbonMessenger.email', 'gibbonMessenger.messageWall', 'gibbonMessenger.sms', 'gibbonMessenger.messageWall_dateStart', 'gibbonMessenger.messageWall_dateEnd', 'gibbonMessenger.emailReceipt', 'gibbonMessenger.confidential', 'gibbonMessenger.status', 'gibbonPerson.title', 'gibbonPerson.surname', 'gibbonPerson.preferredName', 'gibbonRole.category',
            ])
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=gibbonMessenger.gibbonPersonID')
            ->innerJoin('gibbonRole', 'gibbonRole.gibbonRoleID=gibbonPerson.gibbonRoleIDPrimary')
            ->where('gibbonMessenger.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        if (!empty($gibbonPersonID)) {
            $query->where('gibbonMessenger.gibbonPersonID=:gibbonPersonID')
                ->bindValue('gibbonPersonID', $gibbonPersonID);
        }

        $criteria->addFilterRules([
            'status' => function ($query, $status) {
                return $query
                    ->where('gibbonMessenger.status=:status')
                    ->bindValue('status', $status);
            },
            'confidential' => function ($query, $gibbonPersonIDCreatedBy) {
                return $query
                    ->where('(gibbonMessenger.confidential="N" OR (gibbonMessenger.confidential="Y" AND gibbonMessenger.gibbonPersonID=:gibbonPersonIDCreatedBy))')
                    ->bindValue('gibbonPersonIDCreatedBy', $gibbonPersonIDCreatedBy);
            },
        ]);

        return $this->runQuery($query, $criteria);
    }

    public function getRecentMessageWallTimestamp()
    {
        $sql = "SELECT UNIX_TIMESTAMP(timestamp) FROM gibbonMessenger WHERE messageWall='Y' ORDER BY timestamp DESC LIMIT 1";

        return $this->db()->selectOne($sql);
    }

    public function getSendingMessages()
    {
        $query = $this
            ->newSelect()
            ->from('gibbonLog')
            ->cols(['gibbonLog.gibbonLogID', 'gibbonLog.serialisedArray'])
            ->where("gibbonLog.title='Background Process - MessageProcess'")
            ->where("(gibbonLog.serialisedArray LIKE '%s:7:\"Running\";%' OR gibbonLog.serialisedArray LIKE '%s:7:\"Ready\";%')")
            ->orderBy(['gibbonLog.timestamp DESC']);

        $logs = $this->runSelect($query)->fetchAll();

        return array_filter(array_reduce($logs, function ($group, $item) {
            $item['data'] = unserialize($item['serialisedArray']) ?? [];
            $gibbonMessengerID =  str_pad(($item['data']['data'][0] ?? 0), 12, '0', STR_PAD_LEFT);

            if (!empty($gibbonMessengerID)) {
                $group[$gibbonMessengerID] = $item['gibbonLogID'];
            }

            return $group;
        }, []));
    }

    public function getMessageDetailsByID($gibbonMessengerID)
    {
        $data = ['gibbonMessengerID' => $gibbonMessengerID];
        $sql = "SELECT gibbonMessenger.*, title, surname, preferredName FROM gibbonMessenger LEFT JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonMessengerID=:gibbonMessengerID";

        return $this->db()->selectOne($sql, $data);
    }

    public function getMessageDetailsByIDAndOwner($gibbonMessengerID, $gibbonPersonID)
    {
        $data = ['gibbonMessengerID' => $gibbonMessengerID, 'gibbonPersonID' => $gibbonPersonID];
        $sql="SELECT gibbonMessenger.*, title, surname, preferredName FROM gibbonMessenger LEFT JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonMessengerID=:gibbonMessengerID AND gibbonMessenger.gibbonPersonID=:gibbonPersonID";

        return $this->db()->selectOne($sql, $data);
    }

    public function selectMessageTargetsByID($gibbonMessengerID)
    {
        $data = ['gibbonMessengerID' => $gibbonMessengerID];
        $sql = "SELECT * FROM gibbonMessengerTarget WHERE gibbonMessengerID=:gibbonMessengerID ORDER BY type";

        return $this->db()->select($sql, $data);
    }

    /**
     * Retrieve messages into the format specified by $mode parameter.
     *
     * @param string $mode  Mode may be:
     *                      "print" (return table of messages); or
     *                      "array" (return array of messages); or
     *                      "count" (return message count); or
     *                      "result" (return database query result).
     *                      Default: "print".
     * @param string $date  Date in YYYY-MM-DD format. Default: today's date.
     *
     * @return string|int|Result  Format specified by $mode parameter.
     */
    public function getMessages(string $mode = 'print', string $date = '')
    {
        $session = $this->session;
        $connection2 = $this->db()->getConnection();

        $return = '';
        $dataPosts = array();

        if ($date == '') {
            $date = date('Y-m-d');
        }
        if ($mode != 'print' and $mode != 'count' and $mode != 'result' and $mode != 'array') {
            $mode = 'print';
        }

        //Work out all role categories this user has, ignoring "Other"
        $roles = $session->get('gibbonRoleIDAll');
        $roleCategory = '';
        $staff = false;
        $student = false;
        $parent = false;
        for ($i = 0; $i < count($roles); ++$i) {
            $roleCategory = $this->roleGateway->getRoleCategory($roles[$i][0]);
            if ($roleCategory == 'Staff') {
                $staff = true;
            } elseif ($roleCategory == 'Student') {
                $student = true;
            } elseif ($roleCategory == 'Parent') {
                $parent = true;
            }
        }

        //If parent get a list of student IDs
        if ($parent) {
            $children = [];

            $data = array('gibbonPersonID' => $session->get('gibbonPersonID'));
            $sql = "SELECT * FROM gibbonFamilyAdult WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
            while ($row = $result->fetch()) {

                    $dataChild = array('gibbonFamilyID' => $row['gibbonFamilyID'], 'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'today' => date('Y-m-d'));
                    $sqlChild = "SELECT * FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<=:today) AND (dateEnd IS NULL  OR dateEnd>=:today) AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY surname, preferredName ";
                    $resultChild = $connection2->prepare($sqlChild);
                    $resultChild->execute($dataChild);
                while ($rowChild = $resultChild->fetch()) {
                    $children[] = $rowChild['gibbonPersonID'];
                }
            }

        }

        $dataPosts['date'] = $date;
        $dateWhere = "(:date BETWEEN gibbonMessenger.messageWall_dateStart AND gibbonMessenger.messageWall_dateEnd)";

        //My roles
        $roles = $session->get('gibbonRoleIDAll');
        $sqlWhere = '(';
        if (count($roles) > 0) {
            for ($i = 0; $i < count($roles); ++$i) {
                $dataPosts['role'.$i] = $roles[$i][0];
                $sqlWhere .= 'id=:role'.$i.' OR ';
            }
            $sqlWhere = substr($sqlWhere, 0, -3).')';
        }

        if ($sqlWhere != '(') {
            $sqlPosts = "(SELECT gibbonMessenger.*, title, surname, preferredName, authorRole.category AS category, image_240, concat('Role: ', gibbonRole.name) AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole AS authorRole ON (gibbonPerson.gibbonRoleIDPrimary=authorRole.gibbonRoleID) JOIN gibbonRole ON (gibbonMessengerTarget.id=gibbonRole.gibbonRoleID) WHERE gibbonMessenger.status='Sent' AND gibbonMessengerTarget.type='Role' AND $dateWhere AND $sqlWhere)";
        }

        //My role categories
        try {
            $dataRoleCategory = array('gibbonPersonID' => $session->get('gibbonPersonID'));
            $sqlRoleCategory = "SELECT DISTINCT category FROM gibbonRole JOIN gibbonPerson ON (FIND_IN_SET(gibbonRole.gibbonRoleID, gibbonPerson.gibbonRoleIDAll)) WHERE gibbonPersonID=:gibbonPersonID";
            $resultRoleCategory = $connection2->prepare($sqlRoleCategory);
            $resultRoleCategory->execute($dataRoleCategory);
        } catch (\PDOException $e) {
        }
        $sqlWhere = '(';
        if ($resultRoleCategory->rowCount() > 0) {
            $i = 0;
            while ($rowRoleCategory = $resultRoleCategory->fetch()) {
                $dataPosts['roleCategory'.$i] = $rowRoleCategory['category'];
                $sqlWhere .= 'id=:roleCategory'.$i.' OR ';
                ++$i;
            }
            $sqlWhere = substr($sqlWhere, 0, -3).')';
        }
        if ($sqlWhere != '(') {
            $sqlPosts = $sqlPosts." UNION (SELECT DISTINCT gibbonMessenger.*, title, surname, preferredName, authorRole.category AS category, image_240, concat('Role Category: ', gibbonRole.category) AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole AS authorRole ON (gibbonPerson.gibbonRoleIDPrimary=authorRole.gibbonRoleID) JOIN gibbonRole ON (gibbonMessengerTarget.id=gibbonRole.category) WHERE gibbonMessenger.status='Sent' AND gibbonMessengerTarget.type='Role Category' AND $dateWhere AND $sqlWhere)";
        }

        //My year groups
        if ($staff) {
            $dataPosts['gibbonSchoolYearID0'] = $session->get('gibbonSchoolYearID');
            $dataPosts['gibbonPersonID0'] = $session->get('gibbonPersonID');
            // Include staff by courses taught in the same year group.
            $sqlPosts = $sqlPosts." UNION (SELECT gibbonMessenger.*, title, surname, preferredName, category, image_240, 'Year Groups' AS source
                    FROM gibbonMessenger
                    JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID)
                    JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID)
                    JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID)
                    JOIN gibbonCourse ON (FIND_IN_SET(gibbonMessengerTarget.id, gibbonCourse.gibbonYearGroupIDList))
                    JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
                    JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID)
                    JOIN gibbonStaff ON (gibbonCourseClassPerson.gibbonPersonID=gibbonStaff.gibbonPersonID)
                    WHERE gibbonMessenger.status='Sent' AND gibbonStaff.gibbonPersonID=:gibbonPersonID0
                    AND gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID0
                    AND gibbonMessengerTarget.type='Year Group' AND gibbonMessengerTarget.staff='Y' 
                    AND $dateWhere
                    GROUP BY gibbonMessenger.gibbonMessengerID )";
            // Include staff who are tutors of any student in the same year group.
            $sqlPosts .= "UNION (SELECT gibbonMessenger.*, title, surname, preferredName, category, image_240, 'Year Groups' AS source
                    FROM gibbonMessenger
                    JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID)
                    JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID)
                    JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID)
                    JOIN gibbonYearGroup ON (gibbonYearGroup.gibbonYearGroupID=gibbonMessengerTarget.id)
                    JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID)
                    JOIN gibbonFormGroup ON (gibbonFormGroup.gibbonFormGroupID=gibbonStudentEnrolment.gibbonFormGroupID)
                    JOIN gibbonStaff ON (gibbonFormGroup.gibbonPersonIDTutor=gibbonStaff.gibbonPersonID OR gibbonFormGroup.gibbonPersonIDTutor2=gibbonStaff.gibbonPersonID OR gibbonFormGroup.gibbonPersonIDTutor3=gibbonStaff.gibbonPersonID)
                    WHERE gibbonMessenger.status='Sent' AND gibbonStaff.gibbonPersonID=:gibbonPersonID0
                    AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID0
                    AND gibbonMessengerTarget.type='Year Group' AND gibbonMessengerTarget.staff='Y' 
                    AND $dateWhere
                    GROUP BY gibbonMessenger.gibbonMessengerID)";
        }
        if ($student) {
            $dataPosts['gibbonSchoolYearID1'] = $session->get('gibbonSchoolYearID');
            $dataPosts['gibbonPersonID1'] = $session->get('gibbonPersonID');
            $sqlPosts = $sqlPosts." UNION (SELECT gibbonMessenger.*, title, surname, preferredName, category, image_240, concat('Year Group ', gibbonYearGroup.nameShort) AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) JOIN gibbonStudentEnrolment ON (gibbonMessengerTarget.id=gibbonStudentEnrolment.gibbonYearGroupID) JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) WHERE gibbonMessenger.status='Sent' AND gibbonStudentEnrolment.gibbonPersonID=:gibbonPersonID1 AND gibbonMessengerTarget.type='Year Group' AND $dateWhere AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID1 AND students='Y')";
        }
        if ($parent and !empty($children)) {
            $dataPosts['gibbonSchoolYearID2'] = $session->get('gibbonSchoolYearID');
            $dataPosts['children'] = implode(',', $children);
            $sqlPosts = $sqlPosts." UNION (SELECT gibbonMessenger.*, title, surname, preferredName, category, image_240, concat('Year Group: ', gibbonYearGroup.nameShort) AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) JOIN gibbonStudentEnrolment ON (gibbonMessengerTarget.id=gibbonStudentEnrolment.gibbonYearGroupID) JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) WHERE gibbonMessenger.status='Sent' AND FIND_IN_SET(gibbonStudentEnrolment.gibbonPersonID, :children) AND gibbonMessengerTarget.type='Year Group' AND $dateWhere AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID2 AND parents='Y')";
        }

        //My form groups
        if ($staff) {
            $sqlWhere = '(';

            try {
                $dataFormGroup = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonIDTutor' => $session->get('gibbonPersonID'), 'gibbonPersonIDTutor2' => $session->get('gibbonPersonID'), 'gibbonPersonIDTutor3' => $session->get('gibbonPersonID'));
                $sqlFormGroup = 'SELECT * FROM gibbonFormGroup WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND (gibbonPersonIDTutor=:gibbonPersonIDTutor OR gibbonPersonIDTutor2=:gibbonPersonIDTutor2 OR gibbonPersonIDTutor3=:gibbonPersonIDTutor3)';
                $resultFormGroup = $connection2->prepare($sqlFormGroup);
                $resultFormGroup->execute($dataFormGroup);
            } catch (\PDOException $e) {
                $resultFormGroup = new \Gibbon\Database\Result();
            }

            if ($resultFormGroup->rowCount() > 0) {
                $i = 0;
                while ($rowFormGroup = $resultFormGroup->fetch()) {
                    $dataPosts['form'.$i] = $rowFormGroup['gibbonFormGroupID'];
                    $sqlWhere .= 'id=:form'.$i.' OR ';
                    $i++;
                }
                $sqlWhere = substr($sqlWhere, 0, -3).')';
                if ($sqlWhere != '(') {
                    $sqlPosts = $sqlPosts." UNION (SELECT gibbonMessenger.*, title, surname, preferredName, category, image_240, concat('Form Group: ', gibbonFormGroup.nameShort) AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) JOIN gibbonFormGroup ON (gibbonMessengerTarget.id=gibbonFormGroup.gibbonFormGroupID) WHERE gibbonMessenger.status='Sent' AND gibbonMessengerTarget.type='Form Group' AND $dateWhere AND $sqlWhere AND staff='Y')";
                }
            }
        }
        if ($student) {
            $dataPosts['gibbonSchoolYearID3'] = $session->get('gibbonSchoolYearID');
            $dataPosts['gibbonPersonID2'] = $session->get('gibbonPersonID');
            $sqlPosts = $sqlPosts." UNION (SELECT gibbonMessenger.*, title, surname, preferredName, category, image_240, concat('Form Group: ', gibbonFormGroup.nameShort) AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) JOIN gibbonStudentEnrolment ON (gibbonMessengerTarget.id=gibbonStudentEnrolment.gibbonFormGroupID) JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID) WHERE gibbonMessenger.status='Sent' AND gibbonStudentEnrolment.gibbonPersonID=:gibbonPersonID2 AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID3 AND gibbonMessengerTarget.type='Form Group' AND $dateWhere AND students='Y')";
        }
        if ($parent and !empty($children)) {
            $dataPosts['gibbonSchoolYearID4'] = $session->get('gibbonSchoolYearID');
            $dataPosts['children'] = implode(',', $children);
            $sqlPosts = $sqlPosts." UNION (SELECT gibbonMessenger.*, title, surname, preferredName, category, image_240, concat('Form Group: ', gibbonFormGroup.nameShort) AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) JOIN gibbonStudentEnrolment ON (gibbonMessengerTarget.id=gibbonStudentEnrolment.gibbonFormGroupID) JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID) WHERE gibbonMessenger.status='Sent' AND FIND_IN_SET(gibbonStudentEnrolment.gibbonPersonID, :children) AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID4 AND gibbonMessengerTarget.type='Form Group' AND $dateWhere AND parents='Y')";
        }

        //My courses
        //First check for any course, then do specific parent check

            $dataClasses = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $session->get('gibbonPersonID'));
            $sqlClasses = "SELECT DISTINCT gibbonCourseClass.gibbonCourseID FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND NOT role LIKE '%- Left'";
            $resultClasses = $connection2->prepare($sqlClasses);
            $resultClasses->execute($dataClasses);
        $sqlWhere = '(';
        if ($resultClasses->rowCount() > 0) {
            $i = 0;
            while ($rowClasses = $resultClasses->fetch()) {
                $dataPosts['course'.$i] = $rowClasses['gibbonCourseID'];
                $sqlWhere .= 'id=:course'.$i.' OR ';
                $i++;
            }
            $sqlWhere = substr($sqlWhere, 0, -3).')';
            if ($sqlWhere != '(') {
                if ($staff) {
                    $sqlPosts = $sqlPosts." UNION (SELECT gibbonMessenger.*, title, surname, preferredName, category, image_240, concat('Course: ', gibbonCourse.nameShort) AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) JOIN gibbonCourse ON (gibbonMessengerTarget.id=gibbonCourse.gibbonCourseID) WHERE gibbonMessenger.status='Sent' AND gibbonMessengerTarget.type='Course' AND $dateWhere AND $sqlWhere AND staff='Y')";
                }
                if ($student) {
                    $sqlPosts = $sqlPosts." UNION (SELECT gibbonMessenger.*, title, surname, preferredName, category, image_240, concat('Course: ', gibbonCourse.nameShort) AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) JOIN gibbonCourse ON (gibbonMessengerTarget.id=gibbonCourse.gibbonCourseID) WHERE gibbonMessenger.status='Sent' AND gibbonMessengerTarget.type='Course' AND $dateWhere AND $sqlWhere AND students='Y')";
                }
            }
        }
        if ($parent and !empty($children)) {

                $dataClasses = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'children' => implode(',', $children));
                $sqlClasses = "SELECT DISTINCT gibbonCourseClass.gibbonCourseID FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND FIND_IN_SET(gibbonCourseClassPerson.gibbonPersonID, :children) AND NOT role LIKE '%- Left'";
                $resultClasses = $connection2->prepare($sqlClasses);
                $resultClasses->execute($dataClasses);
            $sqlWhere = '(';
            if ($resultClasses->rowCount() > 0) {
                $i = 0;
                while ($rowClasses = $resultClasses->fetch()) {
                    $dataPosts['courseParent'.$i] = $rowClasses['gibbonCourseID'];
                    $sqlWhere .= 'id=:courseParent'.$i.' OR ';
                }
                $sqlWhere = substr($sqlWhere, 0, -3).')';
                if ($sqlWhere != '(') {
                    $sqlPosts = $sqlPosts." UNION (SELECT gibbonMessenger.*, title, surname, preferredName, category, image_240, concat('Course: ', gibbonCourse.nameShort) AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) JOIN gibbonCourse ON (gibbonMessengerTarget.id=gibbonCourse.gibbonCourseID) WHERE gibbonMessenger.status='Sent' AND gibbonMessengerTarget.type='Course' AND $dateWhere AND $sqlWhere AND parents='Y')";
                }
            }
        }

        //My classes
        //First check for any role, then do specific parent check

            $dataClasses = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $session->get('gibbonPersonID'));
            $sqlClasses = "SELECT gibbonCourseClass.gibbonCourseClassID FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND NOT role LIKE '%- Left'";
            $resultClasses = $connection2->prepare($sqlClasses);
            $resultClasses->execute($dataClasses);
        $sqlWhere = '(';
        if ($resultClasses->rowCount() > 0) {
            $i = 0;
            while ($rowClasses = $resultClasses->fetch()) {
                $dataPosts['class'.$i] = $rowClasses['gibbonCourseClassID'];
                $sqlWhere .= 'id=:class'.$i.' OR ';
                $i++;
            }
            $sqlWhere = substr($sqlWhere, 0, -3).')';
            if ($sqlWhere != '(') {
                if ($staff) {
                    $sqlPosts = $sqlPosts." UNION (SELECT gibbonMessenger.*, title, surname, preferredName, category, image_240, concat('Class: ', gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) JOIN gibbonCourseClass ON (gibbonMessengerTarget.id=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonMessenger.status='Sent' AND gibbonMessengerTarget.type='Class' AND $dateWhere AND $sqlWhere AND staff='Y')";
                }
                if ($student) {
                    $sqlPosts = $sqlPosts." UNION (SELECT gibbonMessenger.*, title, surname, preferredName, category, image_240, concat('Class: ', gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) JOIN gibbonCourseClass ON (gibbonMessengerTarget.id=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonMessenger.status='Sent' AND gibbonMessengerTarget.type='Class' AND $dateWhere AND $sqlWhere AND students='Y')";
                }
            }
        }
        if ($parent and !empty($children)) {

                $dataClasses = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'children' => implode(',', $children));

                $sqlClasses = "SELECT gibbonCourseClass.gibbonCourseClassID FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND FIND_IN_SET(gibbonCourseClassPerson.gibbonPersonID, :children) AND NOT role LIKE '%- Left'";
                $resultClasses = $connection2->prepare($sqlClasses);
                $resultClasses->execute($dataClasses);
            $sqlWhere = '(';
            if ($resultClasses->rowCount() > 0) {
                $i = 0;
                while ($rowClasses = $resultClasses->fetch()) {
                    $dataPosts['classParent'.$i] = $rowClasses['gibbonCourseClassID'];
                    $sqlWhere .= 'id=:classParent'.$i.' OR ';
                    $i++;
                }
                $sqlWhere = substr($sqlWhere, 0, -3).')';
                if ($sqlWhere != '(') {
                    $sqlPosts = $sqlPosts." UNION (SELECT gibbonMessenger.*, title, surname, preferredName, category, image_240, concat('Class: ', gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) JOIN gibbonCourseClass ON (gibbonMessengerTarget.id=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonMessenger.status='Sent' AND gibbonMessengerTarget.type='Class' AND $dateWhere AND $sqlWhere AND parents='Y')";
                }
            }
        }

        //My activities
        if ($staff) {

                $dataActivities = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $session->get('gibbonPersonID'));
                $sqlActivities = 'SELECT gibbonActivity.gibbonActivityID FROM gibbonActivity JOIN gibbonActivityStaff ON (gibbonActivityStaff.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonActivityStaff.gibbonPersonID=:gibbonPersonID';
                $resultActivities = $connection2->prepare($sqlActivities);
                $resultActivities->execute($dataActivities);
            $sqlWhere = '(';
            if ($resultActivities->rowCount() > 0) {
                $i = 0;
                while ($rowActivities = $resultActivities->fetch()) {
                    $dataPosts['activityStaff'.$i] = $rowActivities['gibbonActivityID'];
                    $sqlWhere .= 'id=:activityStaff'.$i.' OR ';
                    $i++;
                }
                $sqlWhere = substr($sqlWhere, 0, -3).')';
                if ($sqlWhere != '(') {
                    $sqlPosts = $sqlPosts." UNION (SELECT gibbonMessenger.*, title, surname, preferredName, category, image_240, concat('Activity: ', gibbonActivity.name) AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) JOIN gibbonActivity ON (gibbonMessengerTarget.id=gibbonActivity.gibbonActivityID) WHERE gibbonMessenger.status='Sent' AND gibbonMessengerTarget.type='Activity' AND $dateWhere AND $sqlWhere AND staff='Y')";
                }
            }
        }
        if ($student) {

                $dataActivities = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $session->get('gibbonPersonID'));
                $sqlActivities = "SELECT gibbonActivity.gibbonActivityID FROM gibbonActivity JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonActivityStudent.gibbonPersonID=:gibbonPersonID AND status='Accepted'";
                $resultActivities = $connection2->prepare($sqlActivities);
                $resultActivities->execute($dataActivities);
            $sqlWhere = '(';
            if ($resultActivities->rowCount() > 0) {
                $i = 0;
                while ($rowActivities = $resultActivities->fetch()) {
                    $dataPosts['activity'.$i] = $rowActivities['gibbonActivityID'];
                    $sqlWhere .= 'id=:activity'.$i.' OR ';
                    $i++;
                }
                $sqlWhere = substr($sqlWhere, 0, -3).')';
                if ($sqlWhere != '(') {
                    $sqlPosts = $sqlPosts." UNION (SELECT gibbonMessenger.*, title, surname, preferredName, category, image_240, concat('Activity: ', gibbonActivity.name) AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonActivity ON (gibbonMessengerTarget.id=gibbonActivity.gibbonActivityID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) WHERE gibbonMessenger.status='Sent' AND gibbonMessengerTarget.type='Activity' AND $dateWhere AND $sqlWhere AND students='Y')";
                }
            }
        }
        if ($parent and !empty($children)) {

                $dataActivities = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'children' => implode(',', $children));
                $sqlActivities = "SELECT gibbonActivity.gibbonActivityID FROM gibbonActivity JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND FIND_IN_SET(gibbonActivityStudent.gibbonPersonID, :children) AND status='Accepted'";
                $resultActivities = $connection2->prepare($sqlActivities);
                $resultActivities->execute($dataActivities);
            $sqlWhere = '(';
            if ($resultActivities->rowCount() > 0) {
                $i = 0;
                while ($rowActivities = $resultActivities->fetch()) {
                    $dataPosts['activityParent'.$i] = $rowActivities['gibbonActivityID'];
                    $sqlWhere .= 'id=:activityParent'.$i.' OR ';
                    $i++;
                }
                $sqlWhere = substr($sqlWhere, 0, -3).')';
                if ($sqlWhere != '(') {
                    $sqlPosts = $sqlPosts." UNION (SELECT gibbonMessenger.*, title, surname, preferredName, category, image_240, concat('Activity: ', gibbonActivity.name) AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) JOIN gibbonActivity ON (gibbonMessengerTarget.id=gibbonActivity.gibbonActivityID) WHERE gibbonMessenger.status='Sent' AND gibbonMessengerTarget.type='Activity' AND $dateWhere AND $sqlWhere AND parents='Y')";
                }
            }
        }

        //Houses
        $dataPosts['gibbonPersonID3'] = $session->get('gibbonPersonID');
        $sqlPosts = $sqlPosts." UNION (SELECT gibbonMessenger.*, gibbonPerson.title, gibbonPerson.surname, gibbonPerson.preferredName, category, gibbonPerson.image_240, concat('Houses: ', gibbonHouse.name) AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) JOIN gibbonPerson AS inHouse ON (gibbonMessengerTarget.id=inHouse.gibbonHouseID) JOIN gibbonHouse ON (gibbonPerson.gibbonHouseID=gibbonHouse.gibbonHouseID) WHERE gibbonMessenger.status='Sent' AND gibbonMessengerTarget.type='Houses' AND $dateWhere AND inHouse.gibbonPersonID=:gibbonPersonID3)";

        //Individuals
        $dataPosts['gibbonPersonID4'] = $session->get('gibbonPersonID');
        $sqlPosts = $sqlPosts." UNION (SELECT gibbonMessenger.*, gibbonPerson.title, gibbonPerson.surname, gibbonPerson.preferredName, category, gibbonPerson.image_240, 'Individual: You' AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) JOIN gibbonPerson AS individual ON (gibbonMessengerTarget.id=individual.gibbonPersonID) WHERE gibbonMessenger.status='Sent' AND gibbonMessengerTarget.type='Individuals' AND $dateWhere AND individual.gibbonPersonID=:gibbonPersonID4)";


        //Attendance
        if ($student) {
            try {
              $dataAttendance=array( "gibbonPersonID" => $session->get('gibbonPersonID'), "selectedDate"=>$date, "gibbonSchoolYearID"=>$session->get("gibbonSchoolYearID"), "nowDate"=>date("Y-m-d") );
              $sqlAttendance="SELECT galp.gibbonAttendanceLogPersonID, galp.type, galp.date FROM gibbonAttendanceLogPerson AS galp JOIN gibbonStudentEnrolment AS gse ON (galp.gibbonPersonID=gse.gibbonPersonID) JOIN gibbonPerson AS gp ON (gse.gibbonPersonID=gp.gibbonPersonID) WHERE gp.status='Full' AND (gp.dateStart IS NULL OR gp.dateStart<=:nowDate) AND (gp.dateEnd IS NULL OR gp.dateEnd>=:nowDate) AND gse.gibbonSchoolYearID=:gibbonSchoolYearID AND galp.date=:selectedDate AND galp.gibbonPersonID=:gibbonPersonID ORDER BY galp.gibbonAttendanceLogPersonID DESC LIMIT 1" ;
              $resultAttendance=$connection2->prepare($sqlAttendance);
              $resultAttendance->execute($dataAttendance);
            }
            catch(\PDOException $e) { }

            if ($resultAttendance->rowCount() > 0) {
                $studentAttendance = $resultAttendance->fetch();
                $dataPosts['attendanceType1'] = $studentAttendance['type'].' '.$date;
                $sqlPosts = $sqlPosts." UNION (SELECT gibbonMessenger.*, gibbonPerson.title, gibbonPerson.surname, gibbonPerson.preferredName, category, gibbonPerson.image_240, concat('Attendance:', gibbonMessengerTarget.id) AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) WHERE gibbonMessenger.status='Sent' AND gibbonMessengerTarget.type='Attendance' AND gibbonMessengerTarget.id=:attendanceType1 AND $dateWhere )";

            }
        }
        if ($parent and !empty($children)) {
            try {
              $dataAttendance=array( "gibbonPersonID" => $session->get('gibbonPersonID'), "selectedDate"=>$date, "gibbonSchoolYearID"=>$session->get("gibbonSchoolYearID"), "nowDate"=>date("Y-m-d"), 'children' => implode(',', $children) );
              $sqlAttendance="SELECT galp.gibbonAttendanceLogPersonID, galp.type, gp.firstName FROM gibbonAttendanceLogPerson AS galp JOIN gibbonStudentEnrolment AS gse ON (galp.gibbonPersonID=gse.gibbonPersonID) JOIN gibbonPerson AS gp ON (gse.gibbonPersonID=gp.gibbonPersonID) WHERE gp.status='Full' AND (gp.dateStart IS NULL OR gp.dateStart<=:nowDate) AND (gp.dateEnd IS NULL OR gp.dateEnd>=:nowDate) AND gse.gibbonSchoolYearID=:gibbonSchoolYearID AND galp.date=:selectedDate AND FIND_IN_SET(galp.gibbonPersonID, :children) ORDER BY galp.gibbonAttendanceLogPersonID DESC LIMIT 1" ;
              $resultAttendance=$connection2->prepare($sqlAttendance);
              $resultAttendance->execute($dataAttendance);
            }
            catch(\PDOException $e) { }

            if ($resultAttendance->rowCount() > 0) {
                $studentAttendance = $resultAttendance->fetch();
                $dataPosts['attendanceType2'] = $studentAttendance['type'].' '.$date;
                $sqlPosts = $sqlPosts." UNION (SELECT gibbonMessenger.*, gibbonPerson.title, gibbonPerson.surname, gibbonPerson.preferredName, category, gibbonPerson.image_240, concat('Attendance:', gibbonMessengerTarget.id, ' for ', '".$studentAttendance['firstName']."') AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) WHERE gibbonMessenger.status='Sent' AND gibbonMessengerTarget.type='Attendance' AND gibbonMessengerTarget.id=:attendanceType2 AND $dateWhere )";

            }
        }

        // Groups
        if ($staff) {
            $dataPosts['gibbonPersonID5'] = $session->get('gibbonPersonID');
            $sqlPosts = $sqlPosts." UNION (SELECT gibbonMessenger.*, title, surname, preferredName, category, image_240, concat(gibbonGroup.name, ' Group') AS source
            FROM gibbonMessenger
            JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID)
            JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID)
            JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID)
            JOIN gibbonGroup ON (gibbonMessengerTarget.id=gibbonGroup.gibbonGroupID)
            JOIN gibbonGroupPerson ON (gibbonGroup.gibbonGroupID=gibbonGroupPerson.gibbonGroupID)
            WHERE gibbonGroupPerson.gibbonPersonID=:gibbonPersonID5
            AND gibbonMessenger.status='Sent' AND gibbonMessengerTarget.type='Group' AND gibbonMessengerTarget.staff='Y'
            AND $dateWhere )";
        }
        if ($student) {
            $dataPosts['gibbonPersonID6'] = $session->get('gibbonPersonID');
            $sqlPosts = $sqlPosts." UNION (SELECT gibbonMessenger.*, title, surname, preferredName, category, image_240, concat(gibbonGroup.name, ' Group') AS source
            FROM gibbonMessenger
            JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID)
            JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID)
            JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID)
            JOIN gibbonGroup ON (gibbonMessengerTarget.id=gibbonGroup.gibbonGroupID)
            JOIN gibbonGroupPerson ON (gibbonGroup.gibbonGroupID=gibbonGroupPerson.gibbonGroupID)
            WHERE gibbonMessenger.status='Sent' AND gibbonGroupPerson.gibbonPersonID=:gibbonPersonID6
            AND gibbonMessengerTarget.type='Group' AND gibbonMessengerTarget.students='Y'
            AND $dateWhere )";
        }
        if ($parent and !empty($children)) {
            $dataPosts['gibbonPersonID7'] = $session->get('gibbonPersonID');
            $dataPosts['children'] = implode(',', $children);
            $sqlPosts = $sqlPosts." UNION (SELECT gibbonMessenger.*, title, surname, preferredName, category, image_240, concat(gibbonGroup.name, ' Group') AS source
            FROM gibbonMessenger
            JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID)
            JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID)
            JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID)
            JOIN gibbonGroup ON (gibbonMessengerTarget.id=gibbonGroup.gibbonGroupID)
            JOIN gibbonGroupPerson ON (gibbonGroup.gibbonGroupID=gibbonGroupPerson.gibbonGroupID)
            WHERE gibbonMessenger.status='Sent' AND (gibbonGroupPerson.gibbonPersonID=:gibbonPersonID7 OR FIND_IN_SET(gibbonGroupPerson.gibbonPersonID, :children))
            AND gibbonMessengerTarget.type='Group' AND gibbonMessengerTarget.parents='Y'
            AND $dateWhere )";
        }

        // Transport
        if ($staff) {
            $dataPosts['gibbonPersonID8'] = $session->get('gibbonPersonID');
            $sqlPosts = $sqlPosts." UNION (SELECT gibbonMessenger.*, gibbonPerson.title, gibbonPerson.surname, gibbonPerson.preferredName, category, gibbonPerson.image_240, concat('Transport ', transportee.transport) AS source FROM gibbonMessenger
            JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID)
            JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID)
            JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID)
            JOIN gibbonPerson as transportee ON (FIND_IN_SET(gibbonMessengerTarget.id, transportee.transport))
            WHERE gibbonMessenger.status='Sent' AND transportee.gibbonPersonID=:gibbonPersonID8
            AND gibbonMessengerTarget.type='Transport' AND gibbonMessengerTarget.staff='Y'
            AND $dateWhere )";
        }
        if ($student) {
            $dataPosts['gibbonPersonID9'] = $session->get('gibbonPersonID');
            $sqlPosts = $sqlPosts." UNION (SELECT gibbonMessenger.*, gibbonPerson.title, gibbonPerson.surname, gibbonPerson.preferredName, category, gibbonPerson.image_240, concat('Transport ', transportee.transport) AS source FROM gibbonMessenger
            JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID)
            JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID)
            JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID)
            JOIN gibbonPerson as transportee ON (FIND_IN_SET(gibbonMessengerTarget.id, transportee.transport))
            WHERE gibbonMessenger.status='Sent' AND transportee.gibbonPersonID=:gibbonPersonID9
            AND gibbonMessengerTarget.type='Transport' AND gibbonMessengerTarget.students='Y'
            AND $dateWhere )";
        }
        if ($parent and !empty($children)) {
            $dataPosts['gibbonPersonID10'] = $session->get('gibbonPersonID');
            $dataPosts['children'] = implode(',', $children);
            $sqlPosts = $sqlPosts." UNION (SELECT gibbonMessenger.*, gibbonPerson.title, gibbonPerson.surname, gibbonPerson.preferredName, category, gibbonPerson.image_240, concat('Transport ', transportee.transport) AS source FROM gibbonMessenger
            JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID)
            JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID)
            JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID)
            JOIN gibbonPerson as transportee ON (FIND_IN_SET(gibbonMessengerTarget.id, transportee.transport))
            WHERE gibbonMessenger.status='Sent' AND (transportee.gibbonPersonID=:gibbonPersonID10 OR FIND_IN_SET(transportee.gibbonPersonID, :children))
            AND gibbonMessengerTarget.type='Transport' AND gibbonMessengerTarget.parents='Y'
            AND $dateWhere )";
        }

        //SPIT OUT RESULTS
        if ($mode == 'result') {
            $resultReturn = array();
            $resultReturn[0] = $dataPosts;
            $resultReturn[1] = $sqlPosts.' ORDER BY messageWallPin DESC, timestamp DESC, gibbonMessengerID, source';

            return serialize($resultReturn);
        } elseif ($mode == 'array') {
            try {
                $sqlPosts = $sqlPosts.' ORDER BY messageWallPin DESC, timestamp DESC, gibbonMessengerID, source';
                $resultPosts = $connection2->prepare($sqlPosts);
                $resultPosts->execute($dataPosts);
            } catch (\PDOException $e) {
            }

            $arrayPosts = $resultPosts->rowCount() > 0 ? $resultPosts->fetchAll() : [];

            $arrayPosts = array_reduce($arrayPosts, function ($group, $item) {
                if (isset($group[$item['gibbonMessengerID']]['source'])) {
                    $item['source'] .= str_replace(':', ', ', strrchr($group[$item['gibbonMessengerID']]['source'], ':'));
                }
                $group[$item['gibbonMessengerID']] = $item;
                return $group;
            }, []);

            return $arrayPosts;
        } else {
            $count = 0;
            try {
                $sqlPosts = $sqlPosts.' ORDER BY messageWallPin DESC, timestamp DESC, gibbonMessengerID, source';
                $resultPosts = $connection2->prepare($sqlPosts);
                $resultPosts->execute($dataPosts);
            } catch (\PDOException $e) {
            }

            if ($resultPosts->rowCount() < 1) {
                $return .= Format::alert(__('There are no records to display.'), 'message');
            } else {
                $output = array();
                $last = '';
                while ($rowPosts = $resultPosts->fetch()) {
                    if ($last == $rowPosts['gibbonMessengerID']) {
                        $output[($count - 1)]['source'] = $output[($count - 1)]['source'].'<br/>'.$rowPosts['source'];
                    } else {
                        $output[$count]['photo'] = $rowPosts['image_240'];
                        $output[$count]['subject'] = $rowPosts['subject'];
                        $output[$count]['details'] = $rowPosts['body'];
                        $output[$count]['author'] = Format::name($rowPosts['title'], $rowPosts['preferredName'], $rowPosts['surname'], $rowPosts['category']);
                        $output[$count]['source'] = $rowPosts['source'];
                        $output[$count]['gibbonMessengerID'] = $rowPosts['gibbonMessengerID'];
                        $output[$count]['gibbonPersonID'] = $rowPosts['gibbonPersonID'];
                        $output[$count]['messageWallPin'] = $rowPosts['messageWallPin'];

                        ++$count;
                        $last = $rowPosts['gibbonMessengerID'];
                    }
                }

                $table = DataTable::create('messages');
                $table->addMetaData('allowHTML', ['details']);
                $table->modifyRows(function($message, $row) {
                    if ($message['messageWallPin'] == "Y") {
                        $row->addClass('selected');
                    }
                    return $row;
                });

                $table->addColumn('sharing', __('Sharing'))
                    ->width('100px')
                    ->addClass('textCenter align-top')
                    ->format(function ($message) {
                        $output = '<a name="' . $message['gibbonMessengerID'] . '"></a>';

                        $output .= Format::userPhoto($message['photo']);
                        $output .= '<br/>';

                        $output .= '<b><u>' . __('Posted By') . '</b></u><br/>';
                        $output .= $message['author'] . '<br/><br/>';

                        $output .= '<b><u>' . __('Shared Via') . '</b></u><br/>';
                        $output .= $message['source'] . '<br/><br/>';

                        if ($message['messageWallPin'] == "Y") {
                            $output .= '<i>' . __('Pinned To Top') . '</i><br/>';
                        }

                        return $output;
                    });

                $table->addColumn('message', __('Message'))
                    ->width('640px')
                    ->addClass('align-top overflow-x-scroll max-w-lg')
                    ->format(function ($message) {
                        $output = '<h3 style="margin-top: 3px">';
                        $output .= $this->validator->sanitizePlainText($message['subject']);
                        $output .= '</h3>';

                        $output .= '</p>';
                        $output .= $this->validator->sanitizeRichText($message['details']);
                        $output .= '</p>';

                        return $output;
                    });

                $return .= $table->render($output);
            }
            if ($mode == 'print') {
                return $return;
            } else {
                return $count;
            }
        }
    }
}
