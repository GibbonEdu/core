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

namespace Gibbon\Module\Messenger;

use Gibbon\Services\Format;
use Gibbon\Contracts\Services\Session;
use Gibbon\Contracts\Database\Connection;
use Gibbon\Data\PasswordPolicy;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\System\LogGateway;
use Gibbon\Domain\User\RoleGateway;

/**
 * MessageTargets
 *
 * @version v25
 * @since   v25
 */
class MessageTargets
{
    protected $report;

    /**
     * Role gateway
     *
     * @var RoleGateway
     */
    protected $roleGateway;

    public function __construct(
        Session $session,
        Connection $db,
        SettingGateway $settingGateway,
        LogGateway $logGateway,
        RoleGateway $roleGateway
    )
    {
        $this->session = $session;
        $this->db = $db;
        $this->settingGateway = $settingGateway;
        $this->logGateway = $logGateway;
        $this->roleGateway = $roleGateway;
    }

    public function createMessageTargets($gibbonMessengerID, &$partialFail = false)
    {
        $guid = $this->session->get('guid');
        $connection2 = $this->db->getConnection();

        //Roles
        if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_role")) {
            if (!empty($_POST["role"]) && $_POST["role"]=="Y") {
                $choices = $_POST["roles"] ?? [];
                if (!empty($choices)) {
                    foreach ($choices as $t) {
                        try {
                            $dataTarget=array("gibbonMessengerID"=>$gibbonMessengerID, "t"=>$t);
                            $sqlTarget="INSERT INTO gibbonMessengerTarget SET gibbonMessengerID=:gibbonMessengerID, type='Role', id=:t";
                            $result=$connection2->prepare($sqlTarget);
                            $result->execute($dataTarget);
                        }
                        catch(\PDOException $e) {
                            $partialFail = true;
                        }
                    }
                }
            }
        }


        //Role Categories
        if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_role") || isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_postQuickWall.php")) {
            if (!empty($_POST['roleCategory']) && $_POST['roleCategory'] == 'Y') {
                $choices=$_POST['roleCategories'] ?? [];
                if (!empty($choices)) {
                    foreach ($choices as $t) {
                        try {
                            $dataTarget=array("gibbonMessengerID"=>$gibbonMessengerID, "t"=>$t);
                            $sqlTarget="INSERT INTO gibbonMessengerTarget SET gibbonMessengerID=:gibbonMessengerID, type='Role Category', id=:t";
                            $result=$connection2->prepare($sqlTarget);
                            $result->execute($dataTarget);
                        }
                        catch(\PDOException $e) {
                            $partialFail = true;
                        }
                    }
                }
            }
        }

        //Year Groups
        if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_yearGroups_any")) {
            if ($_POST["yearGroup"]=="Y") {
                $staff = $_POST["yearGroupsStaff"] ?? [];
                $students = $_POST["yearGroupsStudents"] ?? [];
                $parents="N";
                if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_yearGroups_parents")) {
                    $parents = $_POST["yearGroupsParents"] ?? [];
                }
                $choices = $_POST["yearGroups"] ?? [];
                if (!empty($choices)) {
                    foreach ($choices as $t) {
                        try {
                            $dataTarget=array("gibbonMessengerID"=>$gibbonMessengerID, "t"=>$t, "staff"=>$staff, "students"=>$students, "parents"=>$parents);
                            $sqlTarget="INSERT INTO gibbonMessengerTarget SET gibbonMessengerID=:gibbonMessengerID, type='Year Group', id=:t, staff=:staff, students=:students, parents=:parents";
                            $result=$connection2->prepare($sqlTarget);
                            $result->execute($dataTarget);
                        }
                        catch(\PDOException $e) {
                            $partialFail = true;
                        }
                    }
                }
            }
        }

        //Form Groups
        if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_formGroups_my") OR isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_formGroups_any")) {
            if ($_POST["formGroup"]=="Y") {
                $staff = $_POST["formGroupsStaff"] ?? [];
                $students = $_POST["formGroupsStudents"] ?? [];
                $parents="N";
                if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_formGroups_parents")) {
                    $parents = $_POST["formGroupsParents"] ?? [];
                }
                $choices = $_POST["formGroups"] ?? [];
                if (!empty($choices)) {
                    foreach ($choices as $t) {
                        try {
                            $dataTarget=array("gibbonMessengerID"=>$gibbonMessengerID, "t"=>$t, "staff"=>$staff, "students"=>$students, "parents"=>$parents);
                            $sqlTarget="INSERT INTO gibbonMessengerTarget SET gibbonMessengerID=:gibbonMessengerID, type='Form Group', id=:t, staff=:staff, students=:students, parents=:parents";
                            $result=$connection2->prepare($sqlTarget);
                            $result->execute($dataTarget);
                        }
                        catch(\PDOException $e) {
                            $partialFail = true;
                        }
                    }
                }
            }
        }

        //Course Groups
        if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_courses_my") OR isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_courses_any")) {
            if ($_POST["course"]=="Y") {
                $staff = $_POST["coursesStaff"] ?? [];
                $students = $_POST["coursesStudents"] ?? [];
                $parents="N";
                if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_courses_parents")) {
                    $parents = $_POST["coursesParents"] ?? [];
                }
                $choices = $_POST["courses"] ?? [];
                if (!empty($choices)) {
                    foreach ($choices as $t) {
                        try {
                            $dataTarget=array("gibbonMessengerID"=>$gibbonMessengerID, "id"=>$t, "staff"=>$staff, "students"=>$students, "parents"=>$parents);
                            $sqlTarget="INSERT INTO gibbonMessengerTarget SET gibbonMessengerID=:gibbonMessengerID, type='Course', id=:id, staff=:staff, students=:students, parents=:parents";
                            $result=$connection2->prepare($sqlTarget);
                            $result->execute($dataTarget);
                        }
                        catch(\PDOException $e) {
                            $partialFail = true;
                        }
                    }
                }
            }
        }

        //Class Groups
        if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_classes_my") OR isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_classes_any")) {
            if ($_POST["class"]=="Y") {
                $staff = $_POST["classesStaff"] ?? [];
                $students = $_POST["classesStudents"] ?? [];
                $parents="N";
                if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_classes_parents")) {
                    $parents = $_POST["classesParents"] ?? [];
                }
                $choices = $_POST["classes"] ?? [];
                if (!empty($choices)) {
                    foreach ($choices as $t) {
                        try {
                            $dataTarget=array("gibbonMessengerID"=>$gibbonMessengerID, "id"=>$t, "staff"=>$staff, "students"=>$students, "parents"=>$parents);
                            $sqlTarget="INSERT INTO gibbonMessengerTarget SET gibbonMessengerID=:gibbonMessengerID, type='Class', id=:id, staff=:staff, students=:students, parents=:parents";
                            $result=$connection2->prepare($sqlTarget);
                            $result->execute($dataTarget);
                        }
                        catch(\PDOException $e) {
                            $partialFail = true;
                        }
                    }
                }
            }
        }

        //Activity Groups
        if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_activities_my") OR isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_activities_any")) {
            if ($_POST["activity"]=="Y") {
                $staff = $_POST["activitiesStaff"] ?? [];
                $students = $_POST["activitiesStudents"] ?? [];
                $parents="N";
                if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_activities_parents")) {
                    $parents = $_POST["activitiesParents"] ?? [];
                }
                $choices = $_POST["activities"] ?? [];
                if (!empty($choices)) {
                    foreach ($choices as $t) {
                        try {
                            $dataTarget=array("gibbonMessengerID"=>$gibbonMessengerID, "id"=>$t, "staff"=>$staff, "students"=>$students, "parents"=>$parents);
                            $sqlTarget="INSERT INTO gibbonMessengerTarget SET gibbonMessengerID=:gibbonMessengerID, type='Activity', id=:id, staff=:staff, students=:students, parents=:parents";
                            $result=$connection2->prepare($sqlTarget);
                            $result->execute($dataTarget);
                        }
                        catch(\PDOException $e) {
                            $partialFail = true;
                        }
                    }
                }
            }
        }

        //Applicants
        if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_applicants")) {
            if ($_POST["applicants"]=="Y") {
                $students = $_POST["applicantsStudents"] ;
                $parents = $_POST["applicantsParents"] ;
                $choices = $_POST["applicantList"] ?? [];
                if (!empty($choices)) {
                    foreach ($choices as $t) {
                        try {
                            $dataTarget=array("gibbonMessengerID"=>$gibbonMessengerID, "id"=>$t);
                            $sqlTarget="INSERT INTO gibbonMessengerTarget SET gibbonMessengerID=:gibbonMessengerID, type='Applicants', id=:id, students=:students, parents=:parents";
                            $result=$connection2->prepare($sqlTarget);
                            $result->execute($dataTarget);
                        }
                        catch(\PDOException $e) {
                            $partialFail = true;
                        }
                    }
                }
            }
        }

        //Houses
        if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_houses_all") OR isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_houses_my")) {
            if ($_POST["houses"]=="Y") {
                $choices = $_POST["houseList"] ?? [];
                if (!empty($choices)) {
                    foreach ($choices as $t) {
                        try {
                            $dataTarget=array("gibbonMessengerID"=>$gibbonMessengerID, "id"=>$t);
                            $sqlTarget="INSERT INTO gibbonMessengerTarget SET gibbonMessengerID=:gibbonMessengerID, type='Houses', id=:id";
                            $result=$connection2->prepare($sqlTarget);
                            $result->execute($dataTarget);
                        }
                        catch(\PDOException $e) {
                            $partialFail = true;
                        }
                    }
                }
            }
        }

        //Transport
        if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_transport_any")) {
            if ($_POST["transport"]=="Y") {
                        $staff = $_POST["transportStaff"] ?? [];
                        $students = $_POST["transportStudents"] ?? [];
                        $parents="N";
            if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_transport_parents")) {
                $parents = $_POST["transportParents"] ?? [];
            }
            $choices = $_POST["transports"] ?? [];
            if (!empty($choices)) {
                foreach ($choices as $t) {
                try {
                    $dataTarget=array("gibbonMessengerID"=>$gibbonMessengerID, "id"=>$t, "students"=>$students, "parents"=>$parents, "staff"=>$staff);
                    $sqlTarget="INSERT INTO gibbonMessengerTarget SET gibbonMessengerID=:gibbonMessengerID, type='Transport', id=:id, students=:students, staff=:staff, parents=:parents";
                    $result=$connection2->prepare($sqlTarget);
                    $result->execute($dataTarget);
                }
                catch(\PDOException $e) {
                    $partialFail = true;
                }
                }
            }
            }
        }

        //Attendance
        if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_attendance")) {
            if ($_POST["attendance"]=="Y") {
            $choices = $_POST["attendanceStatus"] ?? [];
            $students = $_POST["attendanceStudents"] ?? [];
            $parents = $_POST["attendanceParents"] ?? [];
            if (!empty($choices)) {
                foreach ($choices as $t) {
                try {
                    $dataTarget=array("gibbonMessengerID"=>$gibbonMessengerID, "id"=>$t, "students"=>$students, "parents"=>$parents);
                    $sqlTarget="INSERT INTO gibbonMessengerTarget SET gibbonMessengerID=:gibbonMessengerID, type='Attendance', id=:id, students=:students, parents=:parents";
                    $result=$connection2->prepare($sqlTarget);
                    $result->execute($dataTarget);
                }
                catch(\PDOException $e) {
                    $partialFail = true;
                }
                }
            }
            }
        }

        //Groups
        if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_groups_any") || isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_groups_my")) {
            if ($_POST["group"] == "Y") {
                $staff = $_POST["groupsStaff"] ?? [];
                $students = $_POST["groupsStudents"] ?? [];
                $parents = "N";
                if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_groups_parents")) {
                    $parents = $_POST["groupsParents"] ?? [];
                }
                $choices = $_POST["groups"] ?? [];
                if (!empty($choices)) {
                    foreach ($choices as $t) {
                        try {
                            $dataTarget=array("gibbonMessengerID"=>$gibbonMessengerID, "t"=>$t, "staff"=>$staff, "students"=>$students, "parents"=>$parents);
                            $sqlTarget="INSERT INTO gibbonMessengerTarget SET gibbonMessengerID=:gibbonMessengerID, type='Group', id=:t, staff=:staff, students=:students, parents=:parents";
                            $result=$connection2->prepare($sqlTarget);
                            $result->execute($dataTarget);
                        }
                        catch(\PDOException $e) {
                            $partialFail = true;
                        }
                    }
                }
            }
        }

        //Individuals
        if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_individuals")) {
            if ($_POST["individuals"]=="Y") {
                $choices = $_POST["individualList"] ?? [];
                if (!empty($choices)) {
                    foreach ($choices as $t) {
                        try {
                            $dataTarget=array("gibbonMessengerID"=>$gibbonMessengerID, "id"=>$t);
                            $sqlTarget="INSERT INTO gibbonMessengerTarget SET gibbonMessengerID=:gibbonMessengerID, type='Individuals', id=:id";
                            $result=$connection2->prepare($sqlTarget);
                            $result->execute($dataTarget);
                        }
                        catch(\PDOException $e) {
                            $partialFail = true;
                        }
                    }
                }
            }
        }
    }

    public function createMessageRecipientsFromTargets($gibbonMessengerID, $data, &$partialFail = false)
    {
        $guid = $this->session->get('guid');
        $connection2 = $this->db->getConnection();

        //TARGETS
        $AI = $gibbonMessengerID;
        $sms = $data['sms'];
        $email = $data['email'];
        $emailReceipt = $data['emailReceipt'];
        $this->report = [];

        //Get country code
        $countryCode="" ;
        $country = $this->settingGateway->getSettingByScope("System", "country") ;
        $countryCodeTemp = '';
        try {
            $dataCountry=array("printable_name"=>$country);
            $sqlCountry="SELECT iddCountryCode FROM gibbonCountry WHERE printable_name=:printable_name" ;
            $resultCountry=$connection2->prepare($sqlCountry);
            $resultCountry->execute($dataCountry);
        }
        catch(\PDOException $e) { }
        if ($resultCountry->rowCount()==1) {
            $rowCountry=$resultCountry->fetch() ;
            $countryCode=$rowCountry["iddCountryCode"] ;
        }

        //Roles
        if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_role")) {
            if ($_POST["role"]=="Y") {
                $choices=$_POST["roles"] ?? [];
                if (!empty($choices)) {
                    foreach ($choices as $t) {
                        try {
                            $data=array("AI"=>$AI, "t"=>$t);
                            $sql="INSERT INTO gibbonMessengerTarget SET gibbonMessengerID=:AI, type='Role', id=:t" ;
                            $result=$connection2->prepare($sql);
                            $result->execute($data);
                        }
                        catch(\PDOException $e) {
                            $partialFail=TRUE;
                        }

                        //Get email addresses
                        $category = $this->roleGateway->getRoleCategory($t);
                        $gibbonRoleID = str_pad(intval($t), 3, '0', STR_PAD_LEFT);
                        if ($email=="Y") {
                            if ($category=="Parent") {
                                try {
                                    $dataEmail=array('gibbonRoleID'=>$gibbonRoleID);
                                    $sqlEmail="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT email='' AND FIND_IN_SET(:gibbonRoleID, gibbonPerson.gibbonRoleIDAll) AND status='Full' AND contactEmail='Y'" ;
                                    $resultEmail=$connection2->prepare($sqlEmail);
                                    $resultEmail->execute($dataEmail);

                                }
                                catch(\PDOException $e) { }
                                while ($rowEmail=$resultEmail->fetch()) {
                                    $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Role', $t, 'Email', $rowEmail["email"]);
                                }
                            }
                            else {
                                try {
                                    $dataEmail=array('gibbonRoleID'=>$gibbonRoleID);
                                    $sqlEmail="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE NOT email='' AND FIND_IN_SET(:gibbonRoleID, gibbonPerson.gibbonRoleIDAll) AND status='Full' AND (dateStart IS NULL OR dateStart<=CURRENT_DATE) AND (dateEnd IS NULL OR dateEnd>=CURRENT_DATE)" ;
                                    $resultEmail=$connection2->prepare($sqlEmail);
                                    $resultEmail->execute($dataEmail);
                                }
                                catch(\PDOException $e) { }
                                while ($rowEmail=$resultEmail->fetch()) {
                                    $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Role', $t, 'Email', $rowEmail["email"]);
                                }
                            }
                        }
                        if ($sms=="Y" AND $countryCode!="") {
                            if ($category=="Parent") {
                                try {
                                    $dataEmail=array('gibbonRoleID'=>$gibbonRoleID);
                                    $sqlEmail="(SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone1='' AND phone1Type='Mobile' AND FIND_IN_SET(:gibbonRoleID, gibbonPerson.gibbonRoleIDAll) AND status='Full' AND contactSMS='Y')" ;
                                    $sqlEmail.=" UNION (SELECT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone2='' AND phone2Type='Mobile' AND FIND_IN_SET(:gibbonRoleID, gibbonPerson.gibbonRoleIDAll) AND status='Full' AND contactSMS='Y')" ;
                                    $sqlEmail.=" UNION (SELECT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone3='' AND phone3Type='Mobile' AND FIND_IN_SET(:gibbonRoleID, gibbonPerson.gibbonRoleIDAll) AND status='Full' AND contactSMS='Y')" ;
                                    $sqlEmail.=" UNION (SELECT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone4='' AND phone4Type='Mobile' AND FIND_IN_SET(:gibbonRoleID, gibbonPerson.gibbonRoleIDAll) AND status='Full' AND contactSMS='Y')" ;
                                    $resultEmail=$connection2->prepare($sqlEmail);
                                    $resultEmail->execute($dataEmail);
                                }
                                catch(\PDOException $e) { }
                                while ($rowEmail=$resultEmail->fetch()) {
                                    $countryCodeTemp = $countryCode;
                                    if ($rowEmail["countryCode"]=="")
                                        $countryCodeTemp = $rowEmail["countryCode"];
                                    $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Role', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
                                }
                            }
                            else {
                                try {
                                    $dataEmail=array('gibbonRoleID'=>$gibbonRoleID);
                                    $sqlEmail="(SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE NOT phone1='' AND phone1Type='Mobile' AND FIND_IN_SET(:gibbonRoleID, gibbonPerson.gibbonRoleIDAll) AND status='Full' AND (dateStart IS NULL OR dateStart<=CURRENT_DATE) AND (dateEnd IS NULL OR dateEnd>=CURRENT_DATE))" ;
                                    $sqlEmail.=" UNION (SELECT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE NOT phone2='' AND phone2Type='Mobile' AND FIND_IN_SET(:gibbonRoleID, gibbonPerson.gibbonRoleIDAll) AND status='Full' AND (dateStart IS NULL OR dateStart<=CURRENT_DATE) AND (dateEnd IS NULL OR dateEnd>=CURRENT_DATE))" ;
                                    $sqlEmail.=" UNION (SELECT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE NOT phone3='' AND phone3Type='Mobile' AND FIND_IN_SET(:gibbonRoleID, gibbonPerson.gibbonRoleIDAll) AND status='Full' AND (dateStart IS NULL OR dateStart<=CURRENT_DATE) AND (dateEnd IS NULL OR dateEnd>=CURRENT_DATE))" ;
                                    $sqlEmail.=" UNION (SELECT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE NOT phone4='' AND phone4Type='Mobile' AND FIND_IN_SET(:gibbonRoleID, gibbonPerson.gibbonRoleIDAll) AND status='Full' AND (dateStart IS NULL OR dateStart<=CURRENT_DATE) AND (dateEnd IS NULL OR dateEnd>=CURRENT_DATE))" ;
                                    $resultEmail=$connection2->prepare($sqlEmail);
                                    $resultEmail->execute($dataEmail);
                                }
                                catch(\PDOException $e) { }
                                while ($rowEmail=$resultEmail->fetch()) {
                                    $countryCodeTemp = $countryCode;
                                    if ($rowEmail["countryCode"]=="")
                                        $countryCodeTemp = $rowEmail["countryCode"];
                                    $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Role', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
                                }
                            }
                        }
                    }
                }
            }
        }

        //Role Categories
        if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_role")) {
            if ($_POST["roleCategory"]=="Y") {
                $choices=$_POST["roleCategories"] ?? [];
                if (!empty($choices)) {
                    foreach ($choices as $t) {
                        try {
                            $data=array("AI"=>$AI, "t"=>$t);
                            $sql="INSERT INTO gibbonMessengerTarget SET gibbonMessengerID=:AI, type='Role Category', id=:t" ;
                            $result=$connection2->prepare($sql);
                            $result->execute($data);
                        }
                        catch(\PDOException $e) {
                            $partialFail=TRUE;
                        }
                        //Get email addresses
                        if ($email=="Y") {
                            if ($t=="Parent") {
                                try {
                                    $dataEmail=array("category"=>$t);
                                    $sqlEmail="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (FIND_IN_SET(gibbonRole.gibbonRoleID, gibbonPerson.gibbonRoleIDAll)) WHERE NOT email='' AND category=:category AND status='Full' AND contactEmail='Y' AND (dateStart IS NULL OR dateStart<=CURRENT_DATE) AND (dateEnd IS NULL OR dateEnd>=CURRENT_DATE)" ;
                                    $resultEmail=$connection2->prepare($sqlEmail);
                                    $resultEmail->execute($dataEmail);
                                }
                                catch(\PDOException $e) { }
                                while ($rowEmail=$resultEmail->fetch()) {
                                    $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Role Category', $t, 'Email', $rowEmail["email"]);
                                }
                            }
                            else {
                                try {
                                    $dataEmail=array("category"=>$t);
                                    $sqlEmail="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonRole ON (FIND_IN_SET(gibbonRole.gibbonRoleID, gibbonPerson.gibbonRoleIDAll)) WHERE NOT email='' AND category=:category AND status='Full' AND (dateStart IS NULL OR dateStart<=CURRENT_DATE) AND (dateEnd IS NULL OR dateEnd>=CURRENT_DATE)" ;
                                    $resultEmail=$connection2->prepare($sqlEmail);
                                    $resultEmail->execute($dataEmail);
                                }
                                catch(\PDOException $e) { }
                                while ($rowEmail=$resultEmail->fetch()) {
                                    $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Role Category', $t, 'Email', $rowEmail["email"]);
                                }
                            }
                        }
                        if ($sms=="Y" AND $countryCode!="") {
                            if ($t=="Parent") {
                                try {
                                    $dataEmail=array("category"=>$t);
                                    $sqlEmail="(SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (FIND_IN_SET(gibbonRole.gibbonRoleID, gibbonPerson.gibbonRoleIDAll)) WHERE NOT phone1='' AND phone1Type='Mobile' AND category=:category AND status='Full' AND contactSMS='Y')" ;
                                    $sqlEmail.=" UNION (SELECT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (FIND_IN_SET(gibbonRole.gibbonRoleID, gibbonPerson.gibbonRoleIDAll)) WHERE NOT phone2='' AND phone2Type='Mobile' AND category=:category AND status='Full' AND contactSMS='Y')" ;
                                    $sqlEmail.=" UNION (SELECT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (FIND_IN_SET(gibbonRole.gibbonRoleID, gibbonPerson.gibbonRoleIDAll)) WHERE NOT phone3='' AND phone3Type='Mobile' AND category=:category AND status='Full' AND contactSMS='Y')" ;
                                    $sqlEmail.=" UNION (SELECT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (FIND_IN_SET(gibbonRole.gibbonRoleID, gibbonPerson.gibbonRoleIDAll)) WHERE NOT phone4='' AND phone4Type='Mobile' AND category=:category AND status='Full' AND contactSMS='Y')" ;
                                    $resultEmail=$connection2->prepare($sqlEmail);
                                    $resultEmail->execute($dataEmail);
                                }
                                catch(\PDOException $e) {}
                                while ($rowEmail=$resultEmail->fetch()) {
                                    $countryCodeTemp = $countryCode;
                                    if ($rowEmail["countryCode"]=="")
                                        $countryCodeTemp = $rowEmail["countryCode"];
                                    $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Role Category', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
                                }
                            }
                            else {
                                try {
                                    $dataEmail=array("category"=>$t);
                                    $sqlEmail="(SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonRole ON (FIND_IN_SET(gibbonRole.gibbonRoleID, gibbonPerson.gibbonRoleIDAll)) WHERE NOT phone1='' AND phone1Type='Mobile' AND category=:category AND status='Full' AND (dateStart IS NULL OR dateStart<=CURRENT_DATE) AND (dateEnd IS NULL OR dateEnd>=CURRENT_DATE))" ;
                                    $sqlEmail.=" UNION (SELECT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonRole ON (FIND_IN_SET(gibbonRole.gibbonRoleID, gibbonPerson.gibbonRoleIDAll)) WHERE NOT phone2='' AND phone2Type='Mobile' AND category=:category AND status='Full' AND (dateStart IS NULL OR dateStart<=CURRENT_DATE) AND (dateEnd IS NULL OR dateEnd>=CURRENT_DATE))" ;
                                    $sqlEmail.=" UNION (SELECT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonRole ON (FIND_IN_SET(gibbonRole.gibbonRoleID, gibbonPerson.gibbonRoleIDAll)) WHERE NOT phone3='' AND phone3Type='Mobile' AND category=:category AND status='Full' AND (dateStart IS NULL OR dateStart<=CURRENT_DATE) AND (dateEnd IS NULL OR dateEnd>=CURRENT_DATE))" ;
                                    $sqlEmail.=" UNION (SELECT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonRole ON (FIND_IN_SET(gibbonRole.gibbonRoleID, gibbonPerson.gibbonRoleIDAll)) WHERE NOT phone4='' AND phone4Type='Mobile' AND category=:category AND status='Full' AND (dateStart IS NULL OR dateStart<=CURRENT_DATE) AND (dateEnd IS NULL OR dateEnd>=CURRENT_DATE))" ;
                                    $resultEmail=$connection2->prepare($sqlEmail);
                                    $resultEmail->execute($dataEmail);
                                }
                                catch(\PDOException $e) { }
                                while ($rowEmail=$resultEmail->fetch()) {
                                    $countryCodeTemp = $countryCode;
                                    if ($rowEmail["countryCode"]=="")
                                        $countryCodeTemp = $rowEmail["countryCode"];
                                    $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Role Category', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
                                }
                            }
                        }
                    }
                }
            }
        }

        //Year Groups
        if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_yearGroups_any")) {
            if ($_POST["yearGroup"]=="Y") {
                $staff=$_POST["yearGroupsStaff"] ;
                $students=$_POST["yearGroupsStudents"] ;
                $parents="N" ;
                if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_yearGroups_parents")) {
                    $parents=$_POST["yearGroupsParents"] ;
                }
                $choices=$_POST["yearGroups"]  ?? [];
                if (!empty($choices)) {
                    foreach ($choices as $t) {
                        try {
                            $data=array("AI"=>$AI, "t"=>$t, "staff"=>$staff, "students"=>$students, "parents"=>$parents);
                            $sql="INSERT INTO gibbonMessengerTarget SET gibbonMessengerID=:AI, type='Year Group', id=:t, staff=:staff, students=:students, parents=:parents" ;
                            $result=$connection2->prepare($sql);
                            $result->execute($data);
                        }
                        catch(\PDOException $e) {
                            $partialFail=TRUE;
                        }

                        //Get email addresses
                        if ($email=="Y") {
                            if ($staff=="Y") {
                                try {
                                    $dataEmail=array('gibbonSchoolYearID'=>$this->session->get('gibbonSchoolYearID'), 'gibbonYearGroupID'=>$t);
                                    $sqlEmail="(SELECT DISTINCT email, gibbonPerson.gibbonPersonID
                                        FROM gibbonPerson
                                        JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID)
                                        JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonStaff.gibbonPersonID)
                                        JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID)
                                        JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
                                        WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID
                                        AND FIND_IN_SET(:gibbonYearGroupID, gibbonCourse.gibbonYearGroupIDList)
                                        AND NOT gibbonPerson.email=''
                                        AND gibbonPerson.status='Full')
                                    UNION ALL (
                                        SELECT DISTINCT email, gibbonPerson.gibbonPersonID
                                        FROM gibbonPerson
                                        JOIN gibbonFormGroup ON (gibbonFormGroup.gibbonPersonIDTutor=gibbonPerson.gibbonPersonID OR gibbonFormGroup.gibbonPersonIDTutor2=gibbonPerson.gibbonPersonID OR gibbonFormGroup.gibbonPersonIDTutor3=gibbonPerson.gibbonPersonID)
                                        JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID)
                                        WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
                                        AND NOT email='' AND status='Full'
                                        AND gibbonStudentEnrolment.gibbonYearGroupID=:gibbonYearGroupID
                                        GROUP BY gibbonPerson.gibbonPersonID
                                    )" ;
                                    $resultEmail=$connection2->prepare($sqlEmail);
                                    $resultEmail->execute($dataEmail);
                                }
                                catch(\PDOException $e) { }
                                while ($rowEmail=$resultEmail->fetch()) {
                                    $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Year Group', $t, 'Email', $rowEmail["email"]);
                                }
                            }
                            if ($students=="Y") {
                                try {
                                    $dataEmail=array("gibbonSchoolYearID"=>$this->session->get('gibbonSchoolYearID'), "gibbonYearGroupID"=>$t);
                                    $sqlEmail="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT email='' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonYearGroupID=:gibbonYearGroupID" ;
                                    $resultEmail=$connection2->prepare($sqlEmail);
                                    $resultEmail->execute($dataEmail);
                                }
                                catch(\PDOException $e) { }
                                while ($rowEmail=$resultEmail->fetch()) {
                                    $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Year Group', $t, 'Email', $rowEmail["email"]);
                                }
                            }
                            if ($parents=="Y") {
                                try {
                                    $dataStudents=array("gibbonSchoolYearID"=>$this->session->get('gibbonSchoolYearID'), "gibbonYearGroupID"=>$t);
                                    $sqlStudents="SELECT gibbonPerson.gibbonPersonID, surname, preferredName FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonYearGroupID=:gibbonYearGroupID" ;
                                    $resultStudents=$connection2->prepare($sqlStudents);
                                    $resultStudents->execute($dataStudents);
                                }
                                catch(\PDOException $e) { }
                                while ($rowStudents=$resultStudents->fetch()) {
                                    try {
                                        $dataFamily=array("gibbonPersonID"=>$rowStudents["gibbonPersonID"]);
                                        $sqlFamily="SELECT DISTINCT gibbonFamily.gibbonFamilyID FROM gibbonFamily JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID" ;
                                        $resultFamily=$connection2->prepare($sqlFamily);
                                        $resultFamily->execute($dataFamily);
                                    }
                                    catch(\PDOException $e) { }
                                    while ($rowFamily=$resultFamily->fetch()) {
                                        try {
                                            $dataEmail=array("gibbonFamilyID"=>$rowFamily["gibbonFamilyID"] );
                                            $sqlEmail="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT email='' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactEmail='Y'" ;
                                            $resultEmail=$connection2->prepare($sqlEmail);
                                            $resultEmail->execute($dataEmail);
                                        }
                                        catch(\PDOException $e) { }
                                        while ($rowEmail=$resultEmail->fetch()) {
                                            $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Year Group', $t, 'Email', $rowEmail["email"], $rowStudents['gibbonPersonID'], Format::name('', $rowStudents['preferredName'], $rowStudents['surname'], 'Student'));
                                        }
                                    }
                                }
                            }
                        }
                        if ($sms=="Y" AND $countryCode!="") {
                            if ($staff=="Y") {
                                try {
                                    $dataEmail=array();
                                    $sqlEmail="(SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone1='' AND phone1Type='Mobile' AND status='Full')" ;
                                    $sqlEmail.=" UNION (SELECT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone2='' AND phone2Type='Mobile' AND status='Full')" ;
                                    $sqlEmail.=" UNION (SELECT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone3='' AND phone3Type='Mobile' AND status='Full')" ;
                                    $sqlEmail.=" UNION (SELECT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone4='' AND phone4Type='Mobile' AND status='Full')" ;
                                    $resultEmail=$connection2->prepare($sqlEmail);
                                    $resultEmail->execute($dataEmail);
                                }
                                catch(\PDOException $e) { }
                                while ($rowEmail=$resultEmail->fetch()) {
                                    $countryCodeTemp = $countryCode;
                                    if ($rowEmail["countryCode"]=="")
                                        $countryCodeTemp = $rowEmail["countryCode"];
                                    $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Year Group', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
                                }
                            }
                            if ($students=="Y") {
                                try {
                                    $dataEmail=array("gibbonSchoolYearID"=>$this->session->get('gibbonSchoolYearID'), "gibbonYearGroupID"=>$t);
                                    $sqlEmail="(SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone1='' AND phone1Type='Mobile' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonYearGroupID=:gibbonYearGroupID)" ;
                                    $sqlEmail.=" UNION (SELECT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone2='' AND phone2Type='Mobile' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonYearGroupID=:gibbonYearGroupID)" ;
                                    $sqlEmail.=" UNION (SELECT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone3='' AND phone3Type='Mobile' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonYearGroupID=:gibbonYearGroupID)" ;
                                    $sqlEmail.=" UNION (SELECT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone4='' AND phone4Type='Mobile' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonYearGroupID=:gibbonYearGroupID)" ;
                                    $resultEmail=$connection2->prepare($sqlEmail);
                                    $resultEmail->execute($dataEmail);
                                }
                                catch(\PDOException $e) { }
                                while ($rowEmail=$resultEmail->fetch()) {
                                    $countryCodeTemp = $countryCode;
                                    if ($rowEmail["countryCode"]=="")
                                        $countryCodeTemp = $rowEmail["countryCode"];
                                    $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Year Group', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
                                }
                            }
                            if ($parents=="Y") {
                                try {
                                    $dataStudents=array("gibbonSchoolYearID"=>$this->session->get('gibbonSchoolYearID'), "gibbonYearGroupID"=>$t);
                                    $sqlStudents="SELECT gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonYearGroupID=:gibbonYearGroupID" ;
                                    $resultStudents=$connection2->prepare($sqlStudents);
                                    $resultStudents->execute($dataStudents);
                                }
                                catch(\PDOException $e) { }
                                while ($rowStudents=$resultStudents->fetch()) {
                                    try {
                                        $dataFamily=array("gibbonPersonID"=>$rowStudents["gibbonPersonID"]);
                                        $sqlFamily="SELECT DISTINCT gibbonFamily.gibbonFamilyID FROM gibbonFamily JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID" ;
                                        $resultFamily=$connection2->prepare($sqlFamily);
                                        $resultFamily->execute($dataFamily);
                                    }
                                    catch(\PDOException $e) { }
                                    while ($rowFamily=$resultFamily->fetch()) {
                                        try {
                                            $dataEmail=array("gibbonFamilyID"=>$rowFamily["gibbonFamilyID"] );
                                            $sqlEmail="(SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone1='' AND phone1Type='Mobile' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactSMS='Y')" ;
                                            $sqlEmail.=" UNION (SELECT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone2='' AND phone2Type='Mobile' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactSMS='Y')" ;
                                            $sqlEmail.=" UNION (SELECT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone3='' AND phone3Type='Mobile' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactSMS='Y')" ;
                                            $sqlEmail.=" UNION (SELECT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone4='' AND phone4Type='Mobile' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactSMS='Y')" ;
                                            $resultEmail=$connection2->prepare($sqlEmail);
                                            $resultEmail->execute($dataEmail);
                                        }
                                        catch(\PDOException $e) { }
                                        while ($rowEmail=$resultEmail->fetch()) {
                                            $countryCodeTemp = $countryCode;
                                            if ($rowEmail["countryCode"]=="")
                                                $countryCodeTemp = $rowEmail["countryCode"];
                                            $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Year Group', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        //Form Groups
        if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_formGroups_my") OR isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_formGroups_any")) {
            if ($_POST["formGroup"]=="Y") {
                $staff=$_POST["formGroupsStaff"] ;
                $students=$_POST["formGroupsStudents"] ;
                $parents="N" ;
                if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_formGroups_parents")) {
                    $parents=$_POST["formGroupsParents"] ;
                }
                $choices=$_POST["formGroups"] ?? [];
                if (!empty($choices)) {
                    foreach ($choices as $t) {
                        try {
                            $data=array("AI"=>$AI, "t"=>$t, "staff"=>$staff, "students"=>$students, "parents"=>$parents);
                            $sql="INSERT INTO gibbonMessengerTarget SET gibbonMessengerID=:AI, type='Form Group', id=:t, staff=:staff, students=:students, parents=:parents" ;
                            $result=$connection2->prepare($sql);
                            $result->execute($data);
                        }
                        catch(\PDOException $e) {
                            $partialFail=TRUE;
                        }

                        //Get email addresses
                        if ($email=="Y") {
                            if ($staff=="Y") {
                                try {
                                    $dataEmail=array("t"=>$t);
                                    $sqlEmail="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFormGroup ON (gibbonFormGroup.gibbonPersonIDTutor=gibbonPerson.gibbonPersonID OR gibbonFormGroup.gibbonPersonIDTutor2=gibbonPerson.gibbonPersonID OR gibbonFormGroup.gibbonPersonIDTutor3=gibbonPerson.gibbonPersonID) WHERE NOT email='' AND status='Full' AND gibbonFormGroupID=:t" ;
                                    $resultEmail=$connection2->prepare($sqlEmail);
                                    $resultEmail->execute($dataEmail);
                                }
                                catch(\PDOException $e) { }
                                while ($rowEmail=$resultEmail->fetch()) {
                                    $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Form Group', $t, 'Email', $rowEmail["email"]);
                                }
                            }
                            if ($students=="Y") {
                                try {
                                    $dataEmail=array("gibbonSchoolYearID"=>$this->session->get('gibbonSchoolYearID'), "gibbonFormGroupID"=>$t);
                                    $sqlEmail="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT email='' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonFormGroupID=:gibbonFormGroupID" ;
                                    $resultEmail=$connection2->prepare($sqlEmail);
                                    $resultEmail->execute($dataEmail);
                                }
                                catch(\PDOException $e) { }
                                while ($rowEmail=$resultEmail->fetch()) {
                                    $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Form Group', $t, 'Email', $rowEmail["email"]);
                                }
                            }
                            if ($parents=="Y") {
                                try {
                                    $dataStudents=array("gibbonSchoolYearID"=>$this->session->get('gibbonSchoolYearID'), "gibbonFormGroupID"=>$t);
                                    $sqlStudents="SELECT DISTINCT gibbonPerson.gibbonPersonID, surname, preferredName FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonFormGroupID=:gibbonFormGroupID" ;
                                    $resultStudents=$connection2->prepare($sqlStudents);
                                    $resultStudents->execute($dataStudents);
                                }
                                catch(\PDOException $e) { }
                                while ($rowStudents=$resultStudents->fetch()) {
                                    try {
                                        $dataFamily=array("gibbonPersonID"=>$rowStudents["gibbonPersonID"]);
                                        $sqlFamily="SELECT DISTINCT gibbonFamily.gibbonFamilyID FROM gibbonFamily JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID" ;
                                        $resultFamily=$connection2->prepare($sqlFamily);
                                        $resultFamily->execute($dataFamily);
                                    }
                                    catch(\PDOException $e) { }
                                    while ($rowFamily=$resultFamily->fetch()) {
                                        try {
                                            $dataEmail=array("gibbonFamilyID"=>$rowFamily["gibbonFamilyID"] );
                                            $sqlEmail="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT email='' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactEmail='Y'" ;
                                            $resultEmail=$connection2->prepare($sqlEmail);
                                            $resultEmail->execute($dataEmail);
                                        }
                                        catch(\PDOException $e) { }
                                        while ($rowEmail=$resultEmail->fetch()) {
                                            $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Form Group', $t, 'Email', $rowEmail["email"], $rowStudents['gibbonPersonID'], Format::name('', $rowStudents['preferredName'], $rowStudents['surname'], 'Student'));
                                        }
                                    }
                                }
                            }
                        }
                        if ($sms=="Y" AND $countryCode!="") {
                            if ($staff=="Y") {
                                try {
                                    $dataEmail=array("gibbonFormGroupID"=>$t);
                                    $sqlEmail="(SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFormGroup ON (gibbonFormGroup.gibbonPersonIDTutor=gibbonPerson.gibbonPersonID OR gibbonFormGroup.gibbonPersonIDTutor2=gibbonPerson.gibbonPersonID OR gibbonFormGroup.gibbonPersonIDTutor3=gibbonPerson.gibbonPersonID) WHERE NOT phone1='' AND phone1Type='Mobile' AND status='Full' AND gibbonFormGroupID=:gibbonFormGroupID)" ;
                                    $sqlEmail.=" UNION (SELECT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFormGroup ON (gibbonFormGroup.gibbonPersonIDTutor=gibbonPerson.gibbonPersonID OR gibbonFormGroup.gibbonPersonIDTutor2=gibbonPerson.gibbonPersonID OR gibbonFormGroup.gibbonPersonIDTutor3=gibbonPerson.gibbonPersonID) WHERE NOT phone2='' AND phone2Type='Mobile' AND status='Full' AND gibbonFormGroupID=:gibbonFormGroupID)" ;
                                    $sqlEmail.=" UNION (SELECT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFormGroup ON (gibbonFormGroup.gibbonPersonIDTutor=gibbonPerson.gibbonPersonID OR gibbonFormGroup.gibbonPersonIDTutor2=gibbonPerson.gibbonPersonID OR gibbonFormGroup.gibbonPersonIDTutor3=gibbonPerson.gibbonPersonID) WHERE NOT phone3='' AND phone3Type='Mobile' AND status='Full' AND gibbonFormGroupID=:gibbonFormGroupID)" ;
                                    $sqlEmail.=" UNION (SELECT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFormGroup ON (gibbonFormGroup.gibbonPersonIDTutor=gibbonPerson.gibbonPersonID OR gibbonFormGroup.gibbonPersonIDTutor2=gibbonPerson.gibbonPersonID OR gibbonFormGroup.gibbonPersonIDTutor3=gibbonPerson.gibbonPersonID) WHERE NOT phone4='' AND phone4Type='Mobile' AND status='Full' AND gibbonFormGroupID=:gibbonFormGroupID)" ;
                                    $resultEmail=$connection2->prepare($sqlEmail);
                                    $resultEmail->execute($dataEmail);
                                }
                                catch(\PDOException $e) { }
                                while ($rowEmail=$resultEmail->fetch()) {
                                    $countryCodeTemp = $countryCode;
                                    if ($rowEmail["countryCode"]=="")
                                        $countryCodeTemp = $rowEmail["countryCode"];
                                    $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Form Group', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
                                }
                            }
                            if ($students=="Y") {
                                try {
                                    $dataEmail=array("gibbonSchoolYearID"=>$this->session->get('gibbonSchoolYearID'), "gibbonFormGroupID"=>$t);
                                    $sqlEmail="(SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone1='' AND phone1Type='Mobile' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonFormGroupID=:gibbonFormGroupID)" ;
                                    $sqlEmail.=" UNION (SELECT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone2='' AND phone2Type='Mobile' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonFormGroupID=:gibbonFormGroupID)" ;
                                    $sqlEmail.=" UNION (SELECT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone3='' AND phone3Type='Mobile' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonFormGroupID=:gibbonFormGroupID)" ;
                                    $sqlEmail.=" UNION (SELECT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone4='' AND phone4Type='Mobile' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonFormGroupID=:gibbonFormGroupID)" ;
                                    $resultEmail=$connection2->prepare($sqlEmail);
                                    $resultEmail->execute($dataEmail);
                                }
                                catch(\PDOException $e) { }
                                while ($rowEmail=$resultEmail->fetch()) {
                                    $countryCodeTemp = $countryCode;
                                    if ($rowEmail["countryCode"]=="")
                                        $countryCodeTemp = $rowEmail["countryCode"];
                                    $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Form Group', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
                                }
                            }
                            if ($parents=="Y") {
                                try {
                                    $dataStudents=array("gibbonSchoolYearID"=>$this->session->get('gibbonSchoolYearID'), "gibbonFormGroupID"=>$t);
                                    $sqlStudents="SELECT DISTINCT gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonFormGroupID=:gibbonFormGroupID" ;
                                    $resultStudents=$connection2->prepare($sqlStudents);
                                    $resultStudents->execute($dataStudents);
                                }
                                catch(\PDOException $e) { }
                                while ($rowStudents=$resultStudents->fetch()) {
                                    try {
                                        $dataFamily=array("gibbonPersonID"=>$rowStudents["gibbonPersonID"]);
                                        $sqlFamily="SELECT DISTINCT gibbonFamily.gibbonFamilyID FROM gibbonFamily JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID" ;
                                        $resultFamily=$connection2->prepare($sqlFamily);
                                        $resultFamily->execute($dataFamily);
                                    }
                                    catch(\PDOException $e) { }
                                    while ($rowFamily=$resultFamily->fetch()) {
                                        try {
                                            $dataEmail=array("gibbonFamilyID"=>$rowFamily["gibbonFamilyID"] );
                                            $sqlEmail="(SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone1='' AND phone1Type='Mobile' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactSMS='Y')" ;
                                            $sqlEmail.=" UNION (SELECT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone2='' AND phone2Type='Mobile' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactSMS='Y')" ;
                                            $sqlEmail.=" UNION (SELECT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone3='' AND phone3Type='Mobile' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactSMS='Y')" ;
                                            $sqlEmail.=" UNION (SELECT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone4='' AND phone4Type='Mobile' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactSMS='Y')" ;
                                            $resultEmail=$connection2->prepare($sqlEmail);
                                            $resultEmail->execute($dataEmail);
                                        }
                                        catch(\PDOException $e) { }
                                        while ($rowEmail=$resultEmail->fetch()) {
                                            $countryCodeTemp = $countryCode;
                                            if ($rowEmail["countryCode"]=="")
                                                $countryCodeTemp = $rowEmail["countryCode"];
                                            $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Form Group', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        //Course Groups
        if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_courses_my") OR isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_courses_any")) {
            if ($_POST["course"]=="Y") {
                $staff=$_POST["coursesStaff"] ;
                $students=$_POST["coursesStudents"] ;
                $parents="N" ;
                if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_courses_parents")) {
                    $parents=$_POST["coursesParents"] ;
                }
                $choices=$_POST["courses"] ?? [];
                if (!empty($choices)) {
                    foreach ($choices as $t) {
                        try {
                            $data=array("gibbonMessengerID"=>$AI, "id"=>$t, "staff"=>$staff, "students"=>$students, "parents"=>$parents);
                            $sql="INSERT INTO gibbonMessengerTarget SET gibbonMessengerID=:gibbonMessengerID, type='Course', id=:id, staff=:staff, students=:students, parents=:parents" ;
                            $result=$connection2->prepare($sql);
                            $result->execute($data);
                        }
                        catch(\PDOException $e) {
                            $partialFail=TRUE;
                        }

                        //Get email addresses
                        if ($email=="Y") {
                            if ($staff=="Y") {
                                try {
                                    $dataEmail=array("gibbonCourseID"=>$t);
                                    $sqlEmail="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE (role='Teacher' OR role='Assistant' OR role='Technician') AND NOT email='' AND status='Full' AND gibbonCourse.gibbonCourseID=:gibbonCourseID" ;
                                    $resultEmail=$connection2->prepare($sqlEmail);
                                    $resultEmail->execute($dataEmail);
                                }
                                catch(\PDOException $e) { }
                                while ($rowEmail=$resultEmail->fetch()) {
                                    $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Course', $t, 'Email', $rowEmail["email"]);
                                }
                            }
                            if ($students=="Y") {
                                try {
                                    $dataEmail=array("gibbonCourseID"=>$t);
                                    $sqlEmail="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE role='Student' AND NOT email='' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonCourse.gibbonCourseID=:gibbonCourseID" ;
                                    $resultEmail=$connection2->prepare($sqlEmail);
                                    $resultEmail->execute($dataEmail);
                                }
                                catch(\PDOException $e) { }
                                while ($rowEmail=$resultEmail->fetch()) {
                                    $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Course', $t, 'Email', $rowEmail["email"]);
                                }
                            }
                            if ($parents=="Y") {
                                try {
                                    $dataStudents=array("gibbonCourseID"=>$t);
                                    $sqlStudents="SELECT DISTINCT gibbonPerson.gibbonPersonID, surname, preferredName FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE role='Student' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonCourse.gibbonCourseID=:gibbonCourseID" ;
                                    $resultStudents=$connection2->prepare($sqlStudents);
                                    $resultStudents->execute($dataStudents);
                                }
                                catch(\PDOException $e) { }
                                while ($rowStudents=$resultStudents->fetch()) {
                                    try {
                                        $dataFamily=array("gibbonPersonID"=>$rowStudents["gibbonPersonID"]);
                                        $sqlFamily="SELECT DISTINCT gibbonFamily.gibbonFamilyID FROM gibbonFamily JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID" ;
                                        $resultFamily=$connection2->prepare($sqlFamily);
                                        $resultFamily->execute($dataFamily);
                                    }
                                    catch(\PDOException $e) { }
                                    while ($rowFamily=$resultFamily->fetch()) {
                                        try {
                                            $dataEmail=array("gibbonFamilyID"=>$rowFamily["gibbonFamilyID"] );
                                            $sqlEmail="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT email='' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactEmail='Y'" ;
                                            $resultEmail=$connection2->prepare($sqlEmail);
                                            $resultEmail->execute($dataEmail);
                                        }
                                        catch(\PDOException $e) { }
                                        while ($rowEmail=$resultEmail->fetch()) {
                                            $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Course', $t, 'Email', $rowEmail["email"], $rowStudents['gibbonPersonID'], Format::name('', $rowStudents['preferredName'], $rowStudents['surname'], 'Student'));
                                        }
                                    }
                                }
                                try {
                                    $dataEmail=array("gibbonCourseID"=>$t);
                                    $sqlEmail="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE role='Parent' AND NOT email='' AND status='Full' AND gibbonCourse.gibbonCourseID=:gibbonCourseID" ;
                                    $resultEmail=$connection2->prepare($sqlEmail);
                                    $resultEmail->execute($dataEmail);
                                }
                                catch(\PDOException $e) { }
                                while ($rowEmail=$resultEmail->fetch()) {
                                    $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Course', $t, 'Email', $rowEmail["email"]);
                                }
                            }
                        }
                        if ($sms=="Y" AND $countryCode!="") {
                            if ($staff=="Y") {
                                try {
                                    $dataEmail=array("gibbonCourseID"=>$t);
                                    $sqlEmail="(SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE (role='Teacher' OR role='Assistant' OR role='Technician') AND NOT phone1='' AND phone1Type='Mobile' AND status='Full' AND gibbonCourse.gibbonCourseID=:gibbonCourseID)" ;
                                    $sqlEmail.=" UNION (SELECT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE (role='Teacher' OR role='Assistant' OR role='Technician') AND NOT phone2='' AND phone2Type='Mobile' AND status='Full' AND gibbonCourse.gibbonCourseID=:gibbonCourseID)" ;
                                    $sqlEmail.=" UNION (SELECT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE (role='Teacher' OR role='Assistant' OR role='Technician') AND NOT phone3='' AND phone3Type='Mobile' AND status='Full' AND gibbonCourse.gibbonCourseID=:gibbonCourseID)" ;
                                    $sqlEmail.=" UNION (SELECT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE (role='Teacher' OR role='Assistant' OR role='Technician') AND NOT phone4='' AND phone4Type='Mobile' AND status='Full' AND gibbonCourse.gibbonCourseID=:gibbonCourseID)" ;
                                    $resultEmail=$connection2->prepare($sqlEmail);
                                    $resultEmail->execute($dataEmail);
                                }
                                catch(\PDOException $e) { }
                                while ($rowEmail=$resultEmail->fetch()) {
                                    $countryCodeTemp = $countryCode;
                                    if ($rowEmail["countryCode"]=="")
                                        $countryCodeTemp = $rowEmail["countryCode"];
                                    $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Course', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
                                }
                            }
                            if ($students=="Y") {
                                try {
                                    $dataEmail=array("gibbonCourseID"=>$t);
                                    $sqlEmail="(SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE role='Student' AND NOT phone1='' AND phone1Type='Mobile' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonCourse.gibbonCourseID=:gibbonCourseID)" ;
                                    $sqlEmail.=" UNION (SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE role='Student' AND NOT phone1='' AND phone1Type='Mobile' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonCourse.gibbonCourseID=:gibbonCourseID)" ;
                                    $sqlEmail.=" UNION (SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE role='Student' AND NOT phone1='' AND phone1Type='Mobile' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonCourse.gibbonCourseID=:gibbonCourseID)" ;
                                    $sqlEmail.=" UNION (SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE role='Student' AND NOT phone1='' AND phone1Type='Mobile' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonCourse.gibbonCourseID=:gibbonCourseID)" ;
                                    $resultEmail=$connection2->prepare($sqlEmail);
                                    $resultEmail->execute($dataEmail);
                                }
                                catch(\PDOException $e) { }
                                while ($rowEmail=$resultEmail->fetch()) {
                                    $countryCodeTemp = $countryCode;
                                    if ($rowEmail["countryCode"]=="")
                                        $countryCodeTemp = $rowEmail["countryCode"];
                                    $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Course', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
                                }
                            }
                            if ($parents=="Y") {
                                try {
                                    $dataStudents=array("gibbonCourseID"=>$t);
                                    $sqlStudents="SELECT DISTINCT gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE role='Student' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonCourse.gibbonCourseID=:gibbonCourseID" ;
                                    $resultStudents=$connection2->prepare($sqlStudents);
                                    $resultStudents->execute($dataStudents);
                                }
                                catch(\PDOException $e) { }
                                while ($rowStudents=$resultStudents->fetch()) {
                                    try {
                                        $dataFamily=array("gibbonPersonID"=>$rowStudents["gibbonPersonID"]);
                                        $sqlFamily="SELECT DISTINCT gibbonFamily.gibbonFamilyID FROM gibbonFamily JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID" ;
                                        $resultFamily=$connection2->prepare($sqlFamily);
                                        $resultFamily->execute($dataFamily);
                                    }
                                    catch(\PDOException $e) { }
                                    while ($rowFamily=$resultFamily->fetch()) {
                                        try {
                                            $dataEmail=array("gibbonFamilyID"=>$rowFamily["gibbonFamilyID"] );
                                            $sqlEmail="(SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone1='' AND phone1Type='Mobile' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactSMS='Y')" ;
                                            $sqlEmail.=" UNION (SELECT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone2='' AND phone2Type='Mobile' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactSMS='Y')" ;
                                            $sqlEmail.=" UNION (SELECT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone3='' AND phone3Type='Mobile' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactSMS='Y')" ;
                                            $sqlEmail.=" UNION (SELECT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone4='' AND phone4Type='Mobile' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactSMS='Y')" ;
                                            $resultEmail=$connection2->prepare($sqlEmail);
                                            $resultEmail->execute($dataEmail);
                                        }
                                        catch(\PDOException $e) { }
                                        while ($rowEmail=$resultEmail->fetch()) {
                                            $countryCodeTemp = $countryCode;
                                            if ($rowEmail["countryCode"]=="")
                                                $countryCodeTemp = $rowEmail["countryCode"];
                                            $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Course', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
                                        }
                                    }
                                }
                                try {
                                    $dataEmail=array("gibbonCourseID"=>$t);
                                    $sqlEmail="(SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE role='Parent' AND NOT phone1='' AND phone1Type='Mobile' AND status='Full' AND gibbonCourse.gibbonCourseID=:gibbonCourseID)" ;
                                    $sqlEmail.=" UNION (SELECT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE role='Parent' AND NOT phone2='' AND phone2Type='Mobile' AND status='Full' AND gibbonCourse.gibbonCourseID=:gibbonCourseID)" ;
                                    $sqlEmail.=" UNION (SELECT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE role='Parent' AND NOT phone3='' AND phone3Type='Mobile' AND status='Full' AND gibbonCourse.gibbonCourseID=:gibbonCourseID)" ;
                                    $sqlEmail.=" UNION (SELECT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE role='Parent' AND NOT phone4='' AND phone4Type='Mobile' AND status='Full' AND gibbonCourse.gibbonCourseID=:gibbonCourseID)" ;
                                    $resultEmail=$connection2->prepare($sqlEmail);
                                    $resultEmail->execute($dataEmail);
                                }
                                catch(\PDOException $e) { }
                                while ($rowEmail=$resultEmail->fetch()) {
                                    $countryCodeTemp = $countryCode;
                                    if ($rowEmail["countryCode"]=="")
                                        $countryCodeTemp = $rowEmail["countryCode"];
                                    $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Course', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
                                }
                            }
                        }
                    }
                }
            }
        }

        //Class Groups
        if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_classes_my") OR isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_classes_any")) {
            if ($_POST["class"]=="Y") {
                $staff=$_POST["classesStaff"] ;
                $students=$_POST["classesStudents"] ;
                $parents="N" ;
                if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_classes_parents")) {
                    $parents=$_POST["classesParents"] ;
                }
                $choices=$_POST["classes"] ?? [];;
                if (!empty($choices)) {
                    foreach ($choices as $t) {
                        try {
                            $data=array("gibbonMessengerID"=>$AI, "id"=>$t, "staff"=>$staff, "students"=>$students, "parents"=>$parents);
                            $sql="INSERT INTO gibbonMessengerTarget SET gibbonMessengerID=:gibbonMessengerID, type='Class', id=:id, staff=:staff, students=:students, parents=:parents" ;
                            $result=$connection2->prepare($sql);
                            $result->execute($data);
                        }
                        catch(\PDOException $e) {
                            $partialFail=TRUE;
                        }

                        //Get email addresses
                        if ($email=="Y") {
                            if ($staff=="Y") {
                                try {
                                    $dataEmail=array("gibbonCourseClassID"=>$t);
                                    $sqlEmail="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE (role='Teacher' OR role='Assistant' OR role='Technician') AND NOT email='' AND status='Full' AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID" ;
                                    $resultEmail=$connection2->prepare($sqlEmail);
                                    $resultEmail->execute($dataEmail);
                                }
                                catch(\PDOException $e) { }
                                while ($rowEmail=$resultEmail->fetch()) {
                                    $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Class', $t, 'Email', $rowEmail["email"]);
                                }
                            }
                            if ($students=="Y") {
                                try {
                                    $dataEmail=array("gibbonCourseClassID"=>$t);
                                    $sqlEmail="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE role='Student' AND NOT email='' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID" ;
                                    $resultEmail=$connection2->prepare($sqlEmail);
                                    $resultEmail->execute($dataEmail);
                                }
                                catch(\PDOException $e) { }

                                while ($rowEmail=$resultEmail->fetch()) {
                                    $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Class', $t, 'Email', $rowEmail["email"]);
                                }
                            }
                            if ($parents=="Y") {
                                try {
                                    $dataStudents=array("gibbonCourseClassID"=>$t);
                                    $sqlStudents="SELECT DISTINCT gibbonPerson.gibbonPersonID, surname, preferredName FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE role='Student' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID" ;
                                    $resultStudents=$connection2->prepare($sqlStudents);
                                    $resultStudents->execute($dataStudents);
                                }
                                catch(\PDOException $e) { }
                                while ($rowStudents=$resultStudents->fetch()) {
                                    try {
                                        $dataFamily=array("gibbonPersonID"=>$rowStudents["gibbonPersonID"]);
                                        $sqlFamily="SELECT DISTINCT gibbonFamily.gibbonFamilyID FROM gibbonFamily JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID" ;
                                        $resultFamily=$connection2->prepare($sqlFamily);
                                        $resultFamily->execute($dataFamily);
                                    }
                                    catch(\PDOException $e) { }
                                    while ($rowFamily=$resultFamily->fetch()) {
                                        try {
                                            $dataEmail=array("gibbonFamilyID"=>$rowFamily["gibbonFamilyID"] );
                                            $sqlEmail="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT email='' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactEmail='Y'" ;
                                            $resultEmail=$connection2->prepare($sqlEmail);
                                            $resultEmail->execute($dataEmail);
                                        }
                                        catch(\PDOException $e) { }
                                        while ($rowEmail=$resultEmail->fetch()) {
                                            $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Class', $t, 'Email', $rowEmail["email"], $rowStudents['gibbonPersonID'], Format::name('', $rowStudents['preferredName'], $rowStudents['surname'], 'Student'));
                                        }
                                    }
                                }
                                try {
                                    $dataEmail=array("gibbonCourseClassID"=>$t);
                                    $sqlEmail="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE role='Parent' AND NOT email='' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID" ;
                                    $resultEmail=$connection2->prepare($sqlEmail);
                                    $resultEmail->execute($dataEmail);
                                }
                                catch(\PDOException $e) { }
                                while ($rowEmail=$resultEmail->fetch()) {
                                    $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Class', $t, 'Email', $rowEmail["email"]);
                                }
                            }
                        }
                        if ($sms=="Y" AND $countryCode!="") {
                            if ($staff=="Y") {
                                try {
                                    $dataEmail=array("gibbonCourseClassID"=>$t);
                                    $sqlEmail="(SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE (role='Teacher' OR role='Assistant' OR role='Technician') AND NOT phone1='' AND phone1Type='Mobile' AND status='Full' AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID)" ;
                                    $sqlEmail.=" UNION (SELECT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE (role='Teacher' OR role='Assistant' OR role='Technician') AND NOT phone2='' AND phone2Type='Mobile' AND status='Full' AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID)" ;
                                    $sqlEmail.=" UNION (SELECT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE (role='Teacher' OR role='Assistant' OR role='Technician') AND NOT phone3='' AND phone3Type='Mobile' AND status='Full' AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID)" ;
                                    $sqlEmail.=" UNION (SELECT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE (role='Teacher' OR role='Assistant' OR role='Technician') AND NOT phone4='' AND phone4Type='Mobile' AND status='Full' AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID)" ;
                                    $resultEmail=$connection2->prepare($sqlEmail);
                                    $resultEmail->execute($dataEmail);
                                }
                                catch(\PDOException $e) { }
                                while ($rowEmail=$resultEmail->fetch()) {
                                    $countryCodeTemp = $countryCode;
                                    if ($rowEmail["countryCode"]=="")
                                        $countryCodeTemp = $rowEmail["countryCode"];
                                    $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Class', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
                                }
                            }
                            if ($students=="Y") {
                                try {
                                    $dataEmail=array("gibbonCourseClassID"=>$t);
                                    $sqlEmail="(SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE role='Student' AND NOT phone1='' AND phone1Type='Mobile' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID)" ;
                                    $sqlEmail.=" UNION (SELECT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE role='Student' AND NOT phone2='' AND phone2Type='Mobile' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID)" ;
                                    $sqlEmail.=" UNION (SELECT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE role='Student' AND NOT phone3='' AND phone3Type='Mobile' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID)" ;
                                    $sqlEmail.=" UNION (SELECT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE role='Student' AND NOT phone4='' AND phone4Type='Mobile' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID)" ;
                                    $resultEmail=$connection2->prepare($sqlEmail);
                                    $resultEmail->execute($dataEmail);
                                }
                                catch(\PDOException $e) { }
                                while ($rowEmail=$resultEmail->fetch()) {
                                    $countryCodeTemp = $countryCode;
                                    if ($rowEmail["countryCode"]=="")
                                        $countryCodeTemp = $rowEmail["countryCode"];
                                    $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Class', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
                                }
                            }
                            if ($parents=="Y") {
                                try {
                                    $dataStudents=array("gibbonCourseClassID"=>$t);
                                    $sqlStudents="SELECT DISTINCT gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE role='Student' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID" ;
                                    $resultStudents=$connection2->prepare($sqlStudents);
                                    $resultStudents->execute($dataStudents);
                                }
                                catch(\PDOException $e) { }
                                while ($rowStudents=$resultStudents->fetch()) {
                                    try {
                                        $dataFamily=array("gibbonPersonID"=>$rowStudents["gibbonPersonID"]);
                                        $sqlFamily="SELECT DISTINCT gibbonFamily.gibbonFamilyID FROM gibbonFamily JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID" ;
                                        $resultFamily=$connection2->prepare($sqlFamily);
                                        $resultFamily->execute($dataFamily);
                                    }
                                    catch(\PDOException $e) { }
                                    while ($rowFamily=$resultFamily->fetch()) {
                                        try {
                                            $dataEmail=array("gibbonFamilyID"=>$rowFamily["gibbonFamilyID"] );
                                            $sqlEmail="(SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone1='' AND phone1Type='Mobile' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactSMS='Y')" ;
                                            $sqlEmail.=" UNION (SELECT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone2='' AND phone2Type='Mobile' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactSMS='Y')" ;
                                            $sqlEmail.=" UNION (SELECT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone3='' AND phone3Type='Mobile' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactSMS='Y')" ;
                                            $sqlEmail.=" UNION (SELECT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone4='' AND phone4Type='Mobile' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactSMS='Y')" ;
                                            $resultEmail=$connection2->prepare($sqlEmail);
                                            $resultEmail->execute($dataEmail);
                                        }
                                        catch(\PDOException $e) { }
                                        while ($rowEmail=$resultEmail->fetch()) {
                                            $countryCodeTemp = $countryCode;
                                            if ($rowEmail["countryCode"]=="")
                                                $countryCodeTemp = $rowEmail["countryCode"];
                                            $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Class', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
                                        }
                                    }
                                }
                                try {
                                    $dataEmail=array("gibbonCourseClassID"=>$t);
                                    $sqlEmail="(SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE role='Parent' AND NOT phone1='' AND phone1Type='Mobile' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID)" ;
                                    $sqlEmail.=" UNION (SELECT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE role='Parent' AND NOT phone2='' AND phone2Type='Mobile' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID)" ;
                                    $sqlEmail.=" UNION (SELECT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE role='Parent' AND NOT phone3='' AND phone3Type='Mobile' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID)" ;
                                    $sqlEmail.=" UNION (SELECT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE role='Parent' AND NOT phone4='' AND phone4Type='Mobile' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID)" ;
                                    $resultEmail=$connection2->prepare($sqlEmail);
                                    $resultEmail->execute($dataEmail);
                                }
                                catch(\PDOException $e) { }
                                while ($rowEmail=$resultEmail->fetch()) {
                                    $countryCodeTemp = $countryCode;
                                    if ($rowEmail["countryCode"]=="")
                                        $countryCodeTemp = $rowEmail["countryCode"];
                                    $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Class', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
                                }
                            }
                        }
                    }
                }
            }
        }

        //Activity Groups
        if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_activities_my") OR isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_activities_any")) {
            if ($_POST["activity"]=="Y") {
                $staff=$_POST["activitiesStaff"] ;
                $students=$_POST["activitiesStudents"] ;
                $parents="N" ;
                if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_activities_parents")) {
                    $parents=$_POST["activitiesParents"] ;
                }
                $choices=$_POST["activities"] ?? [];
                if (!empty($choices)) {
                    foreach ($choices as $t) {
                        try {
                            $data=array("gibbonMessengerID"=>$AI, "id"=>$t, "staff"=>$staff, "students"=>$students, "parents"=>$parents);
                            $sql="INSERT INTO gibbonMessengerTarget SET gibbonMessengerID=:gibbonMessengerID, type='Activity', id=:id, staff=:staff, students=:students, parents=:parents" ;
                            $result=$connection2->prepare($sql);
                            $result->execute($data);
                        }
                        catch(\PDOException $e) {
                            $partialFail=TRUE;
                        }

                        //Get email addresses
                        if ($email=="Y") {
                            if ($staff=="Y") {
                                try {
                                    $dataEmail=array("gibbonActivityID"=>$t);
                                    $sqlEmail="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonActivityStaff ON (gibbonActivityStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonActivity ON (gibbonActivityStaff.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE NOT email='' AND status='Full' AND gibbonActivity.gibbonActivityID=:gibbonActivityID" ;
                                    $resultEmail=$connection2->prepare($sqlEmail);
                                    $resultEmail->execute($dataEmail);
                                }
                                catch(\PDOException $e) { }
                                while ($rowEmail=$resultEmail->fetch()) {
                                    $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Activity', $t, 'Email', $rowEmail["email"]);
                                }
                            }
                            if ($students=="Y") {
                                try {
                                    $dataEmail=array("gibbonActivityID"=>$t);
                                    $sqlEmail="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonActivity ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE NOT email='' AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonActivityStudent.status='Accepted' AND gibbonActivity.gibbonActivityID=:gibbonActivityID" ;
                                    $resultEmail=$connection2->prepare($sqlEmail);
                                    $resultEmail->execute($dataEmail);
                                }
                                catch(\PDOException $e) { }
                                while ($rowEmail=$resultEmail->fetch()) {
                                    $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Activity', $t, 'Email', $rowEmail["email"]);
                                }
                            }
                            if ($parents=="Y") {
                                try {
                                    $dataStudents=array("gibbonActivityID"=>$t);
                                    $sqlStudents="SELECT DISTINCT gibbonPerson.gibbonPersonID, surname, preferredName FROM gibbonPerson JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonActivity ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonActivityStudent.status='Accepted' AND gibbonActivity.gibbonActivityID=:gibbonActivityID" ;
                                    $resultStudents=$connection2->prepare($sqlStudents);
                                    $resultStudents->execute($dataStudents);
                                }
                                catch(\PDOException $e) { }
                                while ($rowStudents=$resultStudents->fetch()) {
                                    try {
                                        $dataFamily=array("gibbonPersonID"=>$rowStudents["gibbonPersonID"]);
                                        $sqlFamily="SELECT DISTINCT gibbonFamily.gibbonFamilyID FROM gibbonFamily JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID" ;
                                        $resultFamily=$connection2->prepare($sqlFamily);
                                        $resultFamily->execute($dataFamily);
                                    }
                                    catch(\PDOException $e) { }
                                    while ($rowFamily=$resultFamily->fetch()) {
                                        try {
                                            $dataEmail=array("gibbonFamilyID"=>$rowFamily["gibbonFamilyID"] );
                                            $sqlEmail="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT email='' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactEmail='Y'" ;
                                            $resultEmail=$connection2->prepare($sqlEmail);
                                            $resultEmail->execute($dataEmail);
                                        }
                                        catch(\PDOException $e) { }
                                        while ($rowEmail=$resultEmail->fetch()) {
                                            $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Activity', $t, 'Email', $rowEmail["email"], $rowStudents['gibbonPersonID'], Format::name('', $rowStudents['preferredName'], $rowStudents['surname'], 'Student'));
                                        }
                                    }
                                }
                            }
                        }
                        if ($sms=="Y" AND $countryCode!="") {
                            if ($staff=="Y") {
                                try {
                                    $dataEmail=array("gibbonActivityID"=>$t);
                                    $sqlEmail="(SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonActivityStaff ON (gibbonActivityStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonActivity ON (gibbonActivityStaff.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE NOT phone1='' AND phone1Type='Mobile' AND status='Full' AND gibbonActivity.gibbonActivityID=:gibbonActivityID)" ;
                                    $sqlEmail.=" UNION (SELECT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonActivityStaff ON (gibbonActivityStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonActivity ON (gibbonActivityStaff.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE NOT phone2='' AND phone2Type='Mobile' AND status='Full' AND gibbonActivity.gibbonActivityID=:gibbonActivityID)" ;
                                    $sqlEmail.=" UNION (SELECT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonActivityStaff ON (gibbonActivityStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonActivity ON (gibbonActivityStaff.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE NOT phone3='' AND phone3Type='Mobile' AND status='Full' AND gibbonActivity.gibbonActivityID=:gibbonActivityID)" ;
                                    $sqlEmail.=" UNION (SELECT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonActivityStaff ON (gibbonActivityStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonActivity ON (gibbonActivityStaff.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE NOT phone4='' AND phone4Type='Mobile' AND status='Full' AND gibbonActivity.gibbonActivityID=:gibbonActivityID)" ;
                                    $resultEmail=$connection2->prepare($sqlEmail);
                                    $resultEmail->execute($dataEmail);
                                }
                                catch(\PDOException $e) { }
                                while ($rowEmail=$resultEmail->fetch()) {
                                    $countryCodeTemp = $countryCode;
                                    if ($rowEmail["countryCode"]=="")
                                        $countryCodeTemp = $rowEmail["countryCode"];
                                    $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Activity', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
                                }
                            }
                            if ($students=="Y") {
                                try {
                                    $dataEmail=array("gibbonActivityID"=>$t);
                                    $sqlEmail="(SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonActivity ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE NOT phone1='' AND phone1Type='Mobile' AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonActivityStudent.status='Accepted' AND gibbonActivity.gibbonActivityID=:gibbonActivityID)" ;
                                    $sqlEmail.=" UNION (SELECT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonActivity ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE NOT phone2='' AND phone2Type='Mobile' AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonActivityStudent.status='Accepted' AND gibbonActivity.gibbonActivityID=:gibbonActivityID)" ;
                                    $sqlEmail.=" UNION (SELECT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonActivity ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE NOT phone3='' AND phone3Type='Mobile' AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonActivityStudent.status='Accepted' AND gibbonActivity.gibbonActivityID=:gibbonActivityID)" ;
                                    $sqlEmail.=" UNION (SELECT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonActivity ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE NOT phone4='' AND phone4Type='Mobile' AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonActivityStudent.status='Accepted' AND gibbonActivity.gibbonActivityID=:gibbonActivityID)" ;
                                    $resultEmail=$connection2->prepare($sqlEmail);
                                    $resultEmail->execute($dataEmail);
                                }
                                catch(\PDOException $e) { }
                                while ($rowEmail=$resultEmail->fetch()) {
                                    $countryCodeTemp = $countryCode;
                                    if ($rowEmail["countryCode"]=="")
                                        $countryCodeTemp = $rowEmail["countryCode"];
                                    $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Activity', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
                                }
                            }
                            if ($parents=="Y") {
                                try {
                                    $dataStudents=array("gibbonActivityID"=>$t);
                                    $sqlStudents="SELECT DISTINCT gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonActivity ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonActivityStudent.status='Accepted' AND gibbonActivity.gibbonActivityID=:gibbonActivityID" ;
                                    $resultStudents=$connection2->prepare($sqlStudents);
                                    $resultStudents->execute($dataStudents);
                                }
                                catch(\PDOException $e) { }
                                while ($rowStudents=$resultStudents->fetch()) {
                                    try {
                                        $dataFamily=array("gibbonPersonID"=>$rowStudents["gibbonPersonID"]);
                                        $sqlFamily="SELECT DISTINCT gibbonFamily.gibbonFamilyID FROM gibbonFamily JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID" ;
                                        $resultFamily=$connection2->prepare($sqlFamily);
                                        $resultFamily->execute($dataFamily);
                                    }
                                    catch(\PDOException $e) { }
                                    while ($rowFamily=$resultFamily->fetch()) {
                                        try {
                                            $dataEmail=array("gibbonFamilyID"=>$rowFamily["gibbonFamilyID"] );
                                            $sqlEmail="(SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone1='' AND phone1Type='Mobile' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactSMS='Y')" ;
                                            $sqlEmail.=" UNION (SELECT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone2='' AND phone2Type='Mobile' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactSMS='Y')" ;
                                            $sqlEmail.=" UNION (SELECT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone3='' AND phone3Type='Mobile' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactSMS='Y')" ;
                                            $sqlEmail.=" UNION (SELECT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone4='' AND phone4Type='Mobile' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactSMS='Y')" ;
                                            $resultEmail=$connection2->prepare($sqlEmail);
                                            $resultEmail->execute($dataEmail);
                                        }
                                        catch(\PDOException $e) {}
                                        while ($rowEmail=$resultEmail->fetch()) {
                                            $countryCodeTemp = $countryCode;
                                            if ($rowEmail["countryCode"]=="")
                                                $countryCodeTemp = $rowEmail["countryCode"];
                                            $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Activity', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        //Applicants
        if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_applicants")) {
            if ($_POST["applicants"] == "Y") {
                $staff="N" ;
                $students = $_POST["applicantsStudents"] ;
                $parents = $_POST["applicantsParents"] ;
                $applicantsWhere = "AND NOT gibbonApplicationForm.status IN ('Waiting List', 'Rejected', 'Withdrawn', 'Pending')";

                $choices=$_POST["applicantList"] ?? [];
                if (!empty($choices)) {
                    foreach ($choices as $t) {
                        try {
                            $data=array("gibbonMessengerID"=>$AI, "id"=>$t, "students" => $students, "parents" => $parents);
                            $sql="INSERT INTO gibbonMessengerTarget SET gibbonMessengerID=:gibbonMessengerID, type='Applicants', id=:id, students=:students, parents=:parents" ;
                            $result=$connection2->prepare($sql);
                            $result->execute($data);
                        }
                        catch(\PDOException $e) {
                            $partialFail=TRUE;
                        }

                        if ($email == "Y") {
                            if ($students == "Y") {
                                //Get applicant emails
                                try {
                                    $dataEmail=array("gibbonSchoolYearIDEntry"=>$t);
                                    $sqlEmail="SELECT DISTINCT email FROM gibbonApplicationForm WHERE NOT email='' AND gibbonSchoolYearIDEntry=:gibbonSchoolYearIDEntry $applicantsWhere" ;
                                    $resultEmail=$connection2->prepare($sqlEmail);
                                    $resultEmail->execute($dataEmail);
                                }
                                catch(\PDOException $e) { }
                                while ($rowEmail=$resultEmail->fetch()) {
                                    $this->reportAdd($emailReceipt, NULL, 'Applicants', $t, 'Email', $rowEmail["email"]);
                                }
                            }

                            if ($parents == "Y") {
                                // //Get parent 1 emails
                                // try {
                                //     $dataEmail=array("gibbonSchoolYearIDEntry"=>$t);
                                //     $sqlEmail="SELECT DISTINCT parent1email FROM gibbonApplicationForm WHERE NOT parent1email='' AND gibbonSchoolYearIDEntry=:gibbonSchoolYearIDEntry $applicantsWhere" ;
                                //     $resultEmail=$connection2->prepare($sqlEmail);
                                //     $resultEmail->execute($dataEmail);
                                // }
                                // catch(\PDOException $e) { }
                                // while ($rowEmail=$resultEmail->fetch()) {
                                //     $this->reportAdd($emailReceipt, NULL, 'Applicants', $t, 'Email', $rowEmail["parent1email"]);
                                // }

                                // //Get parent 2 emails
                                // try {
                                //     $dataEmail=array("gibbonSchoolYearIDEntry"=>$t);
                                //     $sqlEmail="SELECT DISTINCT parent2email FROM gibbonApplicationForm WHERE NOT parent2email='' AND gibbonSchoolYearIDEntry=:gibbonSchoolYearIDEntry $applicantsWhere" ;
                                //     $resultEmail=$connection2->prepare($sqlEmail);
                                //     $resultEmail->execute($dataEmail);
                                // }
                                // catch(\PDOException $e) { }
                                // while ($rowEmail=$resultEmail->fetch()) {
                                //     $this->reportAdd($emailReceipt, NULL, 'Applicants', $t, 'Email', $rowEmail["parent2email"]);
                                // }

                                //Get parent ID emails (when no family in system, but user is in system)
                                try {
                                    $dataEmail=array("gibbonSchoolYearIDEntry"=>$t);
                                    $sqlEmail="SELECT gibbonPerson.email, gibbonPerson.gibbonPersonID FROM gibbonApplicationForm JOIN gibbonPerson ON (gibbonApplicationForm.parent1gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT parent1gibbonPersonID='' AND gibbonSchoolYearIDEntry=:gibbonSchoolYearIDEntry $applicantsWhere" ;
                                    $resultEmail=$connection2->prepare($sqlEmail);
                                    $resultEmail->execute($dataEmail);
                                }
                                catch(\PDOException $e) { }
                                while ($rowEmail=$resultEmail->fetch()) {
                                    $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Applicants', $t, 'Email', $rowEmail["email"]);
                                }

                                //Get family emails
                                try {
                                    $dataEmail=array("gibbonSchoolYearIDEntry"=>$t);
                                    $sqlEmail="SELECT * FROM gibbonApplicationForm WHERE NOT gibbonFamilyID='' AND gibbonSchoolYearIDEntry=:gibbonSchoolYearIDEntry $applicantsWhere" ;
                                    $resultEmail=$connection2->prepare($sqlEmail);
                                    $resultEmail->execute($dataEmail);
                                }
                                catch(\PDOException $e) { }
                                while ($rowEmail=$resultEmail->fetch()) {
                                    try {
                                        $dataEmail2=array("gibbonFamilyID"=>$rowEmail["gibbonFamilyID"]);
                                        $sqlEmail2="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT email='' AND (status='Full' OR status='Expected') AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactEmail='Y'" ;
                                        $resultEmail2=$connection2->prepare($sqlEmail2);
                                        $resultEmail2->execute($dataEmail2);
                                    }
                                    catch(\PDOException $e) { }
                                    while ($rowEmail2=$resultEmail2->fetch()) {
                                        $this->reportAdd($emailReceipt, $rowEmail2['gibbonPersonID'], 'Applicants', $t, 'Email', $rowEmail2["email"]);
                                    }
                                }
                            }
                        }
                        if ($sms=="Y" AND $countryCode!="") {
                            if ($students == "Y") {
                                //Get applicant phone numbers
                                try {
                                    $dataEmail=array("gibbonSchoolYearIDEntry"=>$t);
                                    $sqlEmail="(SELECT phone1 AS phone, phone1CountryCode AS countryCode FROM gibbonApplicationForm WHERE NOT phone1='' AND phone1Type='Mobile' AND gibbonSchoolYearIDEntry=:gibbonSchoolYearIDEntry $applicantsWhere)" ;
                                    $sqlEmail.=" UNION (SELECT phone2 AS phone, phone2CountryCode AS countryCode FROM gibbonApplicationForm WHERE NOT phone2='' AND phone2Type='Mobile' AND gibbonSchoolYearIDEntry=:gibbonSchoolYearIDEntry $applicantsWhere)" ;
                                    $resultEmail=$connection2->prepare($sqlEmail);
                                    $resultEmail->execute($dataEmail);
                                }
                                catch(\PDOException $e) { }
                                while ($rowEmail=$resultEmail->fetch()) {
                                    $countryCodeTemp = $countryCode;
                                    if ($rowEmail["countryCode"]=="")
                                        $countryCodeTemp = $rowEmail["countryCode"];
                                    $this->reportAdd($emailReceipt, NULL, 'Applicants', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
                                }
                            }

                            if ($parents == "Y") {
                                //Get parent 1 numbers
                                try {
                                    $dataEmail=array("gibbonSchoolYearIDEntry"=>$t);
                                    $sqlEmail="(SELECT CONCAT(parent1phone1CountryCode,parent1phone1) AS phone, parent1phone1CountryCode AS countryCode FROM gibbonApplicationForm WHERE NOT parent1phone1='' AND parent1phone1Type='Mobile' AND parent1phone1CountryCode='$countryCode' AND gibbonSchoolYearIDEntry=:gibbonSchoolYearIDEntry $applicantsWhere)" ;
                                    $sqlEmail.=" UNION (SELECT CONCAT(parent1phone2CountryCode,parent1phone2) AS phone, parent1phone2CountryCode AS countryCode FROM gibbonApplicationForm WHERE NOT parent1phone2='' AND parent1phone2Type='Mobile' AND parent1phone2CountryCode='$countryCode' AND gibbonSchoolYearIDEntry=:gibbonSchoolYearIDEntry $applicantsWhere)" ;
                                    $resultEmail=$connection2->prepare($sqlEmail);
                                    $resultEmail->execute($dataEmail);
                                }
                                catch(\PDOException $e) { }
                                while ($rowEmail=$resultEmail->fetch()) {
                                    $countryCodeTemp = $countryCode;
                                    if ($rowEmail["countryCode"]=="")
                                        $countryCodeTemp = $rowEmail["countryCode"];
                                    $this->reportAdd($emailReceipt, NULL, 'Applicants', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
                                }

                                //Get parent 2 numbers
                                try {
                                    $dataEmail=array("gibbonSchoolYearIDEntry"=>$t);
                                    $sqlEmail="(SELECT CONCAT(parent2phone1CountryCode,parent2phone1) AS phone, parent2phone1CountryCode AS countryCode FROM gibbonApplicationForm WHERE NOT parent2phone1='' AND parent2phone1Type='Mobile' AND parent2phone1CountryCode='$countryCode' AND gibbonSchoolYearIDEntry=:gibbonSchoolYearIDEntry $applicantsWhere)" ;
                                    $sqlEmail.=" UNION (SELECT CONCAT(parent2phone2CountryCode,parent2phone2) AS phone, parent2phone2CountryCode AS countryCode FROM gibbonApplicationForm WHERE NOT parent2phone2='' AND parent2phone2Type='Mobile' AND parent2phone2CountryCode='$countryCode' AND gibbonSchoolYearIDEntry=:gibbonSchoolYearIDEntry $applicantsWhere)" ;
                                    $resultEmail=$connection2->prepare($sqlEmail);
                                    $resultEmail->execute($dataEmail);
                                }
                                catch(\PDOException $e) { }
                                while ($rowEmail=$resultEmail->fetch()) {
                                    $countryCodeTemp = $countryCode;
                                    if ($rowEmail["countryCode"]=="")
                                        $countryCodeTemp = $rowEmail["countryCode"];
                                    $this->reportAdd($emailReceipt, NULL, 'Applicants', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
                                }

                                //Get parent ID numbers (when no family in system, but user is in system)
                                try {
                                    $dataEmail=array("gibbonSchoolYearIDEntry"=>$t);
                                    $sqlEmail="(SELECT CONCAT(gibbonPerson.phone1CountryCode,gibbonPerson.phone1) AS phone, gibbonPerson.gibbonPersonID FROM gibbonApplicationForm JOIN gibbonPerson ON (gibbonApplicationForm.parent1gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT gibbonPerson.phone1='' AND gibbonPerson.phone1Type='Mobile' AND gibbonPerson.phone1CountryCode='$countryCode' AND NOT parent1gibbonPersonID='' AND gibbonSchoolYearIDEntry=:gibbonSchoolYearIDEntry $applicantsWhere)" ;
                                    $sqlEmail.=" UNION (SELECT CONCAT(gibbonPerson.phone2CountryCode,gibbonPerson.phone2) AS phone, gibbonPerson.gibbonPersonID FROM gibbonApplicationForm JOIN gibbonPerson ON (gibbonApplicationForm.parent1gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT gibbonPerson.phone2='' AND gibbonPerson.phone2Type='Mobile' AND gibbonPerson.phone2CountryCode='$countryCode' AND NOT parent1gibbonPersonID='' AND gibbonSchoolYearIDEntry=:gibbonSchoolYearIDEntry $applicantsWhere)" ;
                                    $sqlEmail.=" UNION (SELECT CONCAT(gibbonPerson.phone3CountryCode,gibbonPerson.phone3) AS phone, gibbonPerson.gibbonPersonID FROM gibbonApplicationForm JOIN gibbonPerson ON (gibbonApplicationForm.parent1gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT gibbonPerson.phone3='' AND gibbonPerson.phone3Type='Mobile' AND gibbonPerson.phone3CountryCode='$countryCode' AND NOT parent1gibbonPersonID='' AND gibbonSchoolYearIDEntry=:gibbonSchoolYearIDEntry $applicantsWhere)" ;
                                    $sqlEmail.=" UNION (SELECT CONCAT(gibbonPerson.phone4CountryCode,gibbonPerson.phone4) AS phone, gibbonPerson.gibbonPersonID FROM gibbonApplicationForm JOIN gibbonPerson ON (gibbonApplicationForm.parent1gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT gibbonPerson.phone4='' AND gibbonPerson.phone4Type='Mobile' AND gibbonPerson.phone4CountryCode='$countryCode' AND NOT parent1gibbonPersonID='' AND gibbonSchoolYearIDEntry=:gibbonSchoolYearIDEntry $applicantsWhere)" ;
                                    $resultEmail=$connection2->prepare($sqlEmail);
                                    $resultEmail->execute($dataEmail);
                                }
                                catch(\PDOException $e) { }
                                while ($rowEmail=$resultEmail->fetch()) {
                                    $countryCodeTemp = $countryCode;
                                    if ($rowEmail["countryCode"]=="")
                                        $countryCodeTemp = $rowEmail["countryCode"];
                                    $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Applicants', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
                                }

                                //Get family numbers
                                try {
                                    $dataEmail=array("gibbonSchoolYearIDEntry"=>$t);
                                    $sqlEmail="SELECT * FROM gibbonApplicationForm WHERE NOT gibbonFamilyID='' AND gibbonSchoolYearIDEntry=:gibbonSchoolYearIDEntry $applicantsWhere" ;
                                    $resultEmail=$connection2->prepare($sqlEmail);
                                    $resultEmail->execute($dataEmail);
                                }
                                catch(\PDOException $e) { }
                                while ($rowEmail=$resultEmail->fetch()) {
                                    try {
                                        $dataEmail2=array("gibbonFamilyID"=>$rowEmail["gibbonFamilyID"]);
                                        $sqlEmail2="(SELECT CONCAT(gibbonPerson.phone1CountryCode,gibbonPerson.phone1) AS phone, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT gibbonPerson.phone1='' AND gibbonPerson.phone1Type='Mobile' AND gibbonPerson.phone1CountryCode='$countryCode' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID)" ;
                                        $sqlEmail2.=" UNION (SELECT CONCAT(gibbonPerson.phone2CountryCode,gibbonPerson.phone2) AS phone, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT gibbonPerson.phone2='' AND gibbonPerson.phone2Type='Mobile' AND gibbonPerson.phone2CountryCode='$countryCode' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID)" ;
                                        $sqlEmail2.=" UNION (SELECT CONCAT(gibbonPerson.phone3CountryCode,gibbonPerson.phone3) AS phone, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT gibbonPerson.phone3='' AND gibbonPerson.phone3Type='Mobile' AND gibbonPerson.phone3CountryCode='$countryCode' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID)" ;
                                        $sqlEmail2.=" UNION (SELECT CONCAT(gibbonPerson.phone4CountryCode,gibbonPerson.phone4) AS phone, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT gibbonPerson.phone4='' AND gibbonPerson.phone4Type='Mobile' AND gibbonPerson.phone4CountryCode='$countryCode' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID)" ;
                                        $resultEmail2=$connection2->prepare($sqlEmail2);
                                        $resultEmail2->execute($dataEmail2);
                                    }
                                    catch(\PDOException $e) { }
                                    while ($rowEmail2=$resultEmail2->fetch()) {
                                        $countryCodeTemp = $countryCode;
                                        if ($rowEmail2["countryCode"]=="")
                                            $countryCodeTemp = $rowEmail2["countryCode"];
                                        $this->reportAdd($emailReceipt, $rowEmail2['gibbonPersonID'], 'Applicants', $t, 'SMS', $countryCodeTemp.$rowEmail2["phone"]);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        //Houses
        if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_houses_all") OR isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_houses_my")) {
            if ($_POST["houses"]=="Y") {
                $choices=$_POST["houseList"] ?? [];
                if (!empty($choices)) {
                    foreach ($choices as $t) {
                        try {
                            $data=array("gibbonMessengerID"=>$AI, "id"=>$t);
                            $sql="INSERT INTO gibbonMessengerTarget SET gibbonMessengerID=:gibbonMessengerID, type='Houses', id=:id" ;
                            $result=$connection2->prepare($sql);
                            $result->execute($data);
                        }
                        catch(\PDOException $e) {
                            $partialFail=TRUE;
                        }

                        if ($email=="Y") {
                            try {
                                $dataEmail=array("gibbonHouseID"=>$t);
                                $sqlEmail="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE NOT email='' AND gibbonHouseID=:gibbonHouseID AND status='Full'" ;
                                $resultEmail=$connection2->prepare($sqlEmail);
                                $resultEmail->execute($dataEmail);
                            }
                            catch(\PDOException $e) { }
                            while ($rowEmail=$resultEmail->fetch()) {
                                $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Houses', $t, 'Email', $rowEmail["email"]);
                            }
                        }
                        if ($sms=="Y" AND $countryCode!="") {
                            try {
                                $dataEmail=array("gibbonHouseID"=>$t);
                                $sqlEmail="(SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE NOT phone1='' AND phone1Type='Mobile' AND gibbonHouseID=:gibbonHouseID AND status='Full')" ;
                                $sqlEmail.=" UNION (SELECT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE NOT phone2='' AND phone2Type='Mobile' AND gibbonHouseID=:gibbonHouseID AND status='Full')" ;
                                $sqlEmail.=" UNION (SELECT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE NOT phone3='' AND phone3Type='Mobile' AND gibbonHouseID=:gibbonHouseID AND status='Full')" ;
                                $sqlEmail.=" UNION (SELECT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE NOT phone4='' AND phone4Type='Mobile' AND gibbonHouseID=:gibbonHouseID AND status='Full')" ;
                                $resultEmail=$connection2->prepare($sqlEmail);
                                $resultEmail->execute($dataEmail);
                            }
                            catch(\PDOException $e) { }
                            while ($rowEmail=$resultEmail->fetch()) {
                                $countryCodeTemp = $countryCode;
                                if ($rowEmail["countryCode"]=="")
                                    $countryCodeTemp = $rowEmail["countryCode"];
                                $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Houses', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
                            }
                        }
                    }
                }
            }
        }

        //Transport
        if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_transport_any")) {
            if ($_POST["transport"]=="Y") {
                $staff=$_POST["transportStaff"] ;
                $students=$_POST["transportStudents"] ;
                $parents="N" ;
                if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_transport_parents")) {
                    $parents=$_POST["transportParents"] ;
                }
                $choices=$_POST["transports"] ?? [];
                if (!empty($choices)) {
                    foreach ($choices as $t) {
                        try {
                            $data=array("AI"=>$AI, "t"=>$t, "staff"=>$staff, "students"=>$students, "parents"=>$parents);
                            $sql="INSERT INTO gibbonMessengerTarget SET gibbonMessengerID=:AI, type='Transport', id=:t, staff=:staff, students=:students, parents=:parents" ;
                            $result=$connection2->prepare($sql);
                            $result->execute($data);
                        }
                        catch(\PDOException $e) {
                            $partialFail=TRUE;
                        }

                        //Get email addresses
                        if ($email=="Y") {
                            if ($staff=="Y") {
                                try {
                                    $dataEmail=array('transport'=>$t);
                                    $sqlEmail="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT email='' AND status='Full' AND FIND_IN_SET(:transport, transport)" ;
                                    $resultEmail=$connection2->prepare($sqlEmail);
                                    $resultEmail->execute($dataEmail);
                                }
                                catch(\PDOException $e) { }
                                while ($rowEmail=$resultEmail->fetch()) {
                                    $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Transport', $t, 'Email', $rowEmail["email"]);
                                }
                            }
                            if ($students=="Y") {
                                try {
                                    $dataEmail=array("gibbonSchoolYearID"=>$this->session->get('gibbonSchoolYearID'), 'transport'=>$t);
                                    $sqlEmail="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT email='' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND FIND_IN_SET(:transport, transport)" ;
                                    $resultEmail=$connection2->prepare($sqlEmail);
                                    $resultEmail->execute($dataEmail);
                                }
                                catch(\PDOException $e) { }
                                while ($rowEmail=$resultEmail->fetch()) {
                                    $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Transport', $t, 'Email', $rowEmail["email"]);
                                }
                            }
                            if ($parents=="Y") {
                                try {
                                    $dataStudents=array("gibbonSchoolYearID"=>$this->session->get('gibbonSchoolYearID'), 'transport'=>$t);
                                    $sqlStudents="SELECT gibbonPerson.gibbonPersonID, surname, preferredName FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND FIND_IN_SET(:transport, transport)" ;
                                    $resultStudents=$connection2->prepare($sqlStudents);
                                    $resultStudents->execute($dataStudents);
                                }
                                catch(\PDOException $e) { }
                                while ($rowStudents=$resultStudents->fetch()) {
                                    try {
                                        $dataFamily=array("gibbonPersonID"=>$rowStudents["gibbonPersonID"]);
                                        $sqlFamily="SELECT DISTINCT gibbonFamily.gibbonFamilyID FROM gibbonFamily JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID" ;
                                        $resultFamily=$connection2->prepare($sqlFamily);
                                        $resultFamily->execute($dataFamily);
                                    }
                                    catch(\PDOException $e) { }
                                    while ($rowFamily=$resultFamily->fetch()) {
                                        try {
                                            $dataEmail=array("gibbonFamilyID"=>$rowFamily["gibbonFamilyID"] );
                                            $sqlEmail="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT email='' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactEmail='Y'" ;
                                            $resultEmail=$connection2->prepare($sqlEmail);
                                            $resultEmail->execute($dataEmail);
                                        }
                                        catch(\PDOException $e) { }
                                        while ($rowEmail=$resultEmail->fetch()) {
                                            $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Transport', $t, 'Email', $rowEmail["email"], $rowStudents['gibbonPersonID'], Format::name('', $rowStudents['preferredName'], $rowStudents['surname'], 'Student'));
                                        }
                                    }
                                }
                            }
                        }
                        if ($sms=="Y" AND $countryCode!="") {
                            if ($staff=="Y") {
                                try {
                                    $dataEmail=array('transport'=>$t);
                                    $sqlEmail="(SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone1='' AND phone1Type='Mobile' AND status='Full' AND FIND_IN_SET(:transport, transport))" ;
                                    $sqlEmail.=" UNION (SELECT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone2='' AND phone2Type='Mobile' AND status='Full' AND FIND_IN_SET(:transport, transport))" ;
                                    $sqlEmail.=" UNION (SELECT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone3='' AND phone3Type='Mobile' AND status='Full' AND FIND_IN_SET(:transport, transport))" ;
                                    $sqlEmail.=" UNION (SELECT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone4='' AND phone4Type='Mobile' AND status='Full' AND FIND_IN_SET(:transport, transport))" ;
                                    $resultEmail=$connection2->prepare($sqlEmail);
                                    $resultEmail->execute($dataEmail);
                                }
                                catch(\PDOException $e) { }
                                while ($rowEmail=$resultEmail->fetch()) {
                                    $countryCodeTemp = $countryCode;
                                    if ($rowEmail["countryCode"]=="")
                                        $countryCodeTemp = $rowEmail["countryCode"];
                                    $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Transport', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
                                }
                            }
                            if ($students=="Y") {
                                try {
                                    $dataEmail=array("gibbonSchoolYearID"=>$this->session->get('gibbonSchoolYearID'), 'transport'=>$t);
                                    $sqlEmail="(SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone1='' AND phone1Type='Mobile' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND FIND_IN_SET(:transport, transport))" ;
                                    $sqlEmail.=" UNION (SELECT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone2='' AND phone2Type='Mobile' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND FIND_IN_SET(:transport, transport))" ;
                                    $sqlEmail.=" UNION (SELECT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone3='' AND phone3Type='Mobile' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND FIND_IN_SET(:transport, transport))" ;
                                    $sqlEmail.=" UNION (SELECT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone4='' AND phone4Type='Mobile' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND FIND_IN_SET(:transport, transport))" ;
                                    $resultEmail=$connection2->prepare($sqlEmail);
                                    $resultEmail->execute($dataEmail);
                                }
                                catch(\PDOException $e) { }
                                while ($rowEmail=$resultEmail->fetch()) {
                                    $countryCodeTemp = $countryCode;
                                    if ($rowEmail["countryCode"]=="")
                                        $countryCodeTemp = $rowEmail["countryCode"];
                                    $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Transport', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
                                }
                            }
                            if ($parents=="Y") {
                                try {
                                    $dataStudents=array("gibbonSchoolYearID"=>$this->session->get('gibbonSchoolYearID'), 'transport'=>$t);
                                    $sqlStudents="SELECT gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND FIND_IN_SET(:transport, transport)" ;
                                    $resultStudents=$connection2->prepare($sqlStudents);
                                    $resultStudents->execute($dataStudents);
                                }
                                catch(\PDOException $e) { }
                                while ($rowStudents=$resultStudents->fetch()) {
                                    try {
                                        $dataFamily=array("gibbonPersonID"=>$rowStudents["gibbonPersonID"]);
                                        $sqlFamily="SELECT DISTINCT gibbonFamily.gibbonFamilyID FROM gibbonFamily JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID" ;
                                        $resultFamily=$connection2->prepare($sqlFamily);
                                        $resultFamily->execute($dataFamily);
                                    }
                                    catch(\PDOException $e) { }
                                    while ($rowFamily=$resultFamily->fetch()) {
                                        try {
                                            $dataEmail=array("gibbonFamilyID"=>$rowFamily["gibbonFamilyID"] );
                                            $sqlEmail="(SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone1='' AND phone1Type='Mobile' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactSMS='Y')" ;
                                            $sqlEmail.=" UNION (SELECT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone2='' AND phone2Type='Mobile' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactSMS='Y')" ;
                                            $sqlEmail.=" UNION (SELECT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone3='' AND phone3Type='Mobile' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactSMS='Y')" ;
                                            $sqlEmail.=" UNION (SELECT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone4='' AND phone4Type='Mobile' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactSMS='Y')" ;
                                            $resultEmail=$connection2->prepare($sqlEmail);
                                            $resultEmail->execute($dataEmail);
                                        }
                                        catch(\PDOException $e) { }
                                        while ($rowEmail=$resultEmail->fetch()) {
                                            $countryCodeTemp = $countryCode;
                                            if ($rowEmail["countryCode"]=="")
                                                $countryCodeTemp = $rowEmail["countryCode"];
                                            $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Transport', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        //Target Absent students / Attendance Status
        if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_attendance")) {
            if ($_POST["attendance"]=="Y") {
                $choices = $_POST["attendanceStatus"] ?? [];
                $students = $_POST["attendanceStudents"] ?? [];
                $parents = $_POST["attendanceParents"] ?? [];
                if (!empty($choices)) {
                    foreach ($choices as $t) {
                        try {
                            $data=array("AI"=>$AI, "t"=>$t, "students"=>$students, "parents"=>$parents);
                            $sql="INSERT INTO gibbonMessengerTarget SET gibbonMessengerID=:AI, type='Attendance', id=:t, students=:students, parents=:parents" ;
                            $result=$connection2->prepare($sql);
                            $result->execute($data);
                        }
                        catch(\PDOException $e) {
                            $partialFail=TRUE;
                        }
                    }
                    //Get all logs by student, with latest log entry first.
                    try {
                        $data=array("selectedDate"=>date('Y-m-d'), "gibbonSchoolYearID"=>$this->session->get('gibbonSchoolYearID'), "nowDate"=>date("Y-m-d"));
                        $sql="SELECT galp.gibbonPersonID, galp.gibbonAttendanceLogPersonID, galp.type, galp.date FROM gibbonAttendanceLogPerson AS galp JOIN gibbonStudentEnrolment AS gse ON (galp.gibbonPersonID=gse.gibbonPersonID) JOIN gibbonPerson AS gp ON (gse.gibbonPersonID=gp.gibbonPersonID) WHERE gp.status='Full' AND (gp.dateStart IS NULL OR gp.dateStart<=:nowDate) AND (gp.dateEnd IS NULL OR gp.dateEnd>=:nowDate) AND gse.gibbonSchoolYearID=:gibbonSchoolYearID AND galp.date=:selectedDate ORDER BY galp.gibbonPersonID, gibbonAttendanceLogPersonID DESC" ;
                        $result=$connection2->prepare($sql);
                        $result->execute($data);
                    }
                    catch(\PDOException $e) { }

                    if ($result->rowCount()>=1) { //Log the personIDs of the students whose latest attendance log is in list of choices submitted by user
                        $selectedStudents=array();
                        $currentStudent="";
                        $lastStudent="";
                        while ($row=$result->fetch()) {
                        $currentStudent=$row["gibbonPersonID"] ;
                        if (in_array($row["type"], $choices) AND $currentStudent!=$lastStudent) {
                            $selectedStudents[]=$currentStudent ;
                        }
                        $lastStudent=$currentStudent ;
                        }

                        if (count($selectedStudents)>=1) {
                        //Get emails
                        if ($email=="Y") {
                            if ($parents=="Y") {
                            try {
                                $dataEmail=array("gibbonSchoolYearID"=>$this->session->get('gibbonSchoolYearID'), "gibbonPersonIDs"=>implode(",",$selectedStudents));
                                $sqlEmail="SELECT DISTINCT parent.email, parent.gibbonPersonID, student.gibbonPersonID AS gibbonPersonIDStudent, student.surname, student.preferredName
                                    FROM gibbonPerson AS student
                                        JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=student.gibbonPersonID)
                                        JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonPersonID=student.gibbonPersonID)
                                        JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID)
                                        JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID)
                                        JOIN gibbonPerson AS parent ON (gibbonFamilyAdult.gibbonPersonID=parent.gibbonPersonID)
                                    WHERE
                                        NOT parent.email=''
                                        AND student.status='Full'
                                        AND (student.dateStart IS NULL OR student.dateStart<='" . date("Y-m-d") . "')
                                        AND (student.dateEnd IS NULL  OR student.dateEnd>='" . date("Y-m-d") . "')
                                        AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
                                        AND FIND_IN_SET(student.gibbonPersonID, :gibbonPersonIDs)
                                        AND parent.status='Full'
                                        AND contactEmail='Y'" ;
                                $resultEmail=$connection2->prepare($sqlEmail);
                                $resultEmail->execute($dataEmail);
                            }
                            catch(\PDOException $e) {}

                            while ($rowEmail=$resultEmail->fetch()) { //Add emails to list of receivers
                                $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Attendance', $t, 'Email', $rowEmail["email"], $rowEmail['gibbonPersonIDStudent'], Format::name('', $rowEmail['preferredName'], $rowEmail['surname'], 'Student'));
                            }
                            }
                            if ($students=="Y") {
                            try { //Get the email for each student
                                $dataEmail=array("gibbonSchoolYearID"=>$this->session->get('gibbonSchoolYearID'), "gibbonPersonIDs"=>implode(",", $selectedStudents));
                                $sqlEmail="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT email='' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.gibbonPersonID AND FIND_IN_SET(gibbonPerson.gibbonPersonID, :gibbonPersonIDs)";
                                $resultEmail=$connection2->prepare($sqlEmail);
                                $resultEmail->execute($dataEmail);
                            }
                            catch(\PDOException $e) { }
                            while ($rowEmail=$resultEmail->fetch()) { //Add emails to list of receivers
                                $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Attendance', $t, 'Email', $rowEmail["email"]);
                            }
                            }
                        } //end get emails
                        //Get SMS
                        if ($sms=="Y" AND $countryCode!="") {
                            if ($parents=="Y") {
                            try { //Get the familyIDs for each student logged
                            $dataFamily=array();
                            $sqlFamily="SELECT DISTINCT gibbonFamilyID FROM gibbonFamilyChild WHERE gibbonPersonID IN (".implode(",",$selectedStudents).")" ;
                            $resultFamily=$connection2->prepare($sqlFamily);
                            $resultFamily->execute($dataFamily);
                            $resultFamilies = $resultFamily->fetchAll();
                            }
                            catch(\PDOException $e) { }

                            foreach ($resultFamilies as $rowFamily) { //Get the people for each familyID
                                try {
                                $dataPerson=array("gibbonFamilyID"=>$rowFamily["gibbonFamilyID"] );
                                $sqlPerson="SELECT DISTINCT gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactSMS='Y'" ;
                                $resultPerson=$connection2->prepare($sqlPerson);
                                $resultPerson->execute($dataPerson);
                                }
                                catch(\PDOException $e) { }
                                while ($rowPerson=$resultPerson->fetch()) { //Add phone numbers to SMS receivers
                                try {
                                    $dataSMS=array("gibbonPersonID"=>$rowPerson["gibbonPersonID"] );
                                    $sqlSMS="(SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE NOT phone1='' AND phone1Type='Mobile' AND gibbonPersonID=:gibbonPersonID)" ;
                                    $sqlSMS.=" UNION (SELECT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE NOT phone2='' AND phone2Type='Mobile' AND gibbonPersonID=:gibbonPersonID)" ;
                                    $sqlSMS.=" UNION (SELECT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE NOT phone3='' AND phone3Type='Mobile' AND gibbonPersonID=:gibbonPersonID)" ;
                                    $sqlSMS.=" UNION (SELECT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE NOT phone4='' AND phone4Type='Mobile' AND gibbonPersonID=:gibbonPersonID)" ;
                                    $resultSMS=$connection2->prepare($sqlSMS);
                                    $resultSMS->execute($dataSMS);
                                }
                                catch(\PDOException $e) { }
                                while ($rowSMS=$resultSMS->fetch()) {
                                    $countryCodeTemp = $countryCode;
                                        if ($rowSMS["countryCode"]=="")
                                            $countryCodeTemp = $rowSMS["countryCode"];
                                        $this->reportAdd($emailReceipt, $rowSMS['gibbonPersonID'], 'Attendance', $t, 'SMS', $countryCodeTemp.$rowSMS["phone"]);
                                }
                                }
                            }
                            }
                            if ($students=="Y") {
                            try { //Get the phone numbers for each student
                                foreach ($selectedStudents as $t) {
                                $dataSMS=array("gibbonPersonID"=>$t);
                                $sqlSMS="(SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE NOT phone1='' AND phone1Type='Mobile' AND gibbonPersonID=:gibbonPersonID AND status='Full')" ;
                                $sqlSMS.=" UNION (SELECT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE NOT phone2='' AND phone2Type='Mobile' AND gibbonPersonID=:gibbonPersonID AND status='Full')" ;
                                $sqlSMS.=" UNION (SELECT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE NOT phone3='' AND phone3Type='Mobile' AND gibbonPersonID=:gibbonPersonID AND status='Full')" ;
                                $sqlSMS.=" UNION (SELECT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE NOT  phone4='' AND phone4Type='Mobile' AND gibbonPersonID=:gibbonPersonID AND status='Full')" ;
                                $resultSMS=$connection2->prepare($sqlSMS);
                                $resultSMS->execute($dataSMS);
                                }
                            }
                            catch(\PDOException $e) { }
                            while ($rowSMS=$resultSMS->fetch()) {
                                $countryCodeTemp = $countryCode;
                                    if ($rowSMS["countryCode"]=="")
                                        $countryCodeTemp = $rowSMS["countryCode"];
                                    $this->reportAdd($emailReceipt, $rowSMS['gibbonPersonID'], 'Attendance', $t, 'SMS', $countryCodeTemp.$rowSMS["phone"]);
                            }
                            }
                        } //END SMS
                        }
                    }
                    }
                }
                }//END Target Absent students / Attendance Status


        //Groups
        if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_groups_my") OR isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_groups_any")) {
            if ($_POST["group"]=="Y") {
                $staff=$_POST["groupsStaff"] ;
                $students=$_POST["groupsStudents"] ;
                $parents="N" ;
                if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_groups_parents")) {
                    $parents=$_POST["groupsParents"] ;
                }
                $choices=$_POST["groups"] ?? [];
                if (!empty($choices)) {
                    foreach ($choices as $t) {
                        try {
                            $data=array("gibbonMessengerID"=>$AI, "id"=>$t, "staff"=>$staff, "students"=>$students, "parents"=>$parents);
                            $sql="INSERT INTO gibbonMessengerTarget SET gibbonMessengerID=:gibbonMessengerID, type='Group', id=:id, staff=:staff, students=:students, parents=:parents" ;
                            $result=$connection2->prepare($sql);
                            $result->execute($data);
                        }
                        catch(\PDOException $e) {
                            $partialFail=TRUE;
                        }

                        //Get email addresses
                        if ($email=="Y") {
                            if ($staff=="Y") {
                                try {
                                    $dataEmail=array("gibbonGroupID"=>$t);
                                    $sqlEmail="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonGroupPerson ON (gibbonGroupPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonGroup ON (gibbonGroupPerson.gibbonGroupID=gibbonGroup.gibbonGroupID) WHERE NOT email='' AND status='Full' AND gibbonGroup.gibbonGroupID=:gibbonGroupID" ;
                                    $resultEmail=$connection2->prepare($sqlEmail);
                                    $resultEmail->execute($dataEmail);
                                }
                                catch(\PDOException $e) {}
                                while ($rowEmail=$resultEmail->fetch()) {
                                    $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Group', $t, 'Email', $rowEmail["email"]);
                                }
                            }
                            if ($students=="Y") {
                                try {
                                    $dataEmail=array("gibbonGroupID"=>$t, 'gibbonSchoolYearID' => $this->session->get('gibbonSchoolYearID'));
                                    $sqlEmail="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonGroupPerson ON (gibbonGroupPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonGroup ON (gibbonGroupPerson.gibbonGroupID=gibbonGroup.gibbonGroupID) WHERE NOT email='' AND status='Full' AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonGroup.gibbonGroupID=:gibbonGroupID" ;
                                    $resultEmail=$connection2->prepare($sqlEmail);
                                    $resultEmail->execute($dataEmail);
                                }
                                catch(\PDOException $e) { }
                                while ($rowEmail=$resultEmail->fetch()) {
                                    $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Group', $t, 'Email', $rowEmail["email"]);
                                }
                            }
                            if ($parents=="Y") {
                                try {
                                    $dataEmail=array("gibbonGroupID"=>$t, 'gibbonSchoolYearID' => $this->session->get('gibbonSchoolYearID'), "gibbonGroupID2"=>$t);
                                    $sqlEmail="(SELECT DISTINCT email, gibbonPerson.gibbonPersonID, NULL AS gibbonPersonIDStudent, NULL AS surname, NULL AS preferredName
                                        FROM gibbonPerson
                                            JOIN gibbonGroupPerson ON (gibbonGroupPerson.gibbonPersonID=gibbonPerson.gibbonPersonID)
                                            JOIN gibbonGroup ON (gibbonGroupPerson.gibbonGroupID=gibbonGroup.gibbonGroupID)
                                            JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID)
                                            JOIN gibbonFamily ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID)
                                        WHERE
                                            NOT email=''
                                            AND contactEmail='Y'
                                            AND gibbonPerson.status='Full'
                                            AND gibbonGroup.gibbonGroupID=:gibbonGroupID)
                                        UNION
                                        (SELECT DISTINCT parent.email, parent.gibbonPersonID, student.gibbonPersonID AS gibbonPersonIDStudent, student.surname, student.preferredName
                                            FROM gibbonGroupPerson
                                                JOIN gibbonGroup ON (gibbonGroupPerson.gibbonGroupID=gibbonGroup.gibbonGroupID)
                                                JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonPersonID=gibbonGroupPerson.gibbonPersonID)
                                                JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonFamilyChild.gibbonPersonID)
                                                JOIN gibbonPerson AS student ON (gibbonStudentEnrolment.gibbonPersonID=student.gibbonPersonID)
                                                JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID)
                                                JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID)
                                                JOIN gibbonPerson AS parent ON (gibbonFamilyAdult.gibbonPersonID=parent.gibbonPersonID)
                                            WHERE
                                                NOT parent.email=''
                                                AND contactEmail='Y'
                                                AND parent.status='Full'
                                                AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
                                                AND gibbonGroup.gibbonGroupID=:gibbonGroupID2)" ;
                                    $resultEmail=$connection2->prepare($sqlEmail);
                                    $resultEmail->execute($dataEmail);
                                }
                                catch(\PDOException $e) { }

                                while ($rowEmail=$resultEmail->fetch()) {
                                    $paddedID = ($rowEmail['gibbonPersonIDStudent'] == '') ? NULL : str_pad($rowEmail['gibbonPersonIDStudent'], 10, '0', STR_PAD_LEFT);
                                    $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Group', $t, 'Email', $rowEmail["email"], $paddedID, Format::name('', $rowEmail['preferredName'], $rowEmail['surname'], 'Student'));
                                }
                            }
                        }
                        if ($sms=="Y" AND $countryCode!="") {
                            if ($staff=="Y") {
                                try {
                                    $dataEmail=array("gibbonGroupID"=>$t);
                                    $sqlEmail="(SELECT DISTINCT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonGroupPerson ON (gibbonGroupPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonGroup ON (gibbonGroupPerson.gibbonGroupID=gibbonGroup.gibbonGroupID) WHERE NOT phone1='' AND phone1Type='Mobile' AND status='Full' AND gibbonGroup.gibbonGroupID=:gibbonGroupID)" ;
                                    $sqlEmail.=" UNION (SELECT DISTINCT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonGroupPerson ON (gibbonGroupPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonGroup ON (gibbonGroupPerson.gibbonGroupID=gibbonGroup.gibbonGroupID) WHERE NOT phone2='' AND phone2Type='Mobile' AND status='Full' AND gibbonGroup.gibbonGroupID=:gibbonGroupID)" ;
                                    $sqlEmail.=" UNION (SELECT DISTINCT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonGroupPerson ON (gibbonGroupPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonGroup ON (gibbonGroupPerson.gibbonGroupID=gibbonGroup.gibbonGroupID) WHERE NOT phone3='' AND phone3Type='Mobile' AND status='Full' AND gibbonGroup.gibbonGroupID=:gibbonGroupID)" ;
                                    $sqlEmail.=" UNION (SELECT DISTINCT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonGroupPerson ON (gibbonGroupPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonGroup ON (gibbonGroupPerson.gibbonGroupID=gibbonGroup.gibbonGroupID) WHERE NOT phone4='' AND phone4Type='Mobile' AND status='Full' AND gibbonGroup.gibbonGroupID=:gibbonGroupID)" ;
                                    $resultEmail=$connection2->prepare($sqlEmail);
                                    $resultEmail->execute($dataEmail);
                                }
                                catch(\PDOException $e) { }
                                while ($rowEmail=$resultEmail->fetch()) {
                                    $countryCodeTemp = $countryCode;
                                    if ($rowEmail["countryCode"]=="")
                                        $countryCodeTemp = $rowEmail["countryCode"];
                                    $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Group', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
                                }
                            }
                            if ($students=="Y") {
                                try {
                                    $dataEmail=array("gibbonGroupID"=>$t, 'gibbonSchoolYearID' => $this->session->get('gibbonSchoolYearID'));
                                    $sqlEmail="(SELECT DISTINCT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonGroupPerson ON (gibbonGroupPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonGroup ON (gibbonGroupPerson.gibbonGroupID=gibbonGroup.gibbonGroupID) WHERE NOT phone1='' AND phone1Type='Mobile' AND status='Full' AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonGroup.gibbonGroupID=:gibbonGroupID)" ;
                                    $sqlEmail.=" UNION (SELECT DISTINCT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonGroupPerson ON (gibbonGroupPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonGroup ON (gibbonGroupPerson.gibbonGroupID=gibbonGroup.gibbonGroupID) WHERE NOT phone2='' AND phone2Type='Mobile' AND status='Full' AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonGroup.gibbonGroupID=:gibbonGroupID)" ;
                                    $sqlEmail.=" UNION (SELECT DISTINCT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonGroupPerson ON (gibbonGroupPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonGroup ON (gibbonGroupPerson.gibbonGroupID=gibbonGroup.gibbonGroupID) WHERE NOT phone3='' AND phone3Type='Mobile' AND status='Full' AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonGroup.gibbonGroupID=:gibbonGroupID)" ;
                                    $sqlEmail.=" UNION (SELECT DISTINCT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonGroupPerson ON (gibbonGroupPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonGroup ON (gibbonGroupPerson.gibbonGroupID=gibbonGroup.gibbonGroupID) WHERE NOT phone4='' AND phone4Type='Mobile' AND status='Full' AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonGroup.gibbonGroupID=:gibbonGroupID)" ;
                                    $resultEmail=$connection2->prepare($sqlEmail);
                                    $resultEmail->execute($dataEmail);
                                }
                                catch(\PDOException $e) { }
                                while ($rowEmail=$resultEmail->fetch()) {
                                    $countryCodeTemp = $countryCode;
                                    if ($rowEmail["countryCode"]=="")
                                        $countryCodeTemp = $rowEmail["countryCode"];
                                    $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Group', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
                                }
                            }
                            if ($parents=="Y") {
                                try {
                                    $dataEmail=array("gibbonGroupID"=>$t, 'gibbonSchoolYearID' => $this->session->get('gibbonSchoolYearID'));
                                    $sqlEmail="(SELECT DISTINCT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonGroupPerson ON (gibbonGroupPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonGroup ON (gibbonGroupPerson.gibbonGroupID=gibbonGroup.gibbonGroupID) JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonFamily ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE NOT phone1='' AND phone1Type='Mobile' AND contactSMS='Y' AND gibbonPerson.status='Full' AND gibbonGroup.gibbonGroupID=:gibbonGroupID)";
                                    $sqlEmail.=" UNION (SELECT DISTINCT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonGroupPerson JOIN gibbonGroup ON (gibbonGroupPerson.gibbonGroupID=gibbonGroup.gibbonGroupID) JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonPersonID=gibbonGroupPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonFamilyChild.gibbonPersonID) JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonPerson ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone1='' AND phone1Type='Mobile' AND contactSMS='Y' AND gibbonPerson.status='Full' AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonGroup.gibbonGroupID=:gibbonGroupID)" ;
                                    $sqlEmail.=" UNION (SELECT DISTINCT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonGroupPerson ON (gibbonGroupPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonGroup ON (gibbonGroupPerson.gibbonGroupID=gibbonGroup.gibbonGroupID) JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonFamily ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE NOT phone2='' AND phone2Type='Mobile' AND contactSMS='Y' AND gibbonPerson.status='Full' AND gibbonGroup.gibbonGroupID=:gibbonGroupID)";
                                    $sqlEmail.=" UNION (SELECT DISTINCT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonGroupPerson JOIN gibbonGroup ON (gibbonGroupPerson.gibbonGroupID=gibbonGroup.gibbonGroupID) JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonPersonID=gibbonGroupPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonFamilyChild.gibbonPersonID) JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonPerson ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone2='' AND phone2Type='Mobile' AND contactSMS='Y' AND gibbonPerson.status='Full' AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonGroup.gibbonGroupID=:gibbonGroupID)" ;
                                    $sqlEmail.=" UNION (SELECT DISTINCT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonGroupPerson ON (gibbonGroupPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonGroup ON (gibbonGroupPerson.gibbonGroupID=gibbonGroup.gibbonGroupID) JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonFamily ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE NOT phone3='' AND phone3Type='Mobile' AND contactSMS='Y' AND gibbonPerson.status='Full' AND gibbonGroup.gibbonGroupID=:gibbonGroupID)";
                                    $sqlEmail.=" UNION (SELECT DISTINCT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonGroupPerson JOIN gibbonGroup ON (gibbonGroupPerson.gibbonGroupID=gibbonGroup.gibbonGroupID) JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonPersonID=gibbonGroupPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonFamilyChild.gibbonPersonID) JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonPerson ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone3='' AND phone3Type='Mobile' AND contactSMS='Y' AND gibbonPerson.status='Full' AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonGroup.gibbonGroupID=:gibbonGroupID)" ;
                                    $sqlEmail.=" UNION (SELECT DISTINCT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonGroupPerson ON (gibbonGroupPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonGroup ON (gibbonGroupPerson.gibbonGroupID=gibbonGroup.gibbonGroupID) JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonFamily ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE NOT phone4='' AND phone4Type='Mobile' AND contactSMS='Y' AND gibbonPerson.status='Full' AND gibbonGroup.gibbonGroupID=:gibbonGroupID)";
                                    $sqlEmail.=" UNION (SELECT DISTINCT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonGroupPerson JOIN gibbonGroup ON (gibbonGroupPerson.gibbonGroupID=gibbonGroup.gibbonGroupID) JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonPersonID=gibbonGroupPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonFamilyChild.gibbonPersonID) JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonPerson ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone4='' AND phone4Type='Mobile' AND contactSMS='Y' AND gibbonPerson.status='Full' AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonGroup.gibbonGroupID=:gibbonGroupID)" ;
                                    $resultEmail=$connection2->prepare($sqlEmail);
                                    $resultEmail->execute($dataEmail);
                                }
                                catch(\PDOException $e) {}
                                while ($rowEmail=$resultEmail->fetch()) {
                                    $countryCodeTemp = $countryCode;
                                    if ($rowEmail["countryCode"]=="")
                                        $countryCodeTemp = $rowEmail["countryCode"];
                                    $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Group', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
                                }
                            }
                        }
                    }
                }
            }
        }

        //Individuals
        if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_individuals")) {
            if ($_POST["individuals"]=="Y") {
                $choices=$_POST["individualList"] ?? [];
                if (!empty($choices)) {
                    foreach ($choices as $t) {
                        try {
                            $data=array("gibbonMessengerID"=>$AI, "id"=>$t);
                            $sql="INSERT INTO gibbonMessengerTarget SET gibbonMessengerID=:gibbonMessengerID, type='Individuals', id=:id" ;
                            $result=$connection2->prepare($sql);
                            $result->execute($data);
                        }
                        catch(\PDOException $e) {
                            $partialFail=TRUE;
                        }

                        if ($email=="Y") {
                            try {
                                $dataEmail=array("gibbonPersonID"=>$t);
                                $sqlEmail="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE NOT email='' AND gibbonPersonID=:gibbonPersonID AND status='Full'" ;
                                $resultEmail=$connection2->prepare($sqlEmail);
                                $resultEmail->execute($dataEmail);
                            }
                            catch(\PDOException $e) { }
                            while ($rowEmail=$resultEmail->fetch()) {
                                $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Individuals', $t, 'Email', $rowEmail["email"]);
                            }
                        }
                        if ($sms=="Y" AND $countryCode!="") {
                            try {
                                $dataEmail=array("gibbonPersonID"=>$t);
                                $sqlEmail="(SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE NOT phone1='' AND phone1Type='Mobile' AND gibbonPersonID=:gibbonPersonID AND status='Full')" ;
                                $sqlEmail.=" UNION (SELECT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE NOT phone2='' AND phone2Type='Mobile' AND gibbonPersonID=:gibbonPersonID AND status='Full')" ;
                                $sqlEmail.=" UNION (SELECT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE NOT phone3='' AND phone3Type='Mobile' AND gibbonPersonID=:gibbonPersonID AND status='Full')" ;
                                $sqlEmail.=" UNION (SELECT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE NOT phone4='' AND phone4Type='Mobile' AND gibbonPersonID=:gibbonPersonID AND status='Full')" ;
                                $resultEmail=$connection2->prepare($sqlEmail);
                                $resultEmail->execute($dataEmail);
                            }
                            catch(\PDOException $e) { }
                            while ($rowEmail=$resultEmail->fetch()) {
                                $countryCodeTemp = $countryCode;
                                if ($rowEmail["countryCode"]=="")
                                    $countryCodeTemp = $rowEmail["countryCode"];
                                $this->reportAdd($emailReceipt, $rowEmail['gibbonPersonID'], 'Individuals', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
                            }
                        }
                    }
                }
            }
        }

        //Write report entries
        foreach ($this->report as $reportEntry) {
            try {
                $confirmed = $reportEntry[5] != '' ? 'N' : null;

                $data = ["gibbonMessengerID"=>$AI, "gibbonPersonID"=>$reportEntry[0], "targetType"=>$reportEntry[1], "targetID"=>$reportEntry[2], "contactType"=>$reportEntry[3], "contactDetail"=>$reportEntry[4], "key"=>$reportEntry[5], "confirmed" => $confirmed, "gibbonPersonIDListStudent" => $reportEntry[6], 'nameListStudent' => json_encode($reportEntry[7])];
                $sql="INSERT INTO gibbonMessengerReceipt SET gibbonMessengerID=:gibbonMessengerID, gibbonPersonID=:gibbonPersonID, targetType=:targetType, targetID=:targetID, contactType=:contactType, contactDetail=:contactDetail, `key`=:key, confirmed=:confirmed, confirmedTimestamp=NULL, gibbonPersonIDListStudent=:gibbonPersonIDListStudent, nameListStudent=:nameListStudent" ;
                $result=$connection2->prepare($sql);
                $result->execute($data);
            }
            catch(\PDOException $e) {
                $partialFail = true;
            }
        }

        return count($this->report);
    }

    /**
     * Helps builds report array for setting gibbonMessengerReceipt
     *
     * @param [type] $emailReceipt
     * @param [type] $gibbonPersonID
     * @param [type] $targetType
     * @param [type] $targetID
     * @param [type] $contactType
     * @param [type] $contactDetail
     * @param [type] $gibbonPersonIDListStudent
     * @param [type] $nameStudent
     * @return array
     */
    private function reportAdd($emailReceipt, $gibbonPersonID, $targetType, $targetID, $contactType, $contactDetail, $gibbonPersonIDListStudent = null, $nameStudent = null)
    {
        if ($contactDetail != '' AND is_null($contactDetail) == false) {
            $count = 0;
            $unique = true;
            $uniqueCount = 0;

            // Use password policy to generate random string
            $randStrGenerator = new PasswordPolicy(true, true, false, 40);

            foreach ($this->report as $reportEntry) {
                if ($reportEntry[4] == $contactDetail && $unique) {
                    $unique = false;
                    $uniqueCount = $count;
                }
                $count ++;
            }

            if ($unique) { //Entry is unique, so create
                $count = count($this->report);
                $this->report[$count][0] = $gibbonPersonID;
                $this->report[$count][1] = $targetType;
                $this->report[$count][2] = $targetID;
                $this->report[$count][3] = $contactType;
                $this->report[$count][4] = $contactDetail;
                if ($contactType == 'Email' and $emailReceipt == 'Y') {
                    $this->report[$count][5] = $randStrGenerator->generate();
                }
                else {
                    $this->report[$count][5] = null;
                }
                $this->report[$count][6] = $gibbonPersonIDListStudent;
                $this->report[$count][7] = [$nameStudent];
            }
            else { //Entry is not unique, so apend student details
                $this->report[$uniqueCount][6] = (empty($this->report[$uniqueCount][6])) ? $gibbonPersonIDListStudent : (!empty($gibbonPersonIDListStudent) ? $this->report[$uniqueCount][6].','.$gibbonPersonIDListStudent : $this->report[$uniqueCount][6]);

                if (empty($this->report[$uniqueCount][7])) {
                    $this->report[$uniqueCount][7] = [$nameStudent];
                } else {
                    $this->report[$uniqueCount][7][] = $nameStudent;
                }
            }
        }
    }
}
