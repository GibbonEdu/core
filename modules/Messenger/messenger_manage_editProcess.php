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

use Gibbon\Data\Validator;
use Gibbon\Services\Format;
use Gibbon\Domain\Messenger\MessengerGateway;

require_once '../../gibbon.php';

$gibbonMessengerID = $_POST['gibbonMessengerID'] ?? '';
$search = $_GET['search'] ?? '';
$address = $_POST['address'] ?? '';

$URL=$session->get('absoluteURL') . "/index.php?q=/modules/Messenger/messenger_manage_edit.php&sidebar=true&search=$search&gibbonMessengerID=" . $gibbonMessengerID;
$time=time();

if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_manage_edit.php")==FALSE) {
    $URL.="&return=error0";
    header("Location: {$URL}");
} else {
    $highestAction=getHighestGroupedAction($guid, $address, $connection2);
    if ($highestAction == FALSE) {
        $URL.="&return=error0";
        header("Location: {$URL}");
        exit;
    }

    // Check for empty POST. This can happen if attachments go horribly wrong.
    if (empty($_POST)) {
        $URL.="&return=error5";
        header("Location: {$URL}");
        exit;
    }

    // Proceed!
    // Validate Inputs
    $validator = $container->get(Validator::class);
    $_POST = $validator->sanitize($_POST, ['body' => 'HTML']);

    $messengerGateway = $container->get(MessengerGateway::class);

    $data = [
        'status'            => $_POST['status'] ?? 'Sent',
        'messageWall'       => $_POST['messageWall'] ?? 'N',
        'messageWallPin'    => $_POST['messageWallPin'] ?? 'N',
        'messageWall_date1' => !empty($_POST['date1']) ? Format::dateConvert($_POST['date1']) : null,
        'messageWall_date2' => !empty($_POST['date2']) ? Format::dateConvert($_POST['date2']) : null,
        'messageWall_date3' => !empty($_POST['date3']) ? Format::dateConvert($_POST['date3']) : null,
        'subject'           => $_POST['subject'] ?? '',
        'body'              => $_POST['body'] ?? '',
        'confidential'      => $_POST['confidential'] ?? 'N',
        'timestamp'         => date('Y-m-d H:i:s'),
    ];

    if ($data['status'] != 'Sent') {
        $data += [
            'email'             => $_POST['email'] ?? 'N',
            'sms'               => $_POST['sms'] ?? 'N',
            'emailReceipt'      => $_POST['emailReceipt'] ?? 'N',
            'emailReceiptText'  => $_POST['emailReceiptText'] ?? '',
        ];
    }

    $data['messageWallPin'] = ($data['messageWall'] == 'Y' && isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_manage.php', 'Manage Messages_all')) ? $data['messageWallPin'] : 'N';

    if (empty($data['subject']) || empty($data['body'])) {
        $URL.="&return=error3";
        header("Location: {$URL}");
        exit;
    }

    //Write to database
    $updated = $messengerGateway->update($gibbonMessengerID, $data);

    if (!$updated) {
        $URL.="&return=error2";
        header("Location: {$URL}");
        exit();
    }

    // TARGETS
    $partialFail = false;

    try {
        $dataRemove=array("gibbonMessengerID"=>$gibbonMessengerID);
        $sqlRemove="DELETE FROM gibbonMessengerTarget WHERE gibbonMessengerID=:gibbonMessengerID";
        $resultRemove=$connection2->prepare($sqlRemove);
        $resultRemove->execute($dataRemove);
    }
    catch(PDOException $e) {
        $partialFail = true;
    }

    //Roles
    if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_role")) {
        if (!empty($_POST["role"]) && $_POST["role"]=="Y") {
            $choices=$_POST["roles"];
            if ($choices!="") {
                foreach ($choices as $t) {
                    try {
                        $data=array("gibbonMessengerID"=>$gibbonMessengerID, "t"=>$t);
                        $sql="INSERT INTO gibbonMessengerTarget SET gibbonMessengerID=:gibbonMessengerID, type='Role', id=:t";
                        $result=$connection2->prepare($sql);
                        $result->execute($data);
                    }
                    catch(PDOException $e) {
                        $partialFail = true;
                    }
                }
            }
        }
    }


    //Role Categories
    if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_role") || isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_postQuickWall.php")) {
        if (!empty($_POST['roleCategory']) && $_POST['roleCategory'] == 'Y') {
            $choices=$_POST['roleCategories'] ?? '';
            if ($choices!="") {
                foreach ($choices as $t) {
                    try {
                        $data=array("gibbonMessengerID"=>$gibbonMessengerID, "t"=>$t);
                        $sql="INSERT INTO gibbonMessengerTarget SET gibbonMessengerID=:gibbonMessengerID, type='Role Category', id=:t";
                        $result=$connection2->prepare($sql);
                        $result->execute($data);
                    }
                    catch(PDOException $e) {
                        $partialFail = true;
                    }
                }
            }
        }
    }

    //Year Groups
    if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_yearGroups_any")) {
        if ($_POST["yearGroup"]=="Y") {
            $staff=$_POST["yearGroupsStaff"];
            $students=$_POST["yearGroupsStudents"];
            $parents="N";
            if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_yearGroups_parents")) {
                $parents=$_POST["yearGroupsParents"];
            }
            $choices=$_POST["yearGroups"];
            if ($choices!="") {
                foreach ($choices as $t) {
                    try {
                        $data=array("gibbonMessengerID"=>$gibbonMessengerID, "t"=>$t, "staff"=>$staff, "students"=>$students, "parents"=>$parents);
                        $sql="INSERT INTO gibbonMessengerTarget SET gibbonMessengerID=:gibbonMessengerID, type='Year Group', id=:t, staff=:staff, students=:students, parents=:parents";
                        $result=$connection2->prepare($sql);
                        $result->execute($data);
                    }
                    catch(PDOException $e) {
                        $partialFail = true;
                    }
                }
            }
        }
    }

    //Form Groups
    if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_formGroups_my") OR isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_formGroups_any")) {
        if ($_POST["formGroup"]=="Y") {
            $staff=$_POST["formGroupsStaff"];
            $students=$_POST["formGroupsStudents"];
            $parents="N";
            if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_formGroups_parents")) {
                $parents=$_POST["formGroupsParents"];
            }
            $choices=$_POST["formGroups"];
            if ($choices!="") {
                foreach ($choices as $t) {
                    try {
                        $data=array("gibbonMessengerID"=>$gibbonMessengerID, "t"=>$t, "staff"=>$staff, "students"=>$students, "parents"=>$parents);
                        $sql="INSERT INTO gibbonMessengerTarget SET gibbonMessengerID=:gibbonMessengerID, type='Form Group', id=:t, staff=:staff, students=:students, parents=:parents";
                        $result=$connection2->prepare($sql);
                        $result->execute($data);
                    }
                    catch(PDOException $e) {
                        $partialFail = true;
                    }
                }
            }
        }
    }

    //Course Groups
    if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_courses_my") OR isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_courses_any")) {
        if ($_POST["course"]=="Y") {
            $staff=$_POST["coursesStaff"];
            $students=$_POST["coursesStudents"];
            $parents="N";
            if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_courses_parents")) {
                $parents=$_POST["coursesParents"];
            }
            $choices=$_POST["courses"];
            if ($choices!="") {
                foreach ($choices as $t) {
                    try {
                        $data=array("gibbonMessengerID"=>$gibbonMessengerID, "id"=>$t, "staff"=>$staff, "students"=>$students, "parents"=>$parents);
                        $sql="INSERT INTO gibbonMessengerTarget SET gibbonMessengerID=:gibbonMessengerID, type='Course', id=:id, staff=:staff, students=:students, parents=:parents";
                        $result=$connection2->prepare($sql);
                        $result->execute($data);
                    }
                    catch(PDOException $e) {
                        $partialFail = true;
                    }
                }
            }
        }
    }

    //Class Groups
    if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_classes_my") OR isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_classes_any")) {
        if ($_POST["class"]=="Y") {
            $staff=$_POST["classesStaff"];
            $students=$_POST["classesStudents"];
            $parents="N";
            if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_classes_parents")) {
                $parents=$_POST["classesParents"];
            }
            $choices=$_POST["classes"];
            if ($choices!="") {
                foreach ($choices as $t) {
                    try {
                        $data=array("gibbonMessengerID"=>$gibbonMessengerID, "id"=>$t, "staff"=>$staff, "students"=>$students, "parents"=>$parents);
                        $sql="INSERT INTO gibbonMessengerTarget SET gibbonMessengerID=:gibbonMessengerID, type='Class', id=:id, staff=:staff, students=:students, parents=:parents";
                        $result=$connection2->prepare($sql);
                        $result->execute($data);
                    }
                    catch(PDOException $e) {
                        $partialFail = true;
                    }
                }
            }
        }
    }

    //Activity Groups
    if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_activities_my") OR isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_activities_any")) {
        if ($_POST["activity"]=="Y") {
            $staff=$_POST["activitiesStaff"];
            $students=$_POST["activitiesStudents"];
            $parents="N";
            if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_activities_parents")) {
                $parents=$_POST["activitiesParents"];
            }
            $choices=$_POST["activities"];
            if ($choices!="") {
                foreach ($choices as $t) {
                    try {
                        $data=array("gibbonMessengerID"=>$gibbonMessengerID, "id"=>$t, "staff"=>$staff, "students"=>$students, "parents"=>$parents);
                        $sql="INSERT INTO gibbonMessengerTarget SET gibbonMessengerID=:gibbonMessengerID, type='Activity', id=:id, staff=:staff, students=:students, parents=:parents";
                        $result=$connection2->prepare($sql);
                        $result->execute($data);
                    }
                    catch(PDOException $e) {
                        $partialFail = true;
                    }
                }
            }
        }
    }

    //Applicants
    if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_applicants")) {
        if ($_POST["applicants"]=="Y") {
            $choices=$_POST["applicantList"];
            if ($choices!="") {
                foreach ($choices as $t) {
                    try {
                        $data=array("gibbonMessengerID"=>$gibbonMessengerID, "id"=>$t);
                        $sql="INSERT INTO gibbonMessengerTarget SET gibbonMessengerID=:gibbonMessengerID, type='Applicants', id=:id";
                        $result=$connection2->prepare($sql);
                        $result->execute($data);
                    }
                    catch(PDOException $e) {
                        $partialFail = true;
                    }
                }
            }
        }
    }

    //Houses
    if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_houses_all") OR isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_houses_my")) {
        if ($_POST["houses"]=="Y") {
            $choices=$_POST["houseList"];
            if ($choices!="") {
                foreach ($choices as $t) {
                    try {
                        $data=array("gibbonMessengerID"=>$gibbonMessengerID, "id"=>$t);
                        $sql="INSERT INTO gibbonMessengerTarget SET gibbonMessengerID=:gibbonMessengerID, type='Houses', id=:id";
                        $result=$connection2->prepare($sql);
                        $result->execute($data);
                    }
                    catch(PDOException $e) {
                        $partialFail = true;
                    }
                }
            }
        }
    }

    //Transport
    if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_transport_any")) {
        if ($_POST["transport"]=="Y") {
                    $staff=$_POST["transportStaff"];
                    $students=$_POST["transportStudents"];
                    $parents="N";
        if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_transport_parents")) {
            $parents=$_POST["transportParents"];
        }
        $choices=$_POST["transports"];
        if ($choices!="") {
            foreach ($choices as $t) {
            try {
                $data=array("gibbonMessengerID"=>$gibbonMessengerID, "id"=>$t, "students"=>$students, "parents"=>$parents, "staff"=>$staff);
                $sql="INSERT INTO gibbonMessengerTarget SET gibbonMessengerID=:gibbonMessengerID, type='Transport', id=:id, students=:students, staff=:staff, parents=:parents";
                $result=$connection2->prepare($sql);
                $result->execute($data);
            }
            catch(PDOException $e) {
                $partialFail = true;
            }
            }
        }
        }
    }

    //Attendance
    if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_attendance")) {
        if ($_POST["attendance"]=="Y") {
        $choices=$_POST["attendanceStatus"];
        $students=$_POST["attendanceStudents"];
        $parents=$_POST["attendanceParents"];
        if ($choices!="") {
            foreach ($choices as $t) {
            try {
                $data=array("gibbonMessengerID"=>$gibbonMessengerID, "id"=>$t, "students"=>$students, "parents"=>$parents);
                $sql="INSERT INTO gibbonMessengerTarget SET gibbonMessengerID=:gibbonMessengerID, type='Attendance', id=:id, students=:students, parents=:parents";
                $result=$connection2->prepare($sql);
                $result->execute($data);
            }
            catch(PDOException $e) {
                $partialFail = true;
            }
            }
        }
        }
    }

    //Groups
    if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_groups_any") || isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_groups_my")) {
        if ($_POST["group"] == "Y") {
            $staff = $_POST["groupsStaff"];
            $students = $_POST["groupsStudents"];
            $parents = "N";
            if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_groups_parents")) {
                $parents=$_POST["groupsParents"];
            }
            $choices=$_POST["groups"];
            if ($choices!="") {
                foreach ($choices as $t) {
                    try {
                        $data=array("gibbonMessengerID"=>$gibbonMessengerID, "t"=>$t, "staff"=>$staff, "students"=>$students, "parents"=>$parents);
                        $sql="INSERT INTO gibbonMessengerTarget SET gibbonMessengerID=:gibbonMessengerID, type='Group', id=:t, staff=:staff, students=:students, parents=:parents";
                        $result=$connection2->prepare($sql);
                        $result->execute($data);
                    }
                    catch(PDOException $e) {
                        $partialFail = true;
                    }
                }
            }
        }
    }

    //Individuals
    if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_individuals")) {
        if ($_POST["individuals"]=="Y") {
            $choices=$_POST["individualList"];
            if ($choices!="") {
                foreach ($choices as $t) {
                    try {
                        $data=array("gibbonMessengerID"=>$gibbonMessengerID, "id"=>$t);
                        $sql="INSERT INTO gibbonMessengerTarget SET gibbonMessengerID=:gibbonMessengerID, type='Individuals', id=:id";
                        $result=$connection2->prepare($sql);
                        $result->execute($data);
                    }
                    catch(PDOException $e) {
                        $partialFail = true;
                    }
                }
            }
        }
    }

    $URL .= $partialFail
        ? "&return=error4"
        : "&return=success0";
    header("Location: {$URL}");
}
