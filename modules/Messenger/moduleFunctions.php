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

//Helps builds report array for setting gibbonMessengerReceipt
function reportAdd($report, $emailReceipt, $gibbonPersonID, $targetType, $targetID, $contactType, $contactDetail)
{
    if ($contactDetail != '' AND is_null($contactDetail) == false) {
        $unique = true;
        foreach ($report as $reportEntry) {
            if ($reportEntry[4] == $contactDetail)
                $unique = false;
        }

        if ($unique) {
            $count = count($report);
            $report[$count][0] = $gibbonPersonID;
            $report[$count][1] = $targetType;
            $report[$count][2] = $targetID;
            $report[$count][3] = $contactType;
            $report[$count][4] = $contactDetail;
            if ($contactType == 'Email' and $emailReceipt == 'Y') {
                $report[$count][5] = randomPassword(40);
            }
            else {
                $report[$count][5] = null;
            }
        }
    }

    return $report;
}

//Build an email signautre for the specified user
function getSignature($guid, $connection2, $gibbonPersonID)
{
    $return = false;

    try {
        $data = array('gibbonPersonID' => $gibbonPersonID);
        $sql = 'SELECT gibbonStaff.*, surname, preferredName, initials FROM gibbonStaff JOIN gibbonPerson ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) { }

    if ($result->rowCount() == 1) {
        $row = $result->fetch();

        $return = '<br/><br/>----<br/>';
        $return .= "<span style='font-weight: bold; color: #447CAA'>".formatName('', $row['preferredName'], $row['surname'], 'Student').'</span><br/>';
        $return .= "<span style='font-style: italic'>";
        if ($row['jobTitle'] != '') {
            $return .= $row['jobTitle'].'<br/>';
        }
        $return .= $_SESSION[$guid]['organisationName'].'<br/>';
        $return .= '</span>';
        $return .= '----<br/>';
    }

    return $return;
}

//Mode may be "print" (return table of messages), "count" (return message count) or "result" (return database query result)
function getMessages($guid, $connection2, $mode = '', $date = '')
{
    $return = '';
    $dataPosts = array();

    if ($date == '') {
        $date = date('Y-m-d');
    }
    if ($mode != 'print' and $mode != 'count' and $mode != 'result') {
        $mode = 'print';
    }

    //Work out all role categories this user has, ignoring "Other"
    $roles = $_SESSION[$guid]['gibbonRoleIDAll'];
    $roleCategory = '';
    $staff = false;
    $student = false;
    $parent = false;
    for ($i = 0; $i < count($roles); ++$i) {
        $roleCategory = getRoleCategory($roles[$i][0], $connection2);
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
        $children = '(';
        try {
            $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
            $sql = "SELECT * FROM gibbonFamilyAdult WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
        }
        while ($row = $result->fetch()) {
            try {
                $dataChild = array('gibbonFamilyID' => $row['gibbonFamilyID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                $sqlChild = "SELECT * FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY surname, preferredName ";
                $resultChild = $connection2->prepare($sqlChild);
                $resultChild->execute($dataChild);
            } catch (PDOException $e) {
            }
            while ($rowChild = $resultChild->fetch()) {
                $children .= 'gibbonPersonID='.$rowChild['gibbonPersonID'].' OR ';
            }
        }
        if ($children != '(') {
            $children = substr($children, 0, -4).')';
        } else {
            $children = false;
        }
    }

    //My roles
    $roles = $_SESSION[$guid]['gibbonRoleIDAll'];
    $sqlWhere = '(';
    if (count($roles) > 0) {
        for ($i = 0; $i < count($roles); ++$i) {
            $dataPosts['role'.$roles[$i][0]] = $roles[$i][0];
            $sqlWhere .= 'id=:role'.$roles[$i][0].' OR ';
        }
        $sqlWhere = substr($sqlWhere, 0, -3).')';
    }
    if ($sqlWhere != '(') {
        $dataPosts['date1'] = $date;
        $dataPosts['date2'] = $date;
        $dataPosts['date3'] = $date;
        $sqlPosts = "(SELECT gibbonMessenger.*, title, surname, preferredName, authorRole.category AS category, image_240, concat('Role: ', gibbonRole.name) AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole AS authorRole ON (gibbonPerson.gibbonRoleIDPrimary=authorRole.gibbonRoleID) JOIN gibbonRole ON (gibbonMessengerTarget.id=gibbonRole.gibbonRoleID) WHERE gibbonMessengerTarget.type='Role' AND (messageWall_date1=:date1 OR messageWall_date2=:date2 OR messageWall_date3=:date3) AND $sqlWhere)";
    }

    //My role categories
    try {
        $dataRoleCategory = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
        $sqlRoleCategory = "SELECT DISTINCT category FROM gibbonRole JOIN gibbonPerson ON (gibbonPerson.gibbonRoleIDAll LIKE CONCAT('%', gibbonRole.gibbonRoleID, '%')) WHERE gibbonPersonID=:gibbonPersonID";
        $resultRoleCategory = $connection2->prepare($sqlRoleCategory);
        $resultRoleCategory->execute($dataRoleCategory);
    } catch (PDOException $e) {
        echo $e->getMessage();
    }
    $sqlWhere = '(';
    if ($resultRoleCategory->rowCount() > 0) {
        $i = 0;
        while ($rowRoleCategory = $resultRoleCategory->fetch()) {
            $dataPosts['role'.$rowRoleCategory['category']] = $rowRoleCategory['category'];
            $sqlWhere .= 'id=:role'.$rowRoleCategory['category'].' OR ';
            ++$i;
        }
        $sqlWhere = substr($sqlWhere, 0, -3).')';
    }
    if ($sqlWhere != '(') {
        $dataPosts['date1'] = $date;
        $dataPosts['date2'] = $date;
        $dataPosts['date3'] = $date;
        $sqlPosts = $sqlPosts." UNION (SELECT DISTINCT gibbonMessenger.*, title, surname, preferredName, authorRole.category AS category, image_240, concat('Role Category: ', gibbonRole.category) AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole AS authorRole ON (gibbonPerson.gibbonRoleIDPrimary=authorRole.gibbonRoleID) JOIN gibbonRole ON (gibbonMessengerTarget.id=gibbonRole.category) WHERE gibbonMessengerTarget.type='Role Category' AND (messageWall_date1=:date1 OR messageWall_date2=:date2 OR messageWall_date3=:date3) AND $sqlWhere)";
    }

    //My year groups
    if ($staff) {
        $dataPosts['date4'] = $date;
        $dataPosts['date5'] = $date;
        $dataPosts['date6'] = $date;
        $sqlPosts = $sqlPosts." UNION (SELECT gibbonMessenger.*, title, surname, preferredName, category, image_240, 'Year Groups' AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) WHERE gibbonMessengerTarget.type='Year Group' AND (messageWall_date1=:date4 OR messageWall_date2=:date5 OR messageWall_date3=:date6) AND staff='Y')";
    }
    if ($student) {
        $dataPosts['date7'] = $date;
        $dataPosts['date8'] = $date;
        $dataPosts['date9'] = $date;
        $dataPosts['gibbonSchoolYearID1'] = $_SESSION[$guid]['gibbonSchoolYearID'];
        $dataPosts['gibbonPersonID1'] = $_SESSION[$guid]['gibbonPersonID'];
        $sqlPosts = $sqlPosts." UNION (SELECT gibbonMessenger.*, title, surname, preferredName, category, image_240, concat('Year Group ', gibbonYearGroup.nameShort) AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) JOIN gibbonStudentEnrolment ON (gibbonMessengerTarget.id=gibbonStudentEnrolment.gibbonYearGroupID) JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) WHERE gibbonStudentEnrolment.gibbonPersonID=:gibbonPersonID1 AND gibbonMessengerTarget.type='Year Group' AND (messageWall_date1=:date7 OR messageWall_date2=:date8 OR messageWall_date3=:date9) AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID1 AND students='Y')";
    }
    if ($parent and $children != false) {
        $dataPosts['date10'] = $date;
        $dataPosts['date11'] = $date;
        $dataPosts['date12'] = $date;
        $dataPosts['gibbonSchoolYearID2'] = $_SESSION[$guid]['gibbonSchoolYearID'];
        $sqlPosts = $sqlPosts." UNION (SELECT gibbonMessenger.*, title, surname, preferredName, category, image_240, concat('Year Group: ', gibbonYearGroup.nameShort) AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) JOIN gibbonStudentEnrolment ON (gibbonMessengerTarget.id=gibbonStudentEnrolment.gibbonYearGroupID) JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) WHERE ".preg_replace('/gibbonPersonID/', 'gibbonStudentEnrolment.gibbonPersonID', $children)." AND gibbonMessengerTarget.type='Year Group' AND (messageWall_date1=:date10 OR messageWall_date2=:date11 OR messageWall_date3=:date12) AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID2 AND parents='Y')";
    }

    //My roll groups
    if ($staff) {
        $sqlWhere = '(';
        try {
            $dataRollGroup = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonIDTutor' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonPersonIDTutor2' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonPersonIDTutor3' => $_SESSION[$guid]['gibbonPersonID']);
            $sqlRollGroup = 'SELECT * FROM gibbonRollGroup WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND (gibbonPersonIDTutor=:gibbonPersonIDTutor OR gibbonPersonIDTutor2=:gibbonPersonIDTutor2 OR gibbonPersonIDTutor3=:gibbonPersonIDTutor3)';
            $resultRollGroup = $connection2->prepare($sqlRollGroup);
            $resultRollGroup->execute($dataRollGroup);
        } catch (PDOException $e) {
        }
        if ($resultRollGroup->rowCount() > 0) {
            while ($rowRollGroup = $resultRollGroup->fetch()) {
                $dataPosts['roll'.$rowRollGroup['gibbonRollGroupID']] = $rowRollGroup['gibbonRollGroupID'];
                $sqlWhere .= 'id=:roll'.$rowRollGroup['gibbonRollGroupID'].' OR ';
            }
            $sqlWhere = substr($sqlWhere, 0, -3).')';
            if ($sqlWhere != '(') {
                $dataPosts['date13'] = $date;
                $dataPosts['date14'] = $date;
                $dataPosts['date15'] = $date;
                $sqlPosts = $sqlPosts." UNION (SELECT gibbonMessenger.*, title, surname, preferredName, category, image_240, concat('Roll Group: ', gibbonRollGroup.nameShort) AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) JOIN gibbonRollGroup ON (gibbonMessengerTarget.id=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonMessengerTarget.type='Roll Group' AND (messageWall_date1=:date13 OR messageWall_date2=:date14 OR messageWall_date3=:date15) AND $sqlWhere AND staff='Y')";
            }
        }
    }
    if ($student) {
        $dataPosts['date16'] = $date;
        $dataPosts['date17'] = $date;
        $dataPosts['date18'] = $date;
        $dataPosts['gibbonSchoolYearID3'] = $_SESSION[$guid]['gibbonSchoolYearID'];
        $dataPosts['gibbonPersonID2'] = $_SESSION[$guid]['gibbonPersonID'];
        $sqlPosts = $sqlPosts." UNION (SELECT gibbonMessenger.*, title, surname, preferredName, category, image_240, concat('Roll Group: ', gibbonRollGroup.nameShort) AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) JOIN gibbonStudentEnrolment ON (gibbonMessengerTarget.id=gibbonStudentEnrolment.gibbonRollGroupID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonStudentEnrolment.gibbonPersonID=:gibbonPersonID2 AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID3 AND gibbonMessengerTarget.type='Roll Group' AND (messageWall_date1=:date16 OR messageWall_date2=:date17 OR messageWall_date3=:date18) AND students='Y')";
    }
    if ($parent and $children != false) {
        $dataPosts['date19'] = $date;
        $dataPosts['date20'] = $date;
        $dataPosts['date21'] = $date;
        $dataPosts['gibbonSchoolYearID4'] = $_SESSION[$guid]['gibbonSchoolYearID'];
        $sqlPosts = $sqlPosts." UNION (SELECT gibbonMessenger.*, title, surname, preferredName, category, image_240, concat('Roll Group: ', gibbonRollGroup.nameShort) AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) JOIN gibbonStudentEnrolment ON (gibbonMessengerTarget.id=gibbonStudentEnrolment.gibbonRollGroupID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE ".preg_replace('/gibbonPersonID/', 'gibbonStudentEnrolment.gibbonPersonID', $children)." AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID4 AND gibbonMessengerTarget.type='Roll Group' AND (messageWall_date1=:date19 OR messageWall_date2=:date20 OR messageWall_date3=:date21) AND parents='Y')";
    }

    //My courses
    //First check for any course, then do specific parent check
    try {
        $dataClasses = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
        $sqlClasses = "SELECT DISTINCT gibbonCourseClass.gibbonCourseID FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND NOT role LIKE '%- Left'";
        $resultClasses = $connection2->prepare($sqlClasses);
        $resultClasses->execute($dataClasses);
    } catch (PDOException $e) {
    }
    $sqlWhere = '(';
    if ($resultClasses->rowCount() > 0) {
        while ($rowClasses = $resultClasses->fetch()) {
            $dataPosts['course'.$rowClasses['gibbonCourseID']] = $rowClasses['gibbonCourseID'];
            $sqlWhere .= 'id=:course'.$rowClasses['gibbonCourseID'].' OR ';
        }
        $sqlWhere = substr($sqlWhere, 0, -3).')';
        if ($sqlWhere != '(') {
            if ($staff) {
                $dataPosts['date22'] = $date;
                $dataPosts['date23'] = $date;
                $dataPosts['date24'] = $date;
                $sqlPosts = $sqlPosts." UNION (SELECT gibbonMessenger.*, title, surname, preferredName, category, image_240, concat('Course: ', gibbonCourse.nameShort) AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) JOIN gibbonCourse ON (gibbonMessengerTarget.id=gibbonCourse.gibbonCourseID) WHERE gibbonMessengerTarget.type='Course' AND (messageWall_date1=:date22 OR messageWall_date2=:date23 OR messageWall_date3=:date24) AND $sqlWhere AND staff='Y')";
            }
            if ($student) {
                $dataPosts['date25'] = $date;
                $dataPosts['date26'] = $date;
                $dataPosts['date27'] = $date;
                $sqlPosts = $sqlPosts." UNION (SELECT gibbonMessenger.*, title, surname, preferredName, category, image_240, concat('Course: ', gibbonCourse.nameShort) AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) JOIN gibbonCourse ON (gibbonMessengerTarget.id=gibbonCourse.gibbonCourseID) WHERE gibbonMessengerTarget.type='Course' AND (messageWall_date1=:date25 OR messageWall_date2=:date26 OR messageWall_date3=:date27) AND $sqlWhere AND students='Y')";
            }
        }
    }
    if ($parent and $children != false) {
        try {
            $dataClasses = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
            $sqlClasses = 'SELECT DISTINCT gibbonCourseClass.gibbonCourseID FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND '.preg_replace('/gibbonPersonID/', 'gibbonCourseClassPerson.gibbonPersonID', $children)." AND NOT role LIKE '%- Left'";
            $resultClasses = $connection2->prepare($sqlClasses);
            $resultClasses->execute($dataClasses);
        } catch (PDOException $e) {
        }
        $sqlWhere = '(';
        if ($resultClasses->rowCount() > 0) {
            while ($rowClasses = $resultClasses->fetch()) {
                $dataPosts['course'.$rowClasses['gibbonCourseID']] = $rowClasses['gibbonCourseID'];
                $sqlWhere .= 'id=:course'.$rowClasses['gibbonCourseID'].' OR ';
            }
            $sqlWhere = substr($sqlWhere, 0, -3).')';
            if ($sqlWhere != '(') {
                $dataPosts['date28'] = $date;
                $dataPosts['date29'] = $date;
                $dataPosts['date30'] = $date;
                $sqlPosts = $sqlPosts." UNION (SELECT gibbonMessenger.*, title, surname, preferredName, category, image_240, concat('Course: ', gibbonCourse.nameShort) AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) JOIN gibbonCourse ON (gibbonMessengerTarget.id=gibbonCourse.gibbonCourseID) WHERE gibbonMessengerTarget.type='Course' AND (messageWall_date1=:date28 OR messageWall_date2=:date29 OR messageWall_date3=:date30) AND $sqlWhere AND parents='Y')";
            }
        }
    }

    //My classes
    //First check for any role, then do specific parent check
    try {
        $dataClasses = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
        $sqlClasses = "SELECT gibbonCourseClass.gibbonCourseClassID FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND NOT role LIKE '%- Left'";
        $resultClasses = $connection2->prepare($sqlClasses);
        $resultClasses->execute($dataClasses);
    } catch (PDOException $e) {
    }
    $sqlWhere = '(';
    if ($resultClasses->rowCount() > 0) {
        while ($rowClasses = $resultClasses->fetch()) {
            $dataPosts['class'.$rowClasses['gibbonCourseClassID']] = $rowClasses['gibbonCourseClassID'];
            $sqlWhere .= 'id=:class'.$rowClasses['gibbonCourseClassID'].' OR ';
        }
        $sqlWhere = substr($sqlWhere, 0, -3).')';
        if ($sqlWhere != '(') {
            if ($staff) {
                $dataPosts['date31'] = $date;
                $dataPosts['date32'] = $date;
                $dataPosts['date33'] = $date;
                $sqlPosts = $sqlPosts." UNION (SELECT gibbonMessenger.*, title, surname, preferredName, category, image_240, concat('Class: ', gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) JOIN gibbonCourseClass ON (gibbonMessengerTarget.id=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonMessengerTarget.type='Class' AND (messageWall_date1=:date31 OR messageWall_date2=:date32 OR messageWall_date3=:date33) AND $sqlWhere AND staff='Y')";
            }
            if ($student) {
                $dataPosts['date34'] = $date;
                $dataPosts['date35'] = $date;
                $dataPosts['date36'] = $date;
                $sqlPosts = $sqlPosts." UNION (SELECT gibbonMessenger.*, title, surname, preferredName, category, image_240, concat('Class: ', gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) JOIN gibbonCourseClass ON (gibbonMessengerTarget.id=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonMessengerTarget.type='Class' AND (messageWall_date1=:date34 OR messageWall_date2=:date35 OR messageWall_date3=:date36) AND $sqlWhere AND students='Y')";
            }
        }
    }
    if ($parent and $children != false) {
        try {
            $dataClasses = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
            $sqlClasses = 'SELECT gibbonCourseClass.gibbonCourseClassID FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND '.preg_replace('/gibbonPersonID/', 'gibbonCourseClassPerson.gibbonPersonID', $children)." AND NOT role LIKE '%- Left'";
            $resultClasses = $connection2->prepare($sqlClasses);
            $resultClasses->execute($dataClasses);
        } catch (PDOException $e) {
        }
        $sqlWhere = '(';
        if ($resultClasses->rowCount() > 0) {
            while ($rowClasses = $resultClasses->fetch()) {
                $dataPosts['class'.$rowClasses['gibbonCourseClassID']] = $rowClasses['gibbonCourseClassID'];
                $sqlWhere .= 'id=:class'.$rowClasses['gibbonCourseClassID'].' OR ';
            }
            $sqlWhere = substr($sqlWhere, 0, -3).')';
            if ($sqlWhere != '(') {
                $dataPosts['date37'] = $date;
                $dataPosts['date38'] = $date;
                $dataPosts['date39'] = $date;
                $sqlPosts = $sqlPosts." UNION (SELECT gibbonMessenger.*, title, surname, preferredName, category, image_240, concat('Class: ', gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) JOIN gibbonCourseClass ON (gibbonMessengerTarget.id=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonMessengerTarget.type='Class' AND (messageWall_date1=:date37 OR messageWall_date2=:date38 OR messageWall_date3=:date39) AND $sqlWhere AND parents='Y')";
            }
        }
    }

    //My activities
    if ($staff) {
        try {
            $dataActivities = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
            $sqlActivities = 'SELECT gibbonActivity.gibbonActivityID FROM gibbonActivity JOIN gibbonActivityStaff ON (gibbonActivityStaff.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonActivityStaff.gibbonPersonID=:gibbonPersonID';
            $resultActivities = $connection2->prepare($sqlActivities);
            $resultActivities->execute($dataActivities);
        } catch (PDOException $e) {
        }
        $sqlWhere = '(';
        if ($resultActivities->rowCount() > 0) {
            while ($rowActivities = $resultActivities->fetch()) {
                $dataPosts['activity'.$rowActivities['gibbonActivityID']] = $rowActivities['gibbonActivityID'];
                $sqlWhere .= 'id=:activity'.$rowActivities['gibbonActivityID'].' OR ';
            }
            $sqlWhere = substr($sqlWhere, 0, -3).')';
            if ($sqlWhere != '(') {
                $dataPosts['date40'] = $date;
                $dataPosts['date41'] = $date;
                $dataPosts['date42'] = $date;
                $sqlPosts = $sqlPosts." UNION (SELECT gibbonMessenger.*, title, surname, preferredName, category, image_240, concat('Activity: ', gibbonActivity.name) AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) JOIN gibbonActivity ON (gibbonMessengerTarget.id=gibbonActivity.gibbonActivityID) WHERE gibbonMessengerTarget.type='Activity' AND (messageWall_date1=:date40 OR messageWall_date2=:date41 OR messageWall_date3=:date42) AND $sqlWhere AND staff='Y')";
            }
        }
    }
    if ($student) {
        try {
            $dataActivities = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
            $sqlActivities = "SELECT gibbonActivity.gibbonActivityID FROM gibbonActivity JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonActivityStudent.gibbonPersonID=:gibbonPersonID AND status='Accepted'";
            $resultActivities = $connection2->prepare($sqlActivities);
            $resultActivities->execute($dataActivities);
        } catch (PDOException $e) {
        }
        $sqlWhere = '(';
        if ($resultActivities->rowCount() > 0) {
            while ($rowActivities = $resultActivities->fetch()) {
                $dataPosts['activity'.$rowActivities['gibbonActivityID']] = $rowActivities['gibbonActivityID'];
                $sqlWhere .= 'id=:activity'.$rowActivities['gibbonActivityID'].' OR ';
            }
            $sqlWhere = substr($sqlWhere, 0, -3).')';
            if ($sqlWhere != '(') {
                $dataPosts['date43'] = $date;
                $dataPosts['date44'] = $date;
                $dataPosts['date45'] = $date;
                $sqlPosts = $sqlPosts." UNION (SELECT gibbonMessenger.*, title, surname, preferredName, category, image_240, concat('Activity: ', gibbonActivity.name) AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonActivity ON (gibbonMessengerTarget.id=gibbonActivity.gibbonActivityID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) WHERE gibbonMessengerTarget.type='Activity' AND (messageWall_date1=:date43 OR messageWall_date2=:date44 OR messageWall_date3=:date45) AND $sqlWhere AND students='Y')";
            }
        }
    }
    if ($parent and $children != false) {
        try {
            $dataActivities = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
            $sqlActivities = 'SELECT gibbonActivity.gibbonActivityID FROM gibbonActivity JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND '.preg_replace('/gibbonPersonID/', 'gibbonActivityStudent.gibbonPersonID', $children)." AND status='Accepted'";
            $resultActivities = $connection2->prepare($sqlActivities);
            $resultActivities->execute($dataActivities);
        } catch (PDOException $e) {
        }
        $sqlWhere = '(';
        if ($resultActivities->rowCount() > 0) {
            while ($rowActivities = $resultActivities->fetch()) {
                $dataPosts['activity'.$rowActivities['gibbonActivityID']] = $rowActivities['gibbonActivityID'];
                $sqlWhere .= 'id=:activity'.$rowActivities['gibbonActivityID'].' OR ';
            }
            $sqlWhere = substr($sqlWhere, 0, -3).')';
            if ($sqlWhere != '(') {
                $dataPosts['date46'] = $date;
                $dataPosts['date47'] = $date;
                $dataPosts['date48'] = $date;
                $sqlPosts = $sqlPosts." UNION (SELECT gibbonMessenger.*, title, surname, preferredName, category, image_240, concat('Activity: ', gibbonActivity.name) AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) JOIN gibbonActivity ON (gibbonMessengerTarget.id=gibbonActivity.gibbonActivityID) WHERE gibbonMessengerTarget.type='Activity' AND (messageWall_date1=:date46 OR messageWall_date2=:date47 OR messageWall_date3=:date48) AND $sqlWhere AND parents='Y')";
            }
        }
    }

    //Houses
    $dataPosts['date49'] = $date;
    $dataPosts['date50'] = $date;
    $dataPosts['date51'] = $date;
    $dataPosts['gibbonPersonID3'] = $_SESSION[$guid]['gibbonPersonID'];
    $sqlPosts = $sqlPosts." UNION (SELECT gibbonMessenger.*, gibbonPerson.title, gibbonPerson.surname, gibbonPerson.preferredName, category, gibbonPerson.image_240, concat('Houses: ', gibbonHouse.name) AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) JOIN gibbonPerson AS inHouse ON (gibbonMessengerTarget.id=inHouse.gibbonHouseID) JOIN gibbonHouse ON (gibbonPerson.gibbonHouseID=gibbonHouse.gibbonHouseID)WHERE gibbonMessengerTarget.type='Houses' AND (messageWall_date1=:date49 OR messageWall_date2=:date50 OR messageWall_date3=:date51) AND inHouse.gibbonPersonID=:gibbonPersonID3)";

    //Individuals
    $dataPosts['date52'] = $date;
    $dataPosts['date53'] = $date;
    $dataPosts['date54'] = $date;
    $dataPosts['gibbonPersonID4'] = $_SESSION[$guid]['gibbonPersonID'];
    $sqlPosts = $sqlPosts." UNION (SELECT gibbonMessenger.*, gibbonPerson.title, gibbonPerson.surname, gibbonPerson.preferredName, category, gibbonPerson.image_240, 'Individual: You' AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) JOIN gibbonPerson AS individual ON (gibbonMessengerTarget.id=individual.gibbonPersonID) WHERE gibbonMessengerTarget.type='Individuals' AND (messageWall_date1=:date52 OR messageWall_date2=:date53 OR messageWall_date3=:date54) AND individual.gibbonPersonID=:gibbonPersonID4)";


    //Attendance
    if ($student) {
        try {
          $dataAttendance=array( "gibbonPersonID" => $_SESSION[$guid]['gibbonPersonID'], "selectedDate"=>$date, "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "nowDate"=>date("Y-m-d") );
          $sqlAttendance="SELECT galp.gibbonAttendanceLogPersonID, galp.type, galp.date FROM gibbonAttendanceLogPerson AS galp JOIN gibbonStudentEnrolment AS gse ON (galp.gibbonPersonID=gse.gibbonPersonID) JOIN gibbonPerson AS gp ON (gse.gibbonPersonID=gp.gibbonPersonID) WHERE gp.status='Full' AND (gp.dateStart IS NULL OR gp.dateStart<=:nowDate) AND (gp.dateEnd IS NULL OR gp.dateEnd>=:nowDate) AND gse.gibbonSchoolYearID=:gibbonSchoolYearID AND galp.date=:selectedDate AND galp.gibbonPersonID=:gibbonPersonID ORDER BY galp.gibbonAttendanceLogPersonID DESC LIMIT 1" ;
          $resultAttendance=$connection2->prepare($sqlAttendance);
          $resultAttendance->execute($dataAttendance);
        }
        catch(PDOException $e) { }

        if ($resultAttendance->rowCount() > 0) {
            $studentAttendance = $resultAttendance->fetch();
            $dataPosts['date55'] = $date;
            $dataPosts['date56'] = $date;
            $dataPosts['date57'] = $date;
            $dataPosts['attendanceType1'] = $studentAttendance['type'].' '.$date;
            $sqlPosts = $sqlPosts." UNION (SELECT gibbonMessenger.*, gibbonPerson.title, gibbonPerson.surname, gibbonPerson.preferredName, category, gibbonPerson.image_240, concat('Attendance:', gibbonMessengerTarget.id) AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) WHERE gibbonMessengerTarget.type='Attendance' AND gibbonMessengerTarget.id=:attendanceType1 AND (messageWall_date1=:date55 OR messageWall_date2=:date56 OR messageWall_date3=:date57) )";

        }
    }
    if ($parent and $children != false) {
        try {
          $dataAttendance=array( "gibbonPersonID" => $_SESSION[$guid]['gibbonPersonID'], "selectedDate"=>$date, "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "nowDate"=>date("Y-m-d") );
          $sqlAttendance="SELECT galp.gibbonAttendanceLogPersonID, galp.type, gp.firstName FROM gibbonAttendanceLogPerson AS galp JOIN gibbonStudentEnrolment AS gse ON (galp.gibbonPersonID=gse.gibbonPersonID) JOIN gibbonPerson AS gp ON (gse.gibbonPersonID=gp.gibbonPersonID) WHERE gp.status='Full' AND (gp.dateStart IS NULL OR gp.dateStart<=:nowDate) AND (gp.dateEnd IS NULL OR gp.dateEnd>=:nowDate) AND gse.gibbonSchoolYearID=:gibbonSchoolYearID AND galp.date=:selectedDate AND ".preg_replace('/gibbonPersonID/', 'galp.gibbonPersonID', $children)." ORDER BY galp.gibbonAttendanceLogPersonID DESC LIMIT 1" ;
          $resultAttendance=$connection2->prepare($sqlAttendance);
          $resultAttendance->execute($dataAttendance);
        }
        catch(PDOException $e) { }

        if ($resultAttendance->rowCount() > 0) {
            $studentAttendance = $resultAttendance->fetch();
            $dataPosts['date57'] = $date;
            $dataPosts['date58'] = $date;
            $dataPosts['date59'] = $date;
            $dataPosts['attendanceType2'] = $studentAttendance['type'].' '.$date;
            $sqlPosts = $sqlPosts." UNION (SELECT gibbonMessenger.*, gibbonPerson.title, gibbonPerson.surname, gibbonPerson.preferredName, category, gibbonPerson.image_240, concat('Attendance:', gibbonMessengerTarget.id, ' for ', '".$studentAttendance['firstName']."') AS source FROM gibbonMessenger JOIN gibbonMessengerTarget ON (gibbonMessengerTarget.gibbonMessengerID=gibbonMessenger.gibbonMessengerID) JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) WHERE gibbonMessengerTarget.type='Attendance' AND gibbonMessengerTarget.id=:attendanceType2 AND (messageWall_date1=:date57 OR messageWall_date2=:date58 OR messageWall_date3=:date59) )";

        }
    }

    //SPIT OUT RESULTS
    if ($mode == 'result') {
        $resultReturn = array();
        $resultReturn[0] = $dataPosts;
        $resultReturn[1] = $sqlPosts.' ORDER BY subject, gibbonMessengerID, source';

        return serialize($resultReturn);
    } else {
        $count = 0;
        try {
            $sqlPosts = $sqlPosts.' ORDER BY subject, gibbonMessengerID, source';
            $resultPosts = $connection2->prepare($sqlPosts);
            $resultPosts->execute($dataPosts);
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        if ($resultPosts->rowCount() < 1) {
            $return .= "<div class='warning'>";
            $return .= __($guid, 'There are no records to display.');
            $return .= '</div>';
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
                    $output[$count]['author'] = formatName($rowPosts['title'], $rowPosts['preferredName'], $rowPosts['surname'], $rowPosts['category']);
                    $output[$count]['source'] = $rowPosts['source'];
                    $output[$count]['gibbonMessengerID'] = $rowPosts['gibbonMessengerID'];
                    $output[$count]['gibbonPersonID'] = $rowPosts['gibbonPersonID'];

                    ++$count;
                    $last = $rowPosts['gibbonMessengerID'];
                }
            }

            $return .= "<table cellspacing='0' style='margin-top: 10px'>";
            $return .= '<tr>';
            $return .= "<th style='text-align: center'>";
            $return .= __($guid, 'Sharing');
            $return .= '</th>';
            $return .= '<th>';
            $return .= __($guid, 'Message');
            $return .= '</th>';
            $return .= '</tr>';
            $rowCount = 0;
            $rowNum = 'odd';
            for ($i = 0; $i < count($output); ++$i) {
                if ($rowCount % 2 == 0) {
                    $rowNum = 'even';
                } else {
                    $rowNum = 'odd';
                }
                ++$rowCount;
                $return .= "<tr class=$rowNum>";
                $return .= "<td style='text-align: center; vertical-align: top; padding-bottom: 10px; padding-top: 10px; border-top: 1px solid #666; width: 100px'>";
                $return .= "<a name='".$output[$i]['gibbonMessengerID']."'></a>";
                $return .= getUserPhoto($guid, $output[$i]['photo'], 75).'<br/>';

				//DEAL WITH LIKES
                if ($output[$i]['gibbonPersonID'] == $_SESSION[$guid]['gibbonPersonID']) {
                    $likesGiven = countLikesByContextAndRecipient($connection2, 'Messenger', 'gibbonMessengerID', $output[$i]['gibbonMessengerID'], $output[$i]['gibbonPersonID']);
                    if ($likesGiven == 1) {
                        $return .= $likesGiven.'x '.__($guid, 'Like').'<br/><br/>';
                    } else {
                        $return .= $likesGiven.'x '.__($guid, 'Likes').'<br/><br/>';
                    }
                } else {
                    $likesGivenByMe = countLikesByContextAndGiver($connection2, 'Messenger', 'gibbonMessengerID', $output[$i]['gibbonMessengerID'], $_SESSION[$guid]['gibbonPersonID'], $output[$i]['gibbonPersonID']);
                    $comment = addSlashes($output[$i]['subject']);
                    $return .= "<div id='star".$output[$i]['gibbonMessengerID']."'>";
                    $return .= '<script type="text/javascript">';
                    $return .= '$(document).ready(function(){';
                    $return .= '$("#starAdd'.$output[$i]['gibbonMessengerID'].'").click(function(){';
                    $return .= '$("#star'.$output[$i]['gibbonMessengerID'].'").load("'.$_SESSION[$guid]['absoluteURL'].'/modules/Messenger/messageWall_view_starAjax.php",{"gibbonPersonID": "'.$output[$i]['gibbonPersonID'].'", "gibbonMessengerID": "'.$output[$i]['gibbonMessengerID'].'", "mode": "add", "comment": "'.$comment.'"});';
                    $return .= '});';
                    $return .= '$("#starRemove'.$output[$i]['gibbonMessengerID'].'").click(function(){';
                    $return .= '$("#star'.$output[$i]['gibbonMessengerID'].'").load("'.$_SESSION[$guid]['absoluteURL'].'/modules/Messenger/messageWall_view_starAjax.php",{"gibbonPersonID": "'.$output[$i]['gibbonPersonID'].'", "gibbonMessengerID": "'.$output[$i]['gibbonMessengerID'].'", "mode": "remove", "comment": "'.$comment.'"});';
                    $return .= '});';
                    $return .= '});';
                    $return .= '</script>';
                    if ($likesGivenByMe != 1) {
                        $return .= "<a id='starAdd".$output[$i]['gibbonMessengerID']."' onclick='return false;' href='#'><img style='margin-top: -8px; margin-bottom: 5px' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/like_off.png'></a>";
                    } else {
                        $return .= "<a id='starRemove".$output[$i]['gibbonMessengerID']."' onclick='return false;' href='#'><img style='margin-top: -8px; margin-bottom: 5px' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/like_on.png'></a>";
                    }
                    $return .= '</div>';
                }

                $return .= '<b><u>Posted By</b></u><br/>';
                $return .= $output[$i]['author'].'<br/><br/>';

                $return .= '<b><u>Shared Via</b></u><br/>';
                $return .= $output[$i]['source'].'<br/><br/>';
                $return .= '</td>';
                $return .= "<td style='border-left: none; vertical-align: top; padding-bottom: 10px; padding-top: 10px; border-top: 1px solid #666; width: 640px'>";
                $return .= "<h3 style='margin-top: 3px'>";
                $return .= $output[$i]['subject'];
                $return .= '</h3>';
                $return .= '</p>';
                $return .= $output[$i]['details'];
                $return .= '</p>';
                $return .= '</td>';
                $return .= '</tr>';
            }
            $return .= '</table>';
        }
        if ($mode == 'print') {
            return $return;
        } else {
            return $count;
        }
    }
}
