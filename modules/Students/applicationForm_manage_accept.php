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

use Gibbon\Forms\Form;
use Gibbon\Comms\NotificationEvent;
use Gibbon\Data\UsernameGenerator;

@session_start();

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';
require $_SESSION[$guid]['absolutePath'].'/lib/PHPMailer/PHPMailerAutoload.php';


if (isActionAccessible($guid, $connection2, '/modules/Students/applicationForm_manage_accept.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/applicationForm_manage.php&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID']."'>".__($guid, 'Manage Applications')."</a> > </div><div class='trailEnd'>".__($guid, 'Accept Application').'</div>';
    echo '</div>';

    //Check if school year specified
    $gibbonApplicationFormID = $_GET['gibbonApplicationFormID'];
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
    $search = $_GET['search'];
    if ($gibbonApplicationFormID == '' or $gibbonSchoolYearID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonApplicationFormID' => $gibbonApplicationFormID);
            $sql = "SELECT * FROM gibbonApplicationForm WHERE gibbonApplicationFormID=:gibbonApplicationFormID AND (status='Pending' OR status='Waiting List')";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __($guid, 'The selected application does not exist or has already been processed.');
            echo '</div>';
        } else {
            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return'], null, null);
            }

            // Grab family ID from Sibling Applications that have been accepted
            $data = array( 'gibbonApplicationFormID' => $gibbonApplicationFormID );
            $sql = "SELECT DISTINCT gibbonApplicationFormID, gibbonFamilyID FROM gibbonApplicationForm
                    JOIN gibbonApplicationFormLink ON (gibbonApplicationForm.gibbonApplicationFormID=gibbonApplicationFormLink.gibbonApplicationFormID1 OR gibbonApplicationForm.gibbonApplicationFormID=gibbonApplicationFormLink.gibbonApplicationFormID2)
                    WHERE gibbonApplicationForm.gibbonFamilyID IS NOT NULL
                    AND gibbonApplicationForm.status='Accepted'
                    AND (gibbonApplicationFormID1=:gibbonApplicationFormID OR gibbonApplicationFormID2=:gibbonApplicationFormID)
                    LIMIT 1";

            $resultLinked = $pdo->executeQuery($data, $sql);

            if ($resultLinked && $resultLinked->rowCount() == 1) {
                $linkedApplication = $resultLinked->fetch();
            }

            //Let's go!
            $values = $result->fetch();
            $step = '';
            if (isset($_GET['step'])) {
                $step = $_GET['step'];
            }
            if ($step != 1 and $step != 2) {
                $step = 1;
            }

            //Step 1
            if ($step == 1) {
                echo '<h3>';
                echo __($guid, 'Step')." $step";
                echo '</h3>';

                echo "<div class='linkTop'>";
                if ($search != '') {
                    echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Students/applicationForm_manage.php&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search'>".__($guid, 'Back to Search Results').'</a>';
                }
                echo '</div>'; 

                $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/applicationForm_manage_accept.php&step=2&gibbonApplicationFormID='.$gibbonApplicationFormID.'&gibbonSchoolYearID='.$gibbonSchoolYearID.'&search='.$search);
                
                $form->addHiddenValue('address', $_SESSION[$guid]['address']);
                $form->addHiddenValue('gibbonApplicationFormID', $gibbonApplicationFormID);
                $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);

                $col = $form->addRow()->addColumn()->addClass('stacked');
                
                $applicantName = formatName('', $values['preferredName'], $values['surname'], 'Student');
                $col->addContent(sprintf(__('Are you sure you want to accept the application for %1$s?'), $applicantName))->wrap('<b>', '</b>');

                $informStudent = (getSettingByScope($connection2, 'Application Form', 'notificationStudentDefault') == 'Y');
                $col->addCheckbox('informStudent')
                    ->description(__('Automatically inform <u>student</u> of their Gibbon login details by email?'))
                    ->inline(true)
                    ->checked($informStudent)
                    ->setClass('');

                $informParents = (getSettingByScope($connection2, 'Application Form', 'notificationParentsDefault') == 'Y');
                $col->addCheckbox('informParents')
                    ->description(__('Automatically inform <u>parents</u> of their Gibbon login details by email?'))
                    ->inline(true)
                    ->checked($informParents)
                    ->setClass('');

                $col->addContent(__('The system will perform the following actions:'))->wrap('<i><u>', '</u></i>');
                $list = $col->addContent()->wrap('<ol>', '</ol>');
                
                $list->append('<li>'.__('Create a Gibbon user account for the student.').'</li>');

                if (!empty($values['gibbonRollGroupID'])) {
                    $list->append('<li>'.__('Enrol the student in the selected school year (as the student has been assigned to a roll group).').'</li>');
                }

                if (!empty($values['gibbonFamilyID']) || !empty($linkedApplication['gibbonFamilyID'])) {
                    $list->append('<li>'.__('Link the student to their family (who are already in Gibbon).').'</li>');
                } else {
                    $list->append('<li>'.__('Create a new family.').'</li>')
                         ->append('<li>'.__('Create user accounts for the parents.').'</li>')
                         ->append('<li>'.__('Link student and parents to the family.').'</li>');
                }

                $list->append('<li>'.__('Save the student\'s payment preferences.').'</li>')
                     ->append('<li>'.__('Set the status of the application to "Accepted".').'</li>');

                // Handle optional auto-enrol feature
                if (!empty($values['gibbonRollGroupID'])) {
                    $data = array('gibbonRollGroupID' => $values['gibbonRollGroupID']);
                    $sql = "SELECT COUNT(*) FROM gibbonCourseClassMap WHERE gibbonRollGroupID=:gibbonRollGroupID";
                    $resultClassMap = $pdo->executeQuery($data, $sql);
                    $classMapCount = ($resultClassMap->rowCount() > 0)? $resultClassMap->fetchColumn(0) : 0;

                    // Student has a roll group and mapped classes exist
                    if ($classMapCount > 0) {
                        $autoEnrolStudent = (getSettingByScope($connection2, 'Timetable Admin', 'autoEnrolCourses') == 'Y');

                        $col->addContent(__('The system can optionally perform the following actions:'))->wrap('<i><u>', '</u></i>');
                        $col->addCheckbox('autoEnrolStudent')
                            ->description(__('Automatically enrol student in classes for their Roll Group.'))
                            ->inline(true)
                            ->checked($autoEnrolStudent)
                            ->setClass('')
                            ->wrap('<ol><li>', '</li></ol>');
                    }
                }

                $col->addContent(__('But you may wish to manually do the following:'))->wrap('<i><u>', '</u></i>');
                $list = $col->addContent()->wrap('<ol>', '</ol>');

                if (empty($values['gibbonRollGroupID'])) {
                    $list->append('<li>'.__('Enrol the student in the selected school year (as the student has been assigned to a roll group).').'</li>');
                }

                $list->append('<li>'.__('Create a medical record for the student.').'</li>')
                     ->append('<li>'.__('Create an individual needs record for the student.').'</li>')
                     ->append('<li>'.__('Create a note of the student\'s scholarship information outside of Gibbon.').'</li>')
                     ->append('<li>'.__('Create a timetable for the student.').'</li>');

                $form->addRow()->addSubmit(__('Accept'));

                echo $form->getOutput();

            } elseif ($step == 2) {
                echo '<h3>';
                echo __($guid, 'Step')." $step";
                echo '</h3>';

                echo "<div class='linkTop'>";
                if ($search != '') {
                    echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Students/applicationForm_manage.php&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search'>".__($guid, 'Back to Search Results').'</a>';
                }
                echo '</div>';

                //Set up variables for automatic email to participants, if selected in Step 1.
                $informParents = 'N';
                if (isset($_POST['informParents'])) {
                    if ($_POST['informParents'] == 'on') {
                        $informParents = 'Y';
                        $informParentsArray = array();
                    }
                }
                $informStudent = 'N';
                if (isset($_POST['informStudent'])) {
                    if ($_POST['informStudent'] == 'on') {
                        $informStudent = 'Y';
                        $informStudentArray = array();
                    }
                }

                //CREATE STUDENT
                $failStudent = true;
                $lock = true;
                try {
                    $sql = 'LOCK TABLES gibbonPerson WRITE, gibbonSetting WRITE, gibbonSchoolYear WRITE, gibbonYearGroup WRITE, gibbonRollGroup WRITE, gibbonHouse WRITE, gibbonStudentEnrolment WRITE, gibbonUsernameFormat WRITE, gibbonRole WRITE';
                    $result = $connection2->query($sql);
                } catch (PDOException $e) {
                    $lock = false;
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
                if ($lock == true) {
                    $gotAI = true;
                    try {
                        $sqlAI = "SHOW TABLE STATUS LIKE 'gibbonPerson'";
                        $resultAI = $connection2->query($sqlAI);
                    } catch (PDOException $e) {
                        $gotAI = false;
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }

                    if ($gotAI == true) {
                        $rowAI = $resultAI->fetch();
                        $gibbonPersonID = str_pad($rowAI['Auto_increment'], 10, '0', STR_PAD_LEFT);

                        // Generate a unique username for the new student
                        $generator = new UsernameGenerator($pdo);
                        $generator->addToken('preferredName', $values['preferredName']);
                        $generator->addToken('firstName', $values['firstName']);
                        $generator->addToken('surname', $values['surname']);

                        $username = $generator->generateByRole('003');

                        // Generate a random password
                        $password = randomPassword(8);
                        $salt = getSalt();
                        $passwordStrong = hash('sha256', $salt.$password);

                        $lastSchool = '';
                        if ($values['schoolDate1'] > $values['schoolDate2']) {
                            $lastSchool = $values['schoolName1'];
                        } elseif ($values['schoolDate2'] > $values['schoolDate1']) {
                            $lastSchool = $values['schoolName2'];
                        }

                        $continueLoop = !(!empty($username) && $username != 'usernamefailed' && !empty($password));

                        //Set default email address for student
                        $email = $values['email'];
                        $emailAlternate = '';
                        $studentDefaultEmail = getSettingByScope($connection2, 'Application Form', 'studentDefaultEmail');
                        if ($studentDefaultEmail != '') {
                            $emailAlternate = $email;
                            $email = str_replace('[username]', $username, $studentDefaultEmail);
                        }

                        //Set default website address for student
                        $website = '';
                        $studentDefaultWebsite = getSettingByScope($connection2, 'Application Form', 'studentDefaultWebsite');
                        if ($studentDefaultWebsite != '') {
                            $website = str_replace('[username]', $username, $studentDefaultWebsite);
                        }

                        // Get student's school year at entry info
                        try {
                            $dataSchoolYear = array('gibbonSchoolYearID' => $values['gibbonSchoolYearIDEntry']);
                            $sqlSchoolYear = 'SELECT name FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID';
                            $resultSchoolYear = $connection2->prepare($sqlSchoolYear);
                            $resultSchoolYear->execute($dataSchoolYear);
                        } catch (PDOException $e) {
                        }
                        $schoolYearName = ($resultSchoolYear->rowCount() == 1)? $resultSchoolYear->fetchColumn(0) : '';

                        // Get student's year group info
                        try {
                            $dataYearGroup = array('gibbonYearGroupID' => $values['gibbonYearGroupIDEntry']);
                            $sqlYearGroup = 'SELECT name FROM gibbonYearGroup WHERE gibbonYearGroupID=:gibbonYearGroupID';
                            $resultYearGroup = $connection2->prepare($sqlYearGroup);
                            $resultYearGroup->execute($dataYearGroup);
                        } catch (PDOException $e) {
                        }
                        $yearGroupName = ($resultYearGroup->rowCount() == 1)? $resultYearGroup->fetchColumn(0) : '';

                        // Get student's roll group info (if any)
                        try {
                            $dataRollGroup = array('gibbonRollGroupID' => $values['gibbonRollGroupID']);
                            $sqlRollGroup = 'SELECT name FROM gibbonRollGroup WHERE gibbonRollGroupID=:gibbonRollGroupID';
                            $resultRollGroup = $connection2->prepare($sqlRollGroup);
                            $resultRollGroup->execute($dataRollGroup);
                        } catch (PDOException $e) {
                        }
                        $rollGroupName = ($resultRollGroup->rowCount() == 1)? $resultRollGroup->fetchColumn(0) : '';

                        //Email website and email address to admin for creation
                        if ($studentDefaultEmail != '' or $studentDefaultWebsite != '') {
                            echo '<h4>';
                            echo __($guid, 'Student Email & Website');
                            echo '</h4>';
                            $to = $_SESSION[$guid]['organisationAdministratorEmail'];
                            $subject = sprintf(__($guid, 'Create Student Email/Websites for %1$s at %2$s'), $_SESSION[$guid]['systemName'], $_SESSION[$guid]['organisationNameShort']);
                            $body = sprintf(__($guid, 'Please create the following for new student %1$s.'), formatName('', $values['preferredName'], $values['surname'], 'Student'))."<br/><br/>";
                            if ($studentDefaultEmail != '') {
                                $body .= __($guid, 'Email').': '.$email."<br/>";
                            }
                            if ($studentDefaultWebsite != '') {
                                $body .= __($guid, 'Website').': '.$website."<br/>";
                            }
                            if ($values['gibbonSchoolYearIDEntry'] != '' && !empty($schoolYearName)) {
                                $body .= __($guid, 'School Year').': '.$schoolYearName."<br/>";
                            }
                            if ($values['gibbonYearGroupIDEntry'] != '' && !empty($yearGroupName)) {
                                $body .= __($guid, 'Year Group').': '.$yearGroupName."<br/>";
                            }
                            if ($values['gibbonRollGroupID'] != '' && !empty($rollGroupName)) {
                                $body .= __($guid, 'Roll Group').': '.$rollGroupName."<br/>";
                            }
                            if ($values['dateStart'] != '') {
                                $body .= __($guid, 'Start Date').': '.dateConvertBack($guid, $values['dateStart'])."<br/>";
                            }

                            $body .= "<p style='font-style: italic;'>".sprintf(__($guid, 'Email sent via %1$s at %2$s.'), $_SESSION[$guid]['systemName'], $_SESSION[$guid]['organisationName']).'</p>';
                            $bodyPlain = emailBodyConvert($body);

                            $mail = getGibbonMailer($guid);
                            $mail->IsSMTP();
                            $mail->SetFrom($_SESSION[$guid]['organisationAdministratorEmail'], $_SESSION[$guid]['organisationAdministratorName']);
                            $mail->AddAddress($to);
                            $mail->CharSet = 'UTF-8';
                            $mail->Encoding = 'base64';
                            $mail->IsHTML(true);
                            $mail->Subject = $subject;
                            $mail->Body = $body;
                            $mail->AltBody = $bodyPlain;

                            if ($mail->Send()) {
                                echo "<div class='success'>";
                                echo sprintf(__($guid, 'A request to create a student email address and/or website address was successfully sent to %1$s.'), $_SESSION[$guid]['organisationAdministratorName']);
                                echo '</div>';
                            } else {
                                echo "<div class='error'>";
                                echo sprintf(__($guid, 'A request to create a student email address and/or website address failed. Please contact %1$s to request these manually.'), $_SESSION[$guid]['organisationAdministratorName']);
                                echo '</div>';
                            }
                        }

                        //ATTEMPT AUTOMATIC HOUSE ASSIGNMENT
                        $gibbonHouseID = null;
                        $house = '';
                        if (getSettingByScope($connection2, 'Application Form', 'autoHouseAssign') == 'Y') {
                            $houseFail = false;
                            if ($values['gibbonYearGroupIDEntry'] == '' or $values['gibbonSchoolYearIDEntry'] == '' and $values['gender'] == '') { //No year group or school year set, so return error
                                $houseFail = true;
                            } else {
                                //Check boys and girls in each house in year group
                                try {
                                    $dataHouse = array('gibbonYearGroupID' => $values['gibbonYearGroupIDEntry'], 'gibbonSchoolYearID' => $values['gibbonSchoolYearIDEntry'], 'gender' => $values['gender']);
                                    $sqlHouse = "SELECT gibbonHouse.name AS house, gibbonHouse.gibbonHouseID, count(DISTINCT gibbonPerson.gibbonPersonID) AS count
                                        FROM gibbonHouse
                                            LEFT JOIN gibbonPerson ON (gibbonPerson.gibbonHouseID=gibbonHouse.gibbonHouseID AND gender=:gender AND status='Full')
                                            LEFT JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID
                                                AND gibbonSchoolYearID=:gibbonSchoolYearID
                                                AND gibbonYearGroupID=:gibbonYearGroupID)
                                        WHERE gibbonHouse.gibbonHouseID IS NOT NULL
                                        GROUP BY house, gibbonHouse.gibbonHouseID
                                        ORDER BY count, RAND(), gibbonHouse.gibbonHouseID";
                                    $resultHouse = $connection2->prepare($sqlHouse);
                                    $resultHouse->execute($dataHouse);
                                } catch (PDOException $e) {
                                    $houseFail = true;
                                }
                                if ($resultHouse->rowCount() > 0) {
                                    $rowHouse = $resultHouse->fetch();
                                    $gibbonHouseID = $rowHouse['gibbonHouseID'];
                                    $house = $rowHouse['house'];
                                } else {
                                    $houseFail = true;
                                }
                            }

                            if ($houseFail == true) {
                                echo "<div class='warning'>";
                                echo __($guid, 'The student could not automatically be added to a house, you may wish to manually add them to a house.');
                                echo '</div>';
                            } else {
                                echo "<div class='success'>";
                                echo sprintf(__($guid, 'The student has automatically been assigned to %1$s house.'), $house);
                                echo '</div>';
                            }
                        }

                        if ($continueLoop == false) {
                            $insertOK = true;
                            try {
                                $data = array('username' => $username, 'passwordStrong' => $passwordStrong, 'passwordStrongSalt' => $salt, 'surname' => $values['surname'], 'firstName' => $values['firstName'], 'preferredName' => $values['preferredName'], 'officialName' => $values['officialName'], 'nameInCharacters' => $values['nameInCharacters'], 'gender' => $values['gender'], 'dob' => $values['dob'], 'languageFirst' => $values['languageFirst'], 'languageSecond' => $values['languageSecond'], 'languageThird' => $values['languageThird'], 'countryOfBirth' => $values['countryOfBirth'], 'citizenship1' => $values['citizenship1'], 'citizenship1Passport' => $values['citizenship1Passport'], 'nationalIDCardNumber' => $values['nationalIDCardNumber'], 'residencyStatus' => $values['residencyStatus'], 'visaExpiryDate' => $values['visaExpiryDate'], 'email' => $email, 'emailAlternate' => $emailAlternate, 'website' => $website, 'phone1Type' => $values['phone1Type'], 'phone1CountryCode' => $values['phone1CountryCode'], 'phone1' => $values['phone1'], 'phone2Type' => $values['phone2Type'], 'phone2CountryCode' => $values['phone2CountryCode'], 'phone2' => $values['phone2'], 'lastSchool' => $lastSchool, 'dateStart' => $values['dateStart'], 'privacy' => $values['privacy'], 'dayType' => $values['dayType'], 'gibbonHouseID' => $gibbonHouseID, 'fields' => $values['fields']);
                                $sql = "INSERT INTO gibbonPerson SET username=:username, password='', passwordStrong=:passwordStrong, passwordStrongSalt=:passwordStrongSalt, gibbonRoleIDPrimary='003', gibbonRoleIDAll='003', status='Full', surname=:surname, firstName=:firstName, preferredName=:preferredName, officialName=:officialName, nameInCharacters=:nameInCharacters, gender=:gender, dob=:dob, languageFirst=:languageFirst, languageSecond=:languageSecond, languageThird=:languageThird, countryOfBirth=:countryOfBirth, citizenship1=:citizenship1, citizenship1Passport=:citizenship1Passport, nationalIDCardNumber=:nationalIDCardNumber, residencyStatus=:residencyStatus, visaExpiryDate=:visaExpiryDate, email=:email, emailAlternate=:emailAlternate, website=:website, phone1Type=:phone1Type, phone1CountryCode=:phone1CountryCode, phone1=:phone1, phone2Type=:phone2Type, phone2CountryCode=:phone2CountryCode, phone2=:phone2, lastSchool=:lastSchool, dateStart=:dateStart, privacy=:privacy, dayType=:dayType, gibbonHouseID=:gibbonHouseID, fields=:fields";
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                $insertOK = false;
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }
                            if ($insertOK == true) {
                                $failStudent = false;

                                //Populate informStudent array
                                if ($informStudent == 'Y') {
                                    $informStudentArray[0]['email'] = $values['email'];
                                    $informStudentArray[0]['surname'] = $values['surname'];
                                    $informStudentArray[0]['preferredName'] = $values['preferredName'];
                                    $informStudentArray[0]['username'] = $username;
                                    $informStudentArray[0]['password'] = $password;
                                }
                            }
                        }
                    }
                }
                try {
                    $sql = 'UNLOCK TABLES';
                    $result = $connection2->query($sql);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                if ($failStudent == true) {
                    echo "<div class='error'>";
                    echo __($guid, 'Student could not be created!');
                    echo '</div>';
                } else {
                    echo '<h4>';
                    echo __($guid, 'Student Details');
                    echo '</h4>';
                    echo '<ul>';
                    echo "<li><b>gibbonPersonID</b>: $gibbonPersonID</li>";
                    echo '<li><b>'.__($guid, 'Name').'</b>: '.formatName('', $values['preferredName'], $values['surname'], 'Student').'</li>';
                    echo '<li><b>'.__($guid, 'Email').'</b>: '.$email.'</li>';
                    echo '<li><b>'.__($guid, 'Email Alternate').'</b>: '.$emailAlternate.'</li>';
                    echo '<li><b>'.__($guid, 'Username')."</b>: $username</li>";
                    echo '<li><b>'.__($guid, 'Password')."</b>: $password</li>";
                    echo '</ul>';

                    //Move documents to student notes
                    try {
                        $dataDoc = array('gibbonApplicationFormID' => $gibbonApplicationFormID);
                        $sqlDoc = 'SELECT * FROM gibbonApplicationFormFile WHERE gibbonApplicationFormID=:gibbonApplicationFormID';
                        $resultDoc = $connection2->prepare($sqlDoc);
                        $resultDoc->execute($dataDoc);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }
                    if ($resultDoc->rowCount() > 0) {
                        $note = '<p>';
                        while ($rowDoc = $resultDoc->fetch()) {
                            $note .= "<a href='".$_SESSION[$guid]['absoluteURL'].'/'.$rowDoc['path']."'>".$rowDoc['name'].'</a><br/>';
                        }
                        $note .= '</p>';
                        try {
                            $data = array('gibbonPersonID' => $gibbonPersonID, 'title' => __($guid, 'Application Documents'), 'note' => $note, 'gibbonPersonIDCreator' => $_SESSION[$guid]['gibbonPersonID'], 'timestamp' => date('Y-m-d H:i:s'));
                            $sql = 'INSERT INTO gibbonStudentNote SET gibbonPersonID=:gibbonPersonID, gibbonStudentNoteCategoryID=NULL, title=:title, note=:note, gibbonPersonIDCreator=:gibbonPersonIDCreator, timestamp=:timestamp';
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }
                    }

                    //Enrol student
                    $enrolmentOK = true;
                    if ($values['gibbonRollGroupID'] != '') {
                        if ($gibbonPersonID != '' and $values['gibbonSchoolYearIDEntry'] != '' and $values['gibbonYearGroupIDEntry'] != '') {
                            try {
                                $data = array('gibbonPersonID' => $gibbonPersonID, 'gibbonSchoolYearID' => $values['gibbonSchoolYearIDEntry'], 'gibbonYearGroupID' => $values['gibbonYearGroupIDEntry'], 'gibbonRollGroupID' => $values['gibbonRollGroupID']);
                                $sql = 'INSERT INTO gibbonStudentEnrolment SET gibbonPersonID=:gibbonPersonID, gibbonSchoolYearID=:gibbonSchoolYearID, gibbonYearGroupID=:gibbonYearGroupID, gibbonRollGroupID=:gibbonRollGroupID';
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                $enrolmentOK = false;
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }
                        } else {
                            $enrolmentOK = false;
                        }

                        //Report back
                        if ($enrolmentOK == false) {
                            echo "<div class='warning'>";
                            echo __($guid, 'Student could not be enroled, so this will have to be done manually at a later date.');
                            echo '</div>';
                        } else {
                            echo '<h4>';
                            echo 'Student Enrolment';
                            echo '</h4>';
                            echo '<ul>';
                            echo '<li>'.__($guid, 'The student has successfully been enroled in the specified school year, year group and roll group.').'</li>';

                            // Handle automatic course enrolment if enabled
                            $autoEnrolStudent = (isset($_POST['autoEnrolStudent']))? $_POST['autoEnrolStudent'] : 'N';
                            if ($autoEnrolStudent == 'Y') {
                                $data = array(
                                    'gibbonRollGroupID' => $values['gibbonRollGroupID'],
                                    'gibbonPersonID' => $gibbonPersonID,
                                );

                                $sql = "INSERT INTO gibbonCourseClassPerson (`gibbonCourseClassID`, `gibbonPersonID`, `role`, `reportable`)
                                        SELECT gibbonCourseClassMap.gibbonCourseClassID, :gibbonPersonID, 'Student', 'Y'
                                        FROM gibbonCourseClassMap
                                        WHERE gibbonCourseClassMap.gibbonRollGroupID=:gibbonRollGroupID";
                                $pdo->executeQuery($data, $sql);

                                if (!$pdo->getQuerySuccess()) {
                                    echo '<li class="warning">'.__($guid, 'Student could not be automatically enroled in courses, so this will have to be done manually at a later date.').'</li>';
                                } else {
                                    echo '<li>'.__($guid, 'The student has automatically been enroled in courses for their Roll Group.').'</li>';
                                }
                            }

                            echo '</ul>';
                        }
                    }

                    //SAVE PAYMENT PREFERENCES
                    $failPayment = true;
                    $invoiceTo = $values['payment'];
                    if ($invoiceTo == 'Company') {
                        $companyName = $values['companyName'];
                        $companyContact = $values['companyContact'];
                        $companyAddress = $values['companyAddress'];
                        $companyEmail = $values['companyEmail'];
                        $companyPhone = $values['companyPhone'];
                        $companyAll = $values['companyAll'];
                        $gibbonFinanceFeeCategoryIDList = null;
                        if ($companyAll == 'N') {
                            $gibbonFinanceFeeCategoryIDList = '';
                            $gibbonFinanceFeeCategoryIDArray = explode(',', $values['gibbonFinanceFeeCategoryIDList']);
                            if (count($gibbonFinanceFeeCategoryIDArray) > 0) {
                                foreach ($gibbonFinanceFeeCategoryIDArray as $gibbonFinanceFeeCategoryID) {
                                    $gibbonFinanceFeeCategoryIDList .= $gibbonFinanceFeeCategoryID.',';
                                }
                                $gibbonFinanceFeeCategoryIDList = substr($gibbonFinanceFeeCategoryIDList, 0, -1);
                            }
                        }
                    } else {
                        $companyName = null;
                        $companyContact = null;
                        $companyAddress = null;
                        $companyEmail = null;
                        $companyPhone = null;
                        $companyAll = null;
                        $gibbonFinanceFeeCategoryIDList = null;
                    }
                    $paymentOK = true;
                    try {
                        $data = array('gibbonPersonID' => $gibbonPersonID, 'invoiceTo' => $invoiceTo, 'companyName' => $companyName, 'companyContact' => $companyContact, 'companyAddress' => $companyAddress, 'companyEmail' => $companyEmail, 'companyPhone' => $companyPhone, 'companyAll' => $companyAll, 'gibbonFinanceFeeCategoryIDList' => $gibbonFinanceFeeCategoryIDList);
                        $sql = 'INSERT INTO gibbonFinanceInvoicee SET gibbonPersonID=:gibbonPersonID, invoiceTo=:invoiceTo, companyName=:companyName, companyContact=:companyContact, companyAddress=:companyAddress, companyEmail=:companyEmail, companyPhone=:companyPhone, companyAll=:companyAll, gibbonFinanceFeeCategoryIDList=:gibbonFinanceFeeCategoryIDList';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $paymentOK = false;
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }

                    if ($paymentOK == false) {
                        echo "<div class='warning'>";
                        echo __($guid, 'Student payment details could not be saved, but we will continue, as this is a minor issue.');
                        echo '</div>';
                    }

                    $failFamily = true;
                    if (!empty($values['gibbonFamilyID']) || !empty($linkedApplication['gibbonFamilyID'])) {

                        if (empty($values['gibbonFamilyID'])) {
                            // Associate the application with the gibbonFamilyID from linked application
                            $values['gibbonFamilyID'] = $linkedApplication['gibbonFamilyID'];
                        }

                        //CONNECT STUDENT TO FAMILY
                        try {
                            $dataFamily = array('gibbonFamilyID' => $values['gibbonFamilyID']);
                            $sqlFamily = 'SELECT * FROM gibbonFamily WHERE gibbonFamilyID=:gibbonFamilyID';
                            $resultFamily = $connection2->prepare($sqlFamily);
                            $resultFamily->execute($dataFamily);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }
                        if ($resultFamily->rowCount() == 1) {
                            $rowFamily = $resultFamily->fetch();
                            $familyName = $rowFamily['name'];
                            if ($familyName != '') {
                                $insertFail = false;
                                try {
                                    $data = array('gibbonPersonID' => $gibbonPersonID, 'gibbonFamilyID' => $values['gibbonFamilyID']);
                                    $sql = 'INSERT INTO gibbonFamilyChild SET gibbonPersonID=:gibbonPersonID, gibbonFamilyID=:gibbonFamilyID';
                                    $result = $connection2->prepare($sql);
                                    $result->execute($data);
                                } catch (PDOException $e) {
                                    $insertFail == true;
                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                }
                                if ($insertFail == false) {
                                    $failFamily = false;
                                }
                            }
                        }

                        // Linked application only: try to find existing parents in this family
                        if (!empty($linkedApplication['gibbonApplicationFormID'])) {

                            for ($i = 1; $i <= 2; $i++) {
                                // Attempt to find parents using surname, preferredName within the existing family adults
                                if (empty($values["parent{$i}gibbonPersonID"])) {
                                    try {
                                        $dataParent = array('gibbonFamilyID' => $values['gibbonFamilyID'], 'parentSurname' => $values["parent{$i}surname"], 'parentPreferredName' => $values["parent{$i}preferredName"]);
                                        $sqlParent = 'SELECT gibbonPerson.gibbonPersonID FROM gibbonFamilyAdult JOIN gibbonPerson ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID AND surname=:parentSurname AND preferredName=:parentPreferredName';
                                        $resultParent = $pdo->executeQuery($dataParent, $sqlParent);
                                    } catch (PDOException $e) {
                                        echo "<div class='error'>".$e->getMessage().'</div>';
                                    }

                                    if (isset($resultParent) && $resultParent->rowCount() == 1) {
                                        // Record the found ID -- otherwise the parent creation code further down will kick in
                                        $values["parent{$i}gibbonPersonID"] = $resultParent->fetchColumn(0);

                                        //Set parent relationship
                                        try {
                                            $dataParent = array('gibbonFamilyID' => $values['gibbonFamilyID'], 'gibbonPersonID1' => $values["parent{$i}gibbonPersonID"], 'gibbonPersonID2' => $gibbonPersonID, 'relationship' => $values["parent{$i}relationship"]);
                                            $sqlParent = 'INSERT INTO gibbonFamilyRelationship SET gibbonFamilyID=:gibbonFamilyID, gibbonPersonID1=:gibbonPersonID1, gibbonPersonID2=:gibbonPersonID2, relationship=:relationship';
                                            $resultParentRelationship = $pdo->executeQuery($dataParent, $sqlParent);
                                        } catch (PDOException $e) {
                                            echo "<div class='error'>".$e->getMessage().'</div>';
                                        }
                                    }
                                }
                            }
                        }

                        try {
                            $dataParents = array('gibbonFamilyID' => $values['gibbonFamilyID']);
                            $sqlParents = 'SELECT gibbonFamilyAdult.*, gibbonPerson.gibbonRoleIDAll FROM gibbonFamilyAdult JOIN gibbonPerson ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID';
                            $resultParents = $connection2->prepare($sqlParents);
                            $resultParents->execute($dataParents);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }
                        while ($rowParents = $resultParents->fetch()) {
                            //Update parent roles
                            if (strpos($rowParents['gibbonRoleIDAll'], '004') === false) {
                                try {
                                    $dataRoleUpdate = array('gibbonPersonID' => $rowParents['gibbonPersonID']);
                                    $sqlRoleUpdate = "UPDATE gibbonPerson SET gibbonRoleIDAll=concat(gibbonRoleIDAll, ',004') WHERE gibbonPersonID=:gibbonPersonID";
                                    $resultRoleUpdate = $connection2->prepare($sqlRoleUpdate);
                                    $resultRoleUpdate->execute($dataRoleUpdate);
                                } catch (PDOException $e) {
                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                }
                            }

                            //Add relationship record for each parent
                            try {
                                $dataRelationship = array('gibbonApplicationFormID' => $gibbonApplicationFormID, 'gibbonPersonID' => $rowParents['gibbonPersonID']);
                                $sqlRelationship = 'SELECT * FROM gibbonApplicationFormRelationship WHERE gibbonApplicationFormID=:gibbonApplicationFormID AND gibbonPersonID=:gibbonPersonID';
                                $resultRelationship = $connection2->prepare($sqlRelationship);
                                $resultRelationship->execute($dataRelationship);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }
                            if ($resultRelationship->rowCount() == 1) {
                                $rowRelationship = $resultRelationship->fetch();
                                $relationship = $rowRelationship['relationship'];
                                try {
                                    $data = array('gibbonFamilyID' => $values['gibbonFamilyID'], 'gibbonPersonID1' => $rowParents['gibbonPersonID'], 'gibbonPersonID2' => $gibbonPersonID);
                                    $sql = 'SELECT * FROM gibbonFamilyRelationship WHERE gibbonFamilyID=:gibbonFamilyID AND gibbonPersonID1=:gibbonPersonID1 AND gibbonPersonID2=:gibbonPersonID2';
                                    $result = $connection2->prepare($sql);
                                    $result->execute($data);
                                } catch (PDOException $e) {
                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                }
                                if ($result->rowCount() == 0) {
                                    try {
                                        $data = array('gibbonFamilyID' => $values['gibbonFamilyID'], 'gibbonPersonID1' => $rowParents['gibbonPersonID'], 'gibbonPersonID2' => $gibbonPersonID, 'relationship' => $relationship);
                                        $sql = 'INSERT INTO gibbonFamilyRelationship SET gibbonFamilyID=:gibbonFamilyID, gibbonPersonID1=:gibbonPersonID1, gibbonPersonID2=:gibbonPersonID2, relationship=:relationship';
                                        $result = $connection2->prepare($sql);
                                        $result->execute($data);
                                    } catch (PDOException $e) {
                                        echo "<div class='error'>".$e->getMessage().'</div>';
                                    }
                                } elseif ($result->rowCount() == 1) {
                                    $values = $result->fetch();

                                    if ($values['relationship'] != $relationship) {
                                        try {
                                            $data = array('relationship' => $relationship, 'gibbonFamilyRelationshipID' => $values['gibbonFamilyRelationshipID']);
                                            $sql = 'UPDATE gibbonFamilyRelationship SET relationship=:relationship WHERE gibbonFamilyRelationshipID=:gibbonFamilyRelationshipID';
                                            $result = $connection2->prepare($sql);
                                            $result->execute($data);
                                        } catch (PDOException $e) {
                                            echo "<div class='error'>".$e->getMessage().'</div>';
                                        }
                                    }
                                } else {
                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                }
                            }
                        }

                        if ($failFamily == true) {
                            echo "<div class='warning'>";
                            echo __($guid, 'Student could not be linked to family!');
                            echo '</div>';
                        } else {
                            echo '<h4>';
                            echo __($guid, 'Family');
                            echo '</h4>';
                            echo '<ul>';
                            echo '<li><b>gibbonFamilyID</b>: '.$values['gibbonFamilyID'].'</li>';
                            echo '<li><b>'.__($guid, 'Family Name')."</b>: $familyName </li>";
                            echo '<li><b>'.__($guid, 'Roles').'</b>: '.__($guid, 'System has tried to assign parents "Parent" role access if they did not already have it.').'</li>';
                            echo '</ul>';
                        }
                    } else {
                        //CREATE A NEW FAMILY
                        $failFamily = true;
                        $lock = true;
                        try {
                            $sql = 'LOCK TABLES gibbonFamily WRITE';
                            $result = $connection2->query($sql);
                        } catch (PDOException $e) {
                            $lock = false;
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }
                        if ($lock == true) {
                            $gotAI = true;
                            try {
                                $sqlAI = "SHOW TABLE STATUS LIKE 'gibbonFamily'";
                                $resultAI = $connection2->query($sqlAI);
                            } catch (PDOException $e) {
                                $gotAI = false;
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }
                            if ($gotAI == true) {
                                $rowAI = $resultAI->fetch();
                                $gibbonFamilyID = str_pad($rowAI['Auto_increment'], 7, '0', STR_PAD_LEFT);

                                $familyName = $values['parent1preferredName'].' '.$values['parent1surname'];
                                if ($values['parent2preferredName'] != '' and $values['parent2surname'] != '') {
                                    $familyName .= ' & '.$values['parent2preferredName'].' '.$values['parent2surname'];
                                }
                                $nameAddress = '';
                                //Parents share same surname and parent 2 has enough information to be added
                                if ($values['parent1surname'] == $values['parent2surname'] and $values['parent2preferredName'] != '' and $values['parent2title'] != '') {
                                    $nameAddress = $values['parent1title'].' & '.$values['parent2title'].' '.$values['parent1surname'];
                                }
                                //Parents have different names, and parent2 is not blank and has enough information to be added
                                elseif ($values['parent1surname'] != $values['parent2surname'] and $values['parent2surname'] != '' and $values['parent2preferredName'] != '' and $values['parent2title'] != '') {
                                    $nameAddress = $values['parent1title'].' '.$values['parent1surname'].' & '.$values['parent2title'].' '.$values['parent2surname'];
                                }
                                //Just use parent1's name
                                else {
                                    $nameAddress = $values['parent1title'].' '.$values['parent1surname'];
                                }
                                $languageHomePrimary = $values['languageHomePrimary'];
                                $languageHomeSecondary = $values['languageHomeSecondary'];

                                $insertOK = true;
                                try {
                                    $data = array('familyName' => $familyName, 'nameAddress' => $nameAddress, 'languageHomePrimary' => $languageHomePrimary, 'languageHomeSecondary' => $languageHomeSecondary, 'homeAddress' => $values['homeAddress'], 'homeAddressDistrict' => $values['homeAddressDistrict'], 'homeAddressCountry' => $values['homeAddressCountry']);
                                    $sql = 'INSERT INTO gibbonFamily SET name=:familyName, nameAddress=:nameAddress, languageHomePrimary=:languageHomePrimary, languageHomeSecondary=:languageHomeSecondary, homeAddress=:homeAddress, homeAddressDistrict=:homeAddressDistrict, homeAddressCountry=:homeAddressCountry';
                                    $result = $connection2->prepare($sql);
                                    $result->execute($data);
                                } catch (PDOException $e) {
                                    $insertOK = false;
                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                }

                                if ($insertOK == true) {
                                    $failFamily = false;
                                }
                            }
                        }
                        try {
                            $sql = 'UNLOCK TABLES';
                            $result = $connection2->query($sql);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }

                        if ($failFamily == true) {
                            echo "<div class='error'>";
                            echo __($guid, 'Family could not be created!');
                            echo '</div>';
                        } else {
                            echo '<h4>';
                            echo __($guid, 'Family Details');
                            echo '</h4>';
                            echo '<ul>';
                            echo "<li><b>gibbonFamilyID</b>: $gibbonFamilyID</li>";
                            echo '<li><b>'.__($guid, 'Family Name')."</b>: $familyName</li>";
                            echo '<li><b>'.__($guid, 'Address Name')."</b>: $nameAddress</li>";
                            echo '</ul>';

                            //LINK STUDENT INTO FAMILY
                            $failFamily = true;
                            if ($gibbonFamilyID != '') {
                                try {
                                    $dataFamily = array('gibbonFamilyID' => $gibbonFamilyID);
                                    $sqlFamily = 'SELECT * FROM gibbonFamily WHERE gibbonFamilyID=:gibbonFamilyID';
                                    $resultFamily = $connection2->prepare($sqlFamily);
                                    $resultFamily->execute($dataFamily);
                                } catch (PDOException $e) {
                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                }

                                if ($resultFamily->rowCount() == 1) {
                                    $rowFamily = $resultFamily->fetch();
                                    $familyName = $rowFamily['name'];
                                    if ($familyName != '') {
                                        $insertOK = true;
                                        try {
                                            $data = array('gibbonPersonID' => $gibbonPersonID, 'gibbonFamilyID' => $gibbonFamilyID);
                                            $sql = 'INSERT INTO gibbonFamilyChild SET gibbonPersonID=:gibbonPersonID, gibbonFamilyID=:gibbonFamilyID';
                                            $result = $connection2->prepare($sql);
                                            $result->execute($data);
                                        } catch (PDOException $e) {
                                            $insertOK = false;
                                            echo "<div class='error'>".$e->getMessage().'</div>';
                                        }
                                        if ($insertOK == true) {
                                            $failFamily = false;
                                        }
                                    }
                                }

                                if ($failFamily == true) {
                                    echo "<div class='warning'>";
                                    echo __($guid, 'Student could not be linked to family!');
                                    echo '</div>';
                                } else {
                                    // Update the application information with the newly created family ID, for Sibling Applications to use
                                    $data = array('gibbonApplicationFormID' => $gibbonApplicationFormID, 'gibbonFamilyID' => $gibbonFamilyID);
                                    $sql = 'UPDATE gibbonApplicationForm SET gibbonFamilyID=:gibbonFamilyID WHERE gibbonApplicationFormID=:gibbonApplicationFormID';
                                    $resultUpdateFamilyID = $pdo->executeQuery($data, $sql);
                                }
                            }

                            //CREATE PARENT 1
                            $failParent1 = true;
                            if ($values['parent1gibbonPersonID'] != '') {
                                $gibbonPersonIDParent1 = $values['parent1gibbonPersonID'];
                                echo '<h4>';
                                echo 'Parent 1';
                                echo '</h4>';
                                echo '<ul>';
                                echo '<li>'.__($guid, 'Parent 1 already exists in Gibbon, and so does not need a new account.').'</li>';
                                echo "<li><b>gibbonPersonID</b>: $gibbonPersonIDParent1</li>";
                                echo '<li><b>'.__($guid, 'Name').'</b>: '.formatName('', $values['parent1preferredName'], $values['parent1surname'], 'Parent').'</li>';
                                echo '</ul>';

                                //LINK PARENT 1 INTO FAMILY
                                $failFamily = true;
                                if ($gibbonFamilyID != '') {
                                    try {
                                        $dataFamily = array('gibbonFamilyID' => $gibbonFamilyID);
                                        $sqlFamily = 'SELECT * FROM gibbonFamily WHERE gibbonFamilyID=:gibbonFamilyID';
                                        $resultFamily = $connection2->prepare($sqlFamily);
                                        $resultFamily->execute($dataFamily);
                                    } catch (PDOException $e) {
                                        echo "<div class='error'>".$e->getMessage().'</div>';
                                    }
                                    if ($resultFamily->rowCount() == 1) {
                                        $rowFamily = $resultFamily->fetch();
                                        $familyName = $rowFamily['name'];
                                        if ($familyName != '') {
                                            $insertOK = true;
                                            try {
                                                $data = array('gibbonPersonID' => $gibbonPersonIDParent1, 'gibbonFamilyID' => $gibbonFamilyID);
                                                $sql = "INSERT INTO gibbonFamilyAdult SET gibbonPersonID=:gibbonPersonID, gibbonFamilyID=:gibbonFamilyID, contactPriority=1, contactCall='Y', contactSMS='Y', contactEmail='Y', contactMail='Y'";
                                                $result = $connection2->prepare($sql);
                                                $result->execute($data);
                                            } catch (PDOException $e) {
                                                $insertOK = false;
                                                echo "<div class='error'>".$e->getMessage().'</div>';
                                            }
                                            if ($insertOK == true) {
                                                $failFamily = false;
                                            }
                                        }
                                    }

                                    if ($failFamily == true) {
                                        echo "<div class='warning'>";
                                        echo __($guid, 'Parent 1 could not be linked to family!');
                                        echo '</div>';
                                    }
                                }

                                //Set parent relationship
                                try {
                                    $data = array('gibbonFamilyID' => $gibbonFamilyID, 'gibbonPersonID1' => $gibbonPersonIDParent1, 'gibbonPersonID2' => $gibbonPersonID, 'relationship' => $values['parent1relationship']);
                                    $sql = 'INSERT INTO gibbonFamilyRelationship SET gibbonFamilyID=:gibbonFamilyID, gibbonPersonID1=:gibbonPersonID1, gibbonPersonID2=:gibbonPersonID2, relationship=:relationship';
                                    $result = $connection2->prepare($sql);
                                    $result->execute($data);
                                } catch (PDOException $e) {
                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                }
                            } else {
                                $lock = true;
                                try {
                                    $sql = 'LOCK TABLES gibbonPerson WRITE, gibbonUsernameFormat WRITE, gibbonRole WRITE';
                                    $result = $connection2->query($sql);
                                } catch (PDOException $e) {
                                    $lock = false;
                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                }
                                if ($lock == true) {
                                    $gotAI = true;
                                    try {
                                        $sqlAI = "SHOW TABLE STATUS LIKE 'gibbonPerson'";
                                        $resultAI = $connection2->query($sqlAI);
                                    } catch (PDOException $e) {
                                        $gotAI = false;
                                        echo "<div class='error'>".$e->getMessage().'</div>';
                                    }

                                    if ($gotAI == true) {
                                        $rowAI = $resultAI->fetch();
                                        $gibbonPersonIDParent1 = str_pad($rowAI['Auto_increment'], 10, '0', STR_PAD_LEFT);

                                        // Generate a unique username for parent 1
                                        $generator = new UsernameGenerator($pdo);
                                        $generator->addToken('preferredName', $values['parent1preferredName']);
                                        $generator->addToken('firstName', $values['parent1firstName']);
                                        $generator->addToken('surname', $values['parent1surname']);

                                        $username = $generator->generateByRole('004');

                                        // Generate a random password
                                        $password = randomPassword(8);
                                        $salt = getSalt();
                                        $passwordStrong = hash('sha256', $salt.$password);

                                        $continueLoop = !(!empty($username) && $username != 'usernamefailed' && !empty($password));

                                        if ($continueLoop == false) {
                                            $insertOK = true;
                                            try {
                                                $data = array('username' => $username, 'passwordStrong' => $passwordStrong, 'passwordStrongSalt' => $salt, 'title' => $values['parent1title'], 'surname' => $values['parent1surname'], 'firstName' => $values['parent1firstName'], 'preferredName' => $values['parent1preferredName'], 'officialName' => $values['parent1officialName'], 'nameInCharacters' => $values['parent1nameInCharacters'], 'gender' => $values['parent1gender'], 'parent1languageFirst' => $values['parent1languageFirst'], 'parent1languageSecond' => $values['parent1languageSecond'], 'citizenship1' => $values['parent1citizenship1'], 'nationalIDCardNumber' => $values['parent1nationalIDCardNumber'], 'residencyStatus' => $values['parent1residencyStatus'], 'visaExpiryDate' => $values['parent1visaExpiryDate'], 'email' => $values['parent1email'], 'phone1Type' => $values['parent1phone1Type'], 'phone1CountryCode' => $values['parent1phone1CountryCode'], 'phone1' => $values['parent1phone1'], 'phone2Type' => $values['parent1phone2Type'], 'phone2CountryCode' => $values['parent1phone2CountryCode'], 'phone2' => $values['parent1phone2'], 'profession' => $values['parent1profession'], 'employer' => $values['parent1employer'], 'parent1fields' => $values['parent1fields']);
                                                $sql = "INSERT INTO gibbonPerson SET username=:username, password='', passwordStrong=:passwordStrong, passwordStrongSalt=:passwordStrongSalt, gibbonRoleIDPrimary='004', gibbonRoleIDAll='004', status='Full', title=:title, surname=:surname, firstName=:firstName, preferredName=:preferredName, officialName=:officialName, nameInCharacters=:nameInCharacters, gender=:gender, languageFirst=:parent1languageFirst, languageSecond=:parent1languageSecond, citizenship1=:citizenship1, nationalIDCardNumber=:nationalIDCardNumber, residencyStatus=:residencyStatus, visaExpiryDate=:visaExpiryDate, email=:email, phone1Type=:phone1Type, phone1CountryCode=:phone1CountryCode, phone1=:phone1, phone2Type=:phone2Type, phone2CountryCode=:phone2CountryCode, phone2=:phone2, profession=:profession, employer=:employer, fields=:parent1fields";
                                                $result = $connection2->prepare($sql);
                                                $result->execute($data);
                                            } catch (PDOException $e) {
                                                $insertOK = false;
                                                echo "<div class='error'>".$e->getMessage().'</div>';
                                            }
                                            if ($insertOK == true) {
                                                $failParent1 = false;

                                                //Populate parent1 in informParent array
                                                if ($informParents == 'Y') {
                                                    $informParentsArray[0]['email'] = $values['parent1email'];
                                                    $informParentsArray[0]['surname'] = $values['parent1surname'];
                                                    $informParentsArray[0]['preferredName'] = $values['parent1preferredName'];
                                                    $informParentsArray[0]['username'] = $username;
                                                    $informParentsArray[0]['password'] = $password;
                                                }
                                            }
                                        }
                                    }
                                }
                                try {
                                    $sql = 'UNLOCK TABLES';
                                    $result = $connection2->query($sql);
                                } catch (PDOException $e) {
                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                }

                                if ($failParent1 == true) {
                                    echo "<div class='error'>";
                                    echo __($guid, 'Parent 1 could not be created!');
                                    echo '</div>';
                                } else {
                                    echo '<h4>';
                                    echo __($guid, 'Parent 1');
                                    echo '</h4>';
                                    echo '<ul>';
                                    echo "<li><b>gibbonPersonID</b>: $gibbonPersonIDParent1</li>";
                                    echo '<li><b>'.__($guid, 'Name').'</b>: '.formatName('', $values['parent1preferredName'], $values['parent1surname'], 'Parent').'</li>';
                                    echo '<li><b>'.__($guid, 'Email').'</b>: '.$values['parent1email'].'</li>';
                                    echo '<li><b>'.__($guid, 'Username')."</b>: $username</li>";
                                    echo '<li><b>'.__($guid, 'Password')."</b>: $password</li>";
                                    echo '</ul>';

                                    //LINK PARENT 1 INTO FAMILY
                                    $failFamily = true;
                                    if ($gibbonFamilyID != '') {
                                        try {
                                            $dataFamily = array('gibbonFamilyID' => $gibbonFamilyID);
                                            $sqlFamily = 'SELECT * FROM gibbonFamily WHERE gibbonFamilyID=:gibbonFamilyID';
                                            $resultFamily = $connection2->prepare($sqlFamily);
                                            $resultFamily->execute($dataFamily);
                                        } catch (PDOException $e) {
                                            echo "<div class='error'>".$e->getMessage().'</div>';
                                        }
                                        if ($resultFamily->rowCount() == 1) {
                                            $rowFamily = $resultFamily->fetch();
                                            $familyName = $rowFamily['name'];
                                            if ($familyName != '') {
                                                $insertOK = true;
                                                try {
                                                    $data = array('gibbonPersonID' => $gibbonPersonIDParent1, 'gibbonFamilyID' => $gibbonFamilyID, 'contactCall' => 'Y', 'contactSMS' => 'Y', 'contactEmail' => 'Y', 'contactMail' => 'Y');
                                                    $sql = 'INSERT INTO gibbonFamilyAdult SET gibbonPersonID=:gibbonPersonID, gibbonFamilyID=:gibbonFamilyID, contactPriority=1, contactCall=:contactCall, contactSMS=:contactSMS, contactEmail=:contactEmail, contactMail=:contactMail';
                                                    $result = $connection2->prepare($sql);
                                                    $result->execute($data);
                                                } catch (PDOException $e) {
                                                    $insertOK = false;
                                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                                }
                                                if ($insertOK == true) {
                                                    $failFamily = false;
                                                }
                                            }
                                        }

                                        if ($failFamily == true) {
                                            echo "<div class='warning'>";
                                            echo __($guid, 'Parent 1 could not be linked to family!');
                                            echo '</div>';
                                        }

                                        //Set parent relationship
                                        try {
                                            $data = array('gibbonFamilyID' => $gibbonFamilyID, 'gibbonPersonID1' => $gibbonPersonIDParent1, 'gibbonPersonID2' => $gibbonPersonID, 'relationship' => $values['parent1relationship']);
                                            $sql = 'INSERT INTO gibbonFamilyRelationship SET gibbonFamilyID=:gibbonFamilyID, gibbonPersonID1=:gibbonPersonID1, gibbonPersonID2=:gibbonPersonID2, relationship=:relationship';
                                            $result = $connection2->prepare($sql);
                                            $result->execute($data);
                                        } catch (PDOException $e) {
                                            echo "<div class='error'>".$e->getMessage().'</div>';
                                        }
                                    }
                                }
                            }

                            //CREATE PARENT 2
                            if ($values['parent2preferredName'] != '' and $values['parent2surname'] != '') {
                                $failParent2 = true;
                                $lock = true;
                                try {
                                    $sql = 'LOCK TABLES gibbonPerson WRITE, gibbonUsernameFormat WRITE, gibbonRole WRITE';
                                    $result = $connection2->query($sql);
                                } catch (PDOException $e) {
                                    $lock = false;
                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                }
                                if ($lock == true) {
                                    $gotAI = true;
                                    try {
                                        $sqlAI = "SHOW TABLE STATUS LIKE 'gibbonPerson'";
                                        $resultAI = $connection2->query($sqlAI);
                                    } catch (PDOException $e) {
                                        $gotAI = false;
                                        echo "<div class='error'>".$e->getMessage().'</div>';
                                    }

                                    if ($gotAI == true) {
                                        $rowAI = $resultAI->fetch();
                                        $gibbonPersonIDParent2 = str_pad($rowAI['Auto_increment'], 10, '0', STR_PAD_LEFT);

                                        // Generate a unique username for parent 2
                                        $generator = new UsernameGenerator($pdo);
                                        $generator->addToken('preferredName', $values['parent2preferredName']);
                                        $generator->addToken('firstName', $values['parent2firstName']);
                                        $generator->addToken('surname', $values['parent2surname']);

                                        $username = $generator->generateByRole('004');

                                        // Generate a random password
                                        $password = randomPassword(8);
                                        $salt = getSalt();
                                        $passwordStrong = hash('sha256', $salt.$password);

                                        $continueLoop = !(!empty($username) && $username != 'usernamefailed' && !empty($password));

                                        if ($continueLoop == false) {
                                            $insertOK = true;
                                            try {
                                                $data = array('username' => $username, 'passwordStrong' => $passwordStrong, 'passwordStrongSalt' => $salt, 'title' => $values['parent2title'], 'surname' => $values['parent2surname'], 'firstName' => $values['parent2firstName'], 'preferredName' => $values['parent2preferredName'], 'officialName' => $values['parent2officialName'], 'nameInCharacters' => $values['parent2nameInCharacters'], 'gender' => $values['parent2gender'], 'parent2languageFirst' => $values['parent2languageFirst'], 'parent2languageSecond' => $values['parent2languageSecond'], 'citizenship1' => $values['parent2citizenship1'], 'nationalIDCardNumber' => $values['parent2nationalIDCardNumber'], 'residencyStatus' => $values['parent2residencyStatus'], 'visaExpiryDate' => $values['parent2visaExpiryDate'], 'email' => $values['parent2email'], 'phone1Type' => $values['parent2phone1Type'], 'phone1CountryCode' => $values['parent2phone1CountryCode'], 'phone1' => $values['parent2phone1'], 'phone2Type' => $values['parent2phone2Type'], 'phone2CountryCode' => $values['parent2phone2CountryCode'], 'phone2' => $values['parent2phone2'], 'profession' => $values['parent2profession'], 'employer' => $values['parent2employer'], 'parent2fields' => $values['parent2fields']);
                                                $sql = "INSERT INTO gibbonPerson SET username=:username, password='', passwordStrong=:passwordStrong, passwordStrongSalt=:passwordStrongSalt, gibbonRoleIDPrimary='004', gibbonRoleIDAll='004', status='Full', title=:title, surname=:surname, firstName=:firstName, preferredName=:preferredName, officialName=:officialName, nameInCharacters=:nameInCharacters, gender=:gender, languageFirst=:parent2languageFirst, languageSecond=:parent2languageSecond, citizenship1=:citizenship1, nationalIDCardNumber=:nationalIDCardNumber, residencyStatus=:residencyStatus, visaExpiryDate=:visaExpiryDate, email=:email, phone1Type=:phone1Type, phone1CountryCode=:phone1CountryCode, phone1=:phone1, phone2Type=:phone2Type, phone2CountryCode=:phone2CountryCode, phone2=:phone2, profession=:profession, employer=:employer, fields=:parent2fields";
                                                $result = $connection2->prepare($sql);
                                                $result->execute($data);
                                            } catch (PDOException $e) {
                                                $insertOK = false;
                                                echo "<div class='error'>".$e->getMessage().'</div>';
                                            }
                                            if ($insertOK == true) {
                                                $failParent2 = false;

                                                //Populate parent2 in informParents array
                                                if ($informParents == 'Y') {
                                                    $informParentsArray[1]['email'] = $values['parent2email'];
                                                    $informParentsArray[1]['surname'] = $values['parent2surname'];
                                                    $informParentsArray[1]['preferredName'] = $values['parent2preferredName'];
                                                    $informParentsArray[1]['username'] = $username;
                                                    $informParentsArray[1]['password'] = $password;
                                                }
                                            }
                                        }
                                    }
                                }
                                try {
                                    $sql = 'UNLOCK TABLES';
                                    $result = $connection2->query($sql);
                                } catch (PDOException $e) {
                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                }

                                if ($failParent2 == true) {
                                    echo "<div class='error'>";
                                    echo __($guid, 'Parent 2 could not be created!');
                                    echo '</div>';
                                } else {
                                    echo '<h4>';
                                    echo __($guid, 'Parent 2');
                                    echo '</h4>';
                                    echo '<ul>';
                                    echo "<li><b>gibbonPersonID</b>: $gibbonPersonIDParent2</li>";
                                    echo '<li><b>'.__($guid, 'Name').'</b>: '.formatName('', $values['parent2preferredName'], $values['parent2surname'], 'Parent').'</li>';
                                    echo '<li><b>'.__($guid, 'Email').'</b>: '.$values['parent2email'].'</li>';
                                    echo '<li><b>'.__($guid, 'Username')."</b>: $username</li>";
                                    echo '<li><b>'.__($guid, 'Password')."</b>: $password</li>";
                                    echo '</ul>';

                                    //LINK PARENT 2 INTO FAMILY
                                    $failFamily = true;
                                    if ($gibbonFamilyID != '') {
                                        try {
                                            $dataFamily = array('gibbonFamilyID' => $gibbonFamilyID);
                                            $sqlFamily = 'SELECT * FROM gibbonFamily WHERE gibbonFamilyID=:gibbonFamilyID';
                                            $resultFamily = $connection2->prepare($sqlFamily);
                                            $resultFamily->execute($dataFamily);
                                        } catch (PDOException $e) {
                                            echo "<div class='error'>".$e->getMessage().'</div>';
                                        }
                                        if ($resultFamily->rowCount() == 1) {
                                            $rowFamily = $resultFamily->fetch();
                                            $familyName = $rowFamily['name'];
                                            if ($familyName != '') {
                                                $insertOK = true;
                                                try {
                                                    $data = array('gibbonPersonID' => $gibbonPersonIDParent2, 'gibbonFamilyID' => $gibbonFamilyID, 'contactCall' => 'Y', 'contactSMS' => 'Y', 'contactEmail' => 'Y', 'contactMail' => 'Y');
                                                    $sql = 'INSERT INTO gibbonFamilyAdult SET gibbonPersonID=:gibbonPersonID, gibbonFamilyID=:gibbonFamilyID, contactPriority=2, contactCall=:contactCall, contactSMS=:contactSMS, contactEmail=:contactEmail, contactMail=:contactMail';
                                                    $result = $connection2->prepare($sql);
                                                    $result->execute($data);
                                                } catch (PDOException $e) {
                                                    $insertOK = false;
                                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                                }
                                                if ($insertOK == true) {
                                                    $failFamily = false;
                                                }
                                            }
                                        }

                                        if ($failFamily == true) {
                                            echo "<div class='warning'>";
                                            echo __($guid, 'Parent 2 could not be linked to family!');
                                            echo '</div>';
                                        }

                                        //Set parent relationship
                                        try {
                                            $data = array('gibbonFamilyID' => $gibbonFamilyID, 'gibbonPersonID1' => $gibbonPersonIDParent2, 'gibbonPersonID2' => $gibbonPersonID, 'relationship' => $values['parent2relationship']);
                                            $sql = 'INSERT INTO gibbonFamilyRelationship SET gibbonFamilyID=:gibbonFamilyID, gibbonPersonID1=:gibbonPersonID1, gibbonPersonID2=:gibbonPersonID2, relationship=:relationship';
                                            $result = $connection2->prepare($sql);
                                            $result->execute($data);
                                        } catch (PDOException $e) {
                                            echo "<div class='error'>".$e->getMessage().'</div>';
                                        }
                                    }
                                }
                            }
                        }
                    }

                    //SEND STUDENT EMAIL
                    if ($informStudent == 'Y') {
                        echo '<h4>';
                        echo __($guid, 'Student Welcome Email');
                        echo '</h4>';
                        $emailCount = 0 ;
                        $notificationStudentMessage = getSettingByScope($connection2, 'Application Form', 'notificationStudentMessage');
                        foreach ($informStudentArray as $informStudentEntry) {
                            if ($informStudentEntry['email'] != '' and $informStudentEntry['surname'] != '' and $informStudentEntry['preferredName'] != '' and $informStudentEntry['username'] != '' and $informStudentEntry['password']) {
                                $to = $informStudentEntry['email'];
                                $subject = sprintf(__($guid, 'Welcome to %1$s at %2$s'), $_SESSION[$guid]['systemName'], $_SESSION[$guid]['organisationNameShort']);
                                if ($notificationStudentMessage != '') {
                                    $body = sprintf(__($guid, 'Dear %1$s,<br/><br/>Welcome to %2$s, %3$s\'s system for managing school information. You can access the system by going to %4$s and logging in with your new username (%5$s) and password (%6$s).<br/><br/>In order to maintain the security of your data, we highly recommend you change your password to something easy to remember but hard to guess. This can be done by using the Preferences page after logging in (top-right of the screen).<br/><br/>'), formatName('', $informStudentEntry['preferredName'], $informStudentEntry['surname'], 'Student'), $_SESSION[$guid]['systemName'], $_SESSION[$guid]['organisationNameShort'], $_SESSION[$guid]['absoluteURL'], $informStudentEntry['username'], $informStudentEntry['password']).$notificationStudentMessage.sprintf(__($guid, 'Please feel free to reply to this email should you have any questions.<br/><br/>%1$s,<br/><br/>%2$s Admissions Administrator'), $_SESSION[$guid]['organisationAdmissionsName'], $_SESSION[$guid]['systemName']);
                                } else {
                                    $body = 'Dear '.formatName('', $informStudentEntry['preferredName'], $informStudentEntry['surname'], 'Student').",<br/><br/>Welcome to ".$_SESSION[$guid]['systemName'].', '.$_SESSION[$guid]['organisationNameShort']."'s system for managing school information. You can access the system by going to ".$_SESSION[$guid]['absoluteURL'].' and logging in with your new username ('.$informStudentEntry['username'].') and password ('.$informStudentEntry['password'].").<br/><br/>In order to maintain the security of your data, we highly recommend you change your password to something easy to remember but hard to guess. This can be done by using the Preferences page after logging in (top-right of the screen).<br/><br/>Please feel free to reply to this email should you have any questions.<br/><br/>".$_SESSION[$guid]['organisationAdmissionsName'].",<br/><br/>".$_SESSION[$guid]['systemName'].' Admissions Administrator';
                                }
                                $bodyPlain = emailBodyConvert($body);

                                $mail = getGibbonMailer($guid);
                                $mail->IsSMTP();
                                $mail->SetFrom($_SESSION[$guid]['organisationAdmissionsEmail'], $_SESSION[$guid]['organisationAdmissionsName']);
                                $mail->AddAddress($to);
                                $mail->CharSet = 'UTF-8';
                                $mail->Encoding = 'base64';
                                $mail->IsHTML(true);
                                $mail->Subject = $subject;
                                $mail->Body = $body;
                                $mail->AltBody = $bodyPlain;

                                if ($mail->Send()) {
                                    echo "<div class='success'>";
                                    echo __($guid, 'A welcome email was successfully sent to').' '.formatName('', $informStudentEntry['preferredName'], $informStudentEntry['surname'], 'Student').'.';
                                    echo '</div>';
                                } else {
                                    echo "<div class='error'>";
                                    echo __($guid, 'A welcome email could not be sent to').' '.formatName('', $informStudentEntry['preferredName'], $informStudentEntry['surname'], 'Student').'.';
                                    echo '</div>';
                                }
                                $emailCount++ ;
                            }
                        }
                        if ($emailCount == 0) {
                            echo '<div class=\'warning\'>';
                            echo __('There are no student email addresses to send to.');
                            echo '</div>';
                        }
                    }

                    //SEND PARENTS EMAIL
                    if ($informParents == 'Y') {
                        echo '<h4>';
                        echo 'Parent Welcome Email';
                        echo '</h4>';
                        $emailCount = 0 ;
                        $notificationParentsMessage = getSettingByScope($connection2, 'Application Form', 'notificationParentsMessage');
                        foreach ($informParentsArray as $informParentsEntry) {
                            if ($informParentsEntry['email'] != '' and $informParentsEntry['surname'] != '' and $informParentsEntry['preferredName'] != '' and $informParentsEntry['username'] != '' and $informParentsEntry['password']) {
                                $to = $informParentsEntry['email'];
                                $subject = sprintf(__($guid, 'Welcome to %1$s at %2$s'), $_SESSION[$guid]['systemName'], $_SESSION[$guid]['organisationNameShort']);
                                if ($notificationParentsMessage != '') {
                                    $body = sprintf(__($guid, 'Dear %1$s,<br/><br/>Welcome to %2$s, %3$s\'s system for managing school information. You can access the system by going to %4$s and logging in with your new username (%5$s) and password (%6$s). You can learn more about using %7$s on the official support website (https://gibbonedu.org/support/parents).<br/><br/>In order to maintain the security of your data, we highly recommend you change your password to something easy to remember but hard to guess. This can be done by using the Preferences page after logging in (top-right of the screen).<br/><br/>'), formatName('', $informParentsEntry['preferredName'], $informParentsEntry['surname'], 'Student'), $_SESSION[$guid]['systemName'], $_SESSION[$guid]['organisationNameShort'], $_SESSION[$guid]['absoluteURL'], $informParentsEntry['username'], $informParentsEntry['password'], $_SESSION[$guid]['systemName']).$notificationParentsMessage.sprintf(__($guid, 'Please feel free to reply to this email should you have any questions.<br/><br/>%1$s,<br/><br/>%2$s Admissions Administrator'), $_SESSION[$guid]['organisationAdmissionsName'], $_SESSION[$guid]['systemName']);
                                } else {
                                    $body = sprintf(__($guid, 'Dear %1$s,<br/><br/>Welcome to %2$s, %3$s\'s system for managing school information. You can access the system by going to %4$s and logging in with your new username (%5$s) and password (%6$s). You can learn more about using %7$s on the official support website (https://gibbonedu.org/support/parents).<br/><br/>In order to maintain the security of your data, we highly recommend you change your password to something easy to remember but hard to guess. This can be done by using the Preferences page after logging in (top-right of the screen).<br/><br/>'), formatName('', $informParentsEntry['preferredName'], $informParentsEntry['surname'], 'Student'), $_SESSION[$guid]['systemName'], $_SESSION[$guid]['organisationNameShort'], $_SESSION[$guid]['absoluteURL'], $informParentsEntry['username'], $informParentsEntry['password'], $_SESSION[$guid]['systemName']).sprintf(__($guid, 'Please feel free to reply to this email should you have any questions.<br/><br/>%1$s,<br/><br/>%2$s Admissions Administrator'), $_SESSION[$guid]['organisationAdmissionsName'], $_SESSION[$guid]['systemName']);
                                }
                                $bodyPlain = emailBodyConvert($body);

                                $mail = getGibbonMailer($guid);
                                $mail->IsSMTP();
                                $mail->SetFrom($_SESSION[$guid]['organisationAdmissionsEmail'], $_SESSION[$guid]['organisationAdmissionsName']);
                                $mail->AddAddress($to);
                                $mail->CharSet = 'UTF-8';
                                $mail->Encoding = 'base64';
                                $mail->IsHTML(true);
                                $mail->Subject = $subject;
                                $mail->Body = $body;
                                $mail->AltBody = $bodyPlain;

                                if ($mail->Send()) {
                                    echo "<div class='success'>";
                                    echo __($guid, 'A welcome email was successfully sent to').' '.formatName('', $informParentsEntry['preferredName'], $informParentsEntry['surname'], 'Student').'.';
                                    echo '</div>';
                                } else {
                                    echo "<div class='error'>";
                                    echo __($guid, 'A welcome email could not be sent to').' '.formatName('', $informParentsEntry['preferredName'], $informParentsEntry['surname'], 'Student').'.';
                                    echo '</div>';
                                }
                                $emailCount++ ;
                            }
                        }
                        if ($emailCount == 0) {
                            echo '<div class=\'warning\'>';
                            echo __('There are no parent email addresses to send to.');
                            echo '</div>';
                        }
                    }

                    // Raise a new notification event
                    $event = new NotificationEvent('Students', 'Application Form Accepted');

                    $studentName = formatName('', $values['preferredName'], $values['surname'], 'Student');
                    $studentGroup = (!empty($rollGroupName))? $rollGroupName : $yearGroupName;

                    $notificationText = sprintf(__('An application form for %1$s (%2$s) has been accepted for the %3$s school year.'), $studentName, $studentGroup, $schoolYearName );
                    if ($enrolmentOK && !empty($values['gibbonRollGroupID'])) {
                        $notificationText .= ' '.__('The student has successfully been enroled in the specified school year, year group and roll group.');
                    } else {
                        $notificationText .= ' '.__('Student could not be enroled, so this will have to be done manually at a later date.');
                    }

                    $event->addScope('gibbonYearGroupID', $values['gibbonYearGroupIDEntry']);
                    $event->addRecipient($_SESSION[$guid]['organisationAdmissions']);
                    $event->setNotificationText($notificationText);
                    $event->setActionLink("/index.php?q=/modules/Students/applicationForm_manage_edit.php&gibbonApplicationFormID=$gibbonApplicationFormID&gibbonSchoolYearID=".$values['gibbonSchoolYearIDEntry']."&search=");

                    $event->sendNotifications($pdo, $gibbon->session);

                    //SET STATUS TO ACCEPTED
                    $failStatus = false;
                    try {
                        $data = array('gibbonApplicationFormID' => $gibbonApplicationFormID);
                        $sql = "UPDATE gibbonApplicationForm SET status='Accepted' WHERE gibbonApplicationFormID=:gibbonApplicationFormID";
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $failStatus = true;
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }

                    if ($failStatus == true) {
                        echo "<div class='error'>";
                        echo __($guid, 'Student status could not be updated: student is in the system, but acceptance has failed.');
                        echo '</div>';
                    } else {
                        echo '<h4>';
                        echo __($guid, 'Application Status');
                        echo '</h4>';
                        echo '<ul>';
                        echo '<li><b>'.__($guid, 'Status').'</b>: '.__($guid, 'Accepted').'</li>';
                        echo '</ul>';

                        echo "<div class='success' style='margin-bottom: 20px'>";
                        echo str_replace('ICHK', $_SESSION[$guid]['organisationNameShort'], __($guid, 'Applicant has been successfully accepted into ICHK.') );
                        echo ' <i><u>'.__($guid, 'You may wish to now do the following:').'</u></i><br/>';
                        echo '<ol>';
                        echo '<li>'.__($guid, 'Enrol the student in the relevant academic year.').'</li>';
                        echo '<li>'.__($guid, 'Create a medical record for the student.').'</li>';
                        echo '<li>'.__($guid, 'Create an individual needs record for the student.').'</li>';
                        echo '<li>'.__($guid, 'Create a note of the student\'s scholarship information outside of Gibbon.').'</li>';
                        echo '<li>'.__($guid, 'Create a timetable for the student.').'</li>';
                        echo '<li>'.__($guid, 'Inform the student and their parents of their Gibbon login details (if this was not done automatically).').'</li>';
                        echo '</ol>';
                        echo '</div>';
                    }
                }
            }
        }
    }
}
?>
