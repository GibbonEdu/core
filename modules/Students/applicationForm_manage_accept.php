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

            //Let's go!
            $row = $result->fetch();
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
                echo '</div>'; ?>
				<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/applicationForm_manage_accept.php&step=2&gibbonApplicationFormID=$gibbonApplicationFormID&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search" ?>">
					<table class='smallIntBorder fullWidth' cellspacing='0'>
						<tr>
							<td>
								<b><?php echo sprintf(__($guid, 'Are you sure you want to accept the application for %1$s?'), formatName('', $row['preferredName'], $row['surname'], 'Student')) ?></b><br/>
								<br/>
								<?php
                                $checkedStudent = '';
								if (getSettingByScope($connection2, 'Application Form', 'notificationStudentDefault') == 'Y') {
									$checkedStudent = 'checked';
								}
								?>
								<input <?php echo $checkedStudent ?> type='checkbox' name='informStudent'/> <?php echo __($guid, 'Automatically inform <u>student</u> of their Gibbon login details by email?') ?><br/>
								<?php
                                $checkedParents = '';
								if (getSettingByScope($connection2, 'Application Form', 'notificationParentsDefault') == 'Y') {
									$checkedParents = 'checked';
								}
								?>
								<input <?php echo $checkedParents ?> type='checkbox' name='informParents'/> <?php echo __($guid, 'Automatically inform <u>parents</u> of their Gibbon login details by email?') ?><br/>

								<br/>
								<i><u><?php echo __($guid, 'The system will perform the following actions:') ?></u></i><br/>
								<ol>
									<li><?php echo __($guid, 'Create a Gibbon user account for the student.') ?></li>
									<?php
                                    if ($row['gibbonRollGroupID'] != '') {
                                        echo '<li>'.__($guid, 'Enrol the student in the selected school year (as the student has been assigned to a roll group).').'</li>';
                                    }
               		 				?>
									<li><?php echo __($guid, 'Save the student\'s payment preferences.') ?></li>
									<?php
                                    if ($row['gibbonFamilyID'] != '') {
                                        echo '<li>'.__($guid, 'Link the student to their family (who are already in Gibbon).').'</li>';
                                    } else {
                                        echo '<li>'.__($guid, 'Create a new family.').'</li>';
                                        echo '<li>'.__($guid, 'Create user accounts for the parents.').'</li>';
                                        echo '<li>'.__($guid, 'Link student and parents to the family.').'</li>';
                                    }
               		 				?>
									<li><?php echo __($guid, 'Set the status of the application to "Accepted".') ?></li>
								</ol>
								<br/>
								<i><u><?php echo __($guid, 'But you may wish to manually do the following:') ?></u></i><br/>
								<ol>
									<?php
                                    if ($row['gibbonRollGroupID'] == '') {
                                        echo '<li>'.__($guid, 'Enrol the student in the relevant academic year (this will not be done automatically, as the student has not been assigned to a roll group).').'</li>';
                                    }
               		 				?>
									<li><?php echo __($guid, 'Create a medical record for the student.') ?></li>
									<li><?php echo __($guid, 'Create an individual needs record for the student.') ?></li>
									<li><?php echo __($guid, 'Create a note of the student\'s scholarship information outside of Gibbon.') ?></li>
									<li><?php echo __($guid, 'Create a timetable for the student.') ?></li>
								</ol>
							</td>
						</tr>
						<tr>
							<td class='right'>
								<input name="gibbonSchoolYearID" id="gibbonSchoolYearID" value="<?php echo $gibbonSchoolYearID ?>" type="hidden">
								<input name="gibbonApplicationFormID" id="gibbonApplicationFormID" value="<?php echo $gibbonApplicationFormID ?>" type="hidden">
								<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
								<input type="submit" value="Accept">
							</td>
						</tr>
					</table>
				</form>
				<?php

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
                    $sql = 'LOCK TABLES gibbonPerson WRITE, gibbonSetting WRITE, gibbonSchoolYear WRITE, gibbonYearGroup WRITE, gibbonRollGroup WRITE, gibbonHouse WRITE, gibbonStudentEnrolment WRITE';
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

                        //Set username & password
                        $username = '';
                        $usernameFormat = getSettingByScope($connection2, 'Application Form', 'usernameFormat');
                        if ($usernameFormat == '') {
                            $username = substr(str_replace(' ', '', preg_replace('/[^A-Za-z ]/', '', strtolower(substr($row['preferredName'], 0, 1).$row['surname']))), 0, 12);
                        } else {
                            $username = $usernameFormat;
                            $username = str_replace('[preferredNameInitial]', strtolower(substr($row['preferredName'], 0, 1)), $username);
                            $username = str_replace('[preferredName]', strtolower($row['preferredName']), $username);
                            $username = str_replace('[surname]', strtolower($row['surname']), $username);
                            $username = str_replace(' ', '', $username);
                            $username = str_replace("'", '', $username);
                            $username = str_replace("-", '', $username);
                            $username = substr($username, 0, 12);
                        }
                        $usernameBase = $username;
                        $count = 1;
                        $continueLoop = true;
                        while ($continueLoop == true and $count < 10000) {
                            $gotUsername = true;
                            try {
                                $dataUsername = array('username' => $username);
                                $sqlUsername = 'SELECT * FROM gibbonPerson WHERE username=:username';
                                $resultUsername = $connection2->prepare($sqlUsername);
                                $resultUsername->execute($dataUsername);
                            } catch (PDOException $e) {
                                $gotUsername = false;
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }

                            if ($resultUsername->rowCount() == 0 and $gotUsername == true) {
                                $continueLoop = false;
                            } else {
                                $username = $usernameBase.$count;
                            }
                            ++$count;
                        }

                        $password = randomPassword(8);
                        $salt = getSalt();
                        $passwordStrong = hash('sha256', $salt.$password);

                        $lastSchool = '';
                        if ($row['schoolDate1'] > $row['schoolDate2']) {
                            $lastSchool = $row['schoolName1'];
                        } elseif ($row['schoolDate2'] > $row['schoolDate1']) {
                            $lastSchool = $row['schoolName2'];
                        }

                        //Set default email address for student
                        $email = $row['email'];
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

                        //Email website and email address to admin for creation
                        if ($studentDefaultEmail != '' or $studentDefaultWebsite != '') {
                            echo '<h4>';
                            echo __($guid, 'Student Email & Website');
                            echo '</h4>';
                            $to = $_SESSION[$guid]['organisationAdministratorEmail'];
                            $subject = sprintf(__($guid, 'Create Student Email/Websites for %1$s at %2$s'), $_SESSION[$guid]['systemName'], $_SESSION[$guid]['organisationNameShort']);
                            $body = sprintf(__($guid, 'Please create the following for new student %1$s.'), formatName('', $row['preferredName'], $row['surname'], 'Student'))."<br/><br/>";
                            if ($studentDefaultEmail != '') {
                                $body .= __($guid, 'Email').': '.$email."<br/>";
                            }
                            if ($studentDefaultWebsite != '') {
                                $body .= __($guid, 'Website').': '.$website."<br/>";
                            }
                            if ($row['gibbonSchoolYearIDEntry'] != '') {
                                try {
                                    $dataYearGroup = array('gibbonSchoolYearID' => $row['gibbonSchoolYearIDEntry']);
                                    $sqlYearGroup = 'SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID';
                                    $resultYearGroup = $connection2->prepare($sqlYearGroup);
                                    $resultYearGroup->execute($dataYearGroup);
                                } catch (PDOException $e) {
                                }

                                if ($resultYearGroup->rowCount() == 1) {
                                    $rowYearGroup = $resultYearGroup->fetch();
                                    if ($rowYearGroup['name'] != '') {
                                        $body .= __($guid, 'School Year').': '.$rowYearGroup['name']."<br/>";
                                    }
                                }
                            }
                            if ($row['gibbonYearGroupIDEntry'] != '') {
                                try {
                                    $dataYearGroup = array('gibbonYearGroupID' => $row['gibbonYearGroupIDEntry']);
                                    $sqlYearGroup = 'SELECT * FROM gibbonYearGroup WHERE gibbonYearGroupID=:gibbonYearGroupID';
                                    $resultYearGroup = $connection2->prepare($sqlYearGroup);
                                    $resultYearGroup->execute($dataYearGroup);
                                } catch (PDOException $e) {
                                }

                                if ($resultYearGroup->rowCount() == 1) {
                                    $rowYearGroup = $resultYearGroup->fetch();
                                    if ($rowYearGroup['name'] != '') {
                                        $body .= __($guid, 'Year Group').': '.$rowYearGroup['name']."<br/>";
                                    }
                                }
                            }
                            if ($row['gibbonRollGroupID'] != '') {
                                try {
                                    $dataYearGroup = array('gibbonRollGroupID' => $row['gibbonRollGroupID']);
                                    $sqlYearGroup = 'SELECT * FROM gibbonRollGroup WHERE gibbonRollGroupID=:gibbonRollGroupID';
                                    $resultYearGroup = $connection2->prepare($sqlYearGroup);
                                    $resultYearGroup->execute($dataYearGroup);
                                } catch (PDOException $e) {
                                }

                                if ($resultYearGroup->rowCount() == 1) {
                                    $rowYearGroup = $resultYearGroup->fetch();
                                    if ($rowYearGroup['name'] != '') {
                                        $body .= __($guid, 'Roll Group').': '.$rowYearGroup['name']."<br/>";
                                    }
                                }
                            }
                            if ($row['dateStart'] != '') {
                                $body .= __($guid, 'Start Date').': '.dateConvertBack($guid, $row['dateStart'])."<br/>";
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
                            if ($row['gibbonYearGroupIDEntry'] == '' or $row['gibbonSchoolYearIDEntry'] == '' and $row['gender'] == '') { //No year group or school year set, so return error
                                $houseFail = true;
                            } else {
                                //Check boys and girls in each house in year group
                                try {
                                    $dataHouse = array('gibbonYearGroupID' => $row['gibbonYearGroupIDEntry'], 'gibbonSchoolYearID' => $row['gibbonSchoolYearIDEntry'], 'gender' => $row['gender']);
                                    $sqlHouse = "SELECT gibbonHouse.name AS house, gibbonHouse.gibbonHouseID, count(gibbonHouse.gibbonHouseID) AS count
                                        FROM gibbonHouse
                                            LEFT JOIN gibbonPerson ON (gibbonPerson.gibbonHouseID=gibbonHouse.gibbonHouseID AND gender=:gender)
                                            LEFT JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID
                                                AND gibbonSchoolYearID=:gibbonSchoolYearID
                                                AND gibbonYearGroupID=:gibbonYearGroupID)
                                        WHERE status='Full'
                                            AND NOT gibbonHouse.gibbonHouseID IS NULL
                                        GROUP BY house, gibbonHouse.gibbonHouseID
                                        ORDER BY count, gibbonHouse.gibbonHouseID";
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
                                $data = array('username' => $username, 'passwordStrong' => $passwordStrong, 'passwordStrongSalt' => $salt, 'surname' => $row['surname'], 'firstName' => $row['firstName'], 'preferredName' => $row['preferredName'], 'officialName' => $row['officialName'], 'nameInCharacters' => $row['nameInCharacters'], 'gender' => $row['gender'], 'dob' => $row['dob'], 'languageFirst' => $row['languageFirst'], 'languageSecond' => $row['languageSecond'], 'languageThird' => $row['languageThird'], 'countryOfBirth' => $row['countryOfBirth'], 'citizenship1' => $row['citizenship1'], 'citizenship1Passport' => $row['citizenship1Passport'], 'nationalIDCardNumber' => $row['nationalIDCardNumber'], 'residencyStatus' => $row['residencyStatus'], 'visaExpiryDate' => $row['visaExpiryDate'], 'email' => $email, 'emailAlternate' => $emailAlternate, 'website' => $website, 'phone1Type' => $row['phone1Type'], 'phone1CountryCode' => $row['phone1CountryCode'], 'phone1' => $row['phone1'], 'phone2Type' => $row['phone2Type'], 'phone2CountryCode' => $row['phone2CountryCode'], 'phone2' => $row['phone2'], 'lastSchool' => $lastSchool, 'dateStart' => $row['dateStart'], 'privacy' => $row['privacy'], 'dayType' => $row['dayType'], 'gibbonHouseID' => $gibbonHouseID, 'fields' => $row['fields']);
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
                                    $informStudentArray[0]['email'] = $row['email'];
                                    $informStudentArray[0]['surname'] = $row['surname'];
                                    $informStudentArray[0]['preferredName'] = $row['preferredName'];
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
                    echo '<li><b>'.__($guid, 'Name').'</b>: '.formatName('', $row['preferredName'], $row['surname'], 'Student').'</li>';
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
                    if ($row['gibbonRollGroupID'] != '') {
                        if ($gibbonPersonID != '' and $row['gibbonSchoolYearIDEntry'] != '' and $row['gibbonYearGroupIDEntry'] != '') {
                            try {
                                $data = array('gibbonPersonID' => $gibbonPersonID, 'gibbonSchoolYearID' => $row['gibbonSchoolYearIDEntry'], 'gibbonYearGroupID' => $row['gibbonYearGroupIDEntry'], 'gibbonRollGroupID' => $row['gibbonRollGroupID']);
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
                            echo '</ul>';
                        }
                    }

                    //SAVE PAYMENT PREFERENCES
                    $failPayment = true;
                    $invoiceTo = $row['payment'];
                    if ($invoiceTo == 'Company') {
                        $companyName = $row['companyName'];
                        $companyContact = $row['companyContact'];
                        $companyAddress = $row['companyAddress'];
                        $companyEmail = $row['companyEmail'];
                        $companyPhone = $row['companyPhone'];
                        $companyAll = $row['companyAll'];
                        $gibbonFinanceFeeCategoryIDList = null;
                        if ($companyAll == 'N') {
                            $gibbonFinanceFeeCategoryIDList = '';
                            $gibbonFinanceFeeCategoryIDArray = explode(',', $row['gibbonFinanceFeeCategoryIDList']);
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
                    if ($row['gibbonFamilyID'] != '') {
                        //CONNECT STUDENT TO FAMILY
                        try {
                            $dataFamily = array('gibbonFamilyID' => $row['gibbonFamilyID']);
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
                                    $data = array('gibbonPersonID' => $gibbonPersonID, 'gibbonFamilyID' => $row['gibbonFamilyID']);
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

                        try {
                            $dataParents = array('gibbonFamilyID' => $row['gibbonFamilyID']);
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
                                    $data = array('gibbonFamilyID' => $row['gibbonFamilyID'], 'gibbonPersonID1' => $rowParents['gibbonPersonID'], 'gibbonPersonID2' => $gibbonPersonID);
                                    $sql = 'SELECT * FROM gibbonFamilyRelationship WHERE gibbonFamilyID=:gibbonFamilyID AND gibbonPersonID1=:gibbonPersonID1 AND gibbonPersonID2=:gibbonPersonID2';
                                    $result = $connection2->prepare($sql);
                                    $result->execute($data);
                                } catch (PDOException $e) {
                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                }
                                if ($result->rowCount() == 0) {
                                    try {
                                        $data = array('gibbonFamilyID' => $row['gibbonFamilyID'], 'gibbonPersonID1' => $rowParents['gibbonPersonID'], 'gibbonPersonID2' => $gibbonPersonID, 'relationship' => $relationship);
                                        $sql = 'INSERT INTO gibbonFamilyRelationship SET gibbonFamilyID=:gibbonFamilyID, gibbonPersonID1=:gibbonPersonID1, gibbonPersonID2=:gibbonPersonID2, relationship=:relationship';
                                        $result = $connection2->prepare($sql);
                                        $result->execute($data);
                                    } catch (PDOException $e) {
                                        echo "<div class='error'>".$e->getMessage().'</div>';
                                    }
                                } elseif ($result->rowCount() == 1) {
                                    $row = $result->fetch();

                                    if ($row['relationship'] != $relationship) {
                                        try {
                                            $data = array('relationship' => $relationship, 'gibbonFamilyRelationshipID' => $row['gibbonFamilyRelationshipID']);
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
                            echo '<li><b>gibbonFamilyID</b>: '.$row['gibbonFamilyID'].'</li>';
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

                                $familyName = $row['parent1preferredName'].' '.$row['parent1surname'];
                                if ($row['parent2preferredName'] != '' and $row['parent2surname'] != '') {
                                    $familyName .= ' & '.$row['parent2preferredName'].' '.$row['parent2surname'];
                                }
                                $nameAddress = '';
                                //Parents share same surname and parent 2 has enough information to be added
                                if ($row['parent1surname'] == $row['parent2surname'] and $row['parent2preferredName'] != '' and $row['parent2title'] != '') {
                                    $nameAddress = $row['parent1title'].' & '.$row['parent2title'].' '.$row['parent1surname'];
                                }
                                //Parents have different names, and parent2 is not blank and has enough information to be added
                                elseif ($row['parent1surname'] != $row['parent2surname'] and $row['parent2surname'] != '' and $row['parent2preferredName'] != '' and $row['parent2title'] != '') {
                                    $nameAddress = $row['parent1title'].' '.$row['parent1surname'].' & '.$row['parent2title'].' '.$row['parent2surname'];
                                }
                                //Just use parent1's name
                                else {
                                    $nameAddress = $row['parent1title'].' '.$row['parent1surname'];
                                }
                                $languageHomePrimary = $row['languageHomePrimary'];
                                $languageHomeSecondary = $row['languageHomeSecondary'];

                                $insertOK = true;
                                try {
                                    $data = array('familyName' => $familyName, 'nameAddress' => $nameAddress, 'languageHomePrimary' => $languageHomePrimary, 'languageHomeSecondary' => $languageHomeSecondary, 'homeAddress' => $row['homeAddress'], 'homeAddressDistrict' => $row['homeAddressDistrict'], 'homeAddressCountry' => $row['homeAddressCountry']);
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
                                }
                            }

                            //CREATE PARENT 1
                            $failParent1 = true;
                            if ($row['parent1gibbonPersonID'] != '') {
                                $gibbonPersonIDParent1 = $row['parent1gibbonPersonID'];
                                echo '<h4>';
                                echo 'Parent 1';
                                echo '</h4>';
                                echo '<ul>';
                                echo '<li>'.__($guid, 'Parent 1 already exists in Gibbon, and so does not need a new account.').'</li>';
                                echo "<li><b>gibbonPersonID</b>: $gibbonPersonIDParent1</li>";
                                echo '<li><b>'.__($guid, 'Name').'</b>: '.formatName('', $row['parent1preferredName'], $row['parent1surname'], 'Parent').'</li>';
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
                                    $data = array('gibbonFamilyID' => $gibbonFamilyID, 'gibbonPersonID1' => $gibbonPersonIDParent1, 'gibbonPersonID2' => $gibbonPersonID, 'relationship' => $row['parent1relationship']);
                                    $sql = 'INSERT INTO gibbonFamilyRelationship SET gibbonFamilyID=:gibbonFamilyID, gibbonPersonID1=:gibbonPersonID1, gibbonPersonID2=:gibbonPersonID2, relationship=:relationship';
                                    $result = $connection2->prepare($sql);
                                    $result->execute($data);
                                } catch (PDOException $e) {
                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                }
                            } else {
                                $lock = true;
                                try {
                                    $sql = 'LOCK TABLES gibbonPerson WRITE';
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

                                        //Set username & password
                                        $username = '';
                                        if ($usernameFormat == '') {
                                            $username = substr(str_replace(' ', '', preg_replace('/[^A-Za-z ]/', '', strtolower(substr($row['parent1preferredName'], 0, 1).$row['parent1surname']))), 0, 12);
                                        } else {
                                            $username = $usernameFormat;
                                            $username = str_replace('[preferredNameInitial]', strtolower(substr($row['parent1preferredName'], 0, 1)), $username);
                                            $username = str_replace('[preferredName]', strtolower($row['parent1preferredName']), $username);
                                            $username = str_replace('[surname]', strtolower($row['parent1surname']), $username);
                                            $username = str_replace(' ', '', $username);
                                            $username = str_replace("'", '', $username);
                                            $username = str_replace("-", '', $username);
                                            $username = substr($username, 0, 12);
                                        }
                                        $usernameBase = $username;
                                        $count = 1;
                                        $continueLoop = true;
                                        while ($continueLoop == true and $count < 10000) {
                                            $gotUsername = true;
                                            try {
                                                $dataUsername = array('username' => $username);
                                                $sqlUsername = 'SELECT * FROM gibbonPerson WHERE username=:username';
                                                $resultUsername = $connection2->prepare($sqlUsername);
                                                $resultUsername->execute($dataUsername);
                                            } catch (PDOException $e) {
                                                $gotUsername = false;
                                                echo "<div class='error'>".$e->getMessage().'</div>';
                                            }

                                            if ($resultUsername->rowCount() == 0 and $gotUsername == true) {
                                                $continueLoop = false;
                                            } else {
                                                $username = $usernameBase.$count;
                                            }
                                            ++$count;
                                        }

                                        $password = randomPassword(8);
                                        $salt = getSalt();
                                        $passwordStrong = hash('sha256', $salt.$password);

                                        if ($continueLoop == false) {
                                            $insertOK = true;
                                            try {
                                                $data = array('username' => $username, 'passwordStrong' => $passwordStrong, 'passwordStrongSalt' => $salt, 'title' => $row['parent1title'], 'surname' => $row['parent1surname'], 'firstName' => $row['parent1firstName'], 'preferredName' => $row['parent1preferredName'], 'officialName' => $row['parent1officialName'], 'nameInCharacters' => $row['parent1nameInCharacters'], 'gender' => $row['parent1gender'], 'parent1languageFirst' => $row['parent1languageFirst'], 'parent1languageSecond' => $row['parent1languageSecond'], 'citizenship1' => $row['parent1citizenship1'], 'nationalIDCardNumber' => $row['parent1nationalIDCardNumber'], 'residencyStatus' => $row['parent1residencyStatus'], 'visaExpiryDate' => $row['parent1visaExpiryDate'], 'email' => $row['parent1email'], 'phone1Type' => $row['parent1phone1Type'], 'phone1CountryCode' => $row['parent1phone1CountryCode'], 'phone1' => $row['parent1phone1'], 'phone2Type' => $row['parent1phone2Type'], 'phone2CountryCode' => $row['parent1phone2CountryCode'], 'phone2' => $row['parent1phone2'], 'profession' => $row['parent1profession'], 'employer' => $row['parent1employer'], 'parent1fields' => $row['parent1fields']);
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
                                                    $informParentsArray[0]['email'] = $row['parent1email'];
                                                    $informParentsArray[0]['surname'] = $row['parent1surname'];
                                                    $informParentsArray[0]['preferredName'] = $row['parent1preferredName'];
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
                                    echo '<li><b>'.__($guid, 'Name').'</b>: '.formatName('', $row['parent1preferredName'], $row['parent1surname'], 'Parent').'</li>';
                                    echo '<li><b>'.__($guid, 'Email').'</b>: '.$row['parent1email'].'</li>';
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
                                            $data = array('gibbonFamilyID' => $gibbonFamilyID, 'gibbonPersonID1' => $gibbonPersonIDParent1, 'gibbonPersonID2' => $gibbonPersonID, 'relationship' => $row['parent1relationship']);
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
                            if ($row['parent2preferredName'] != '' and $row['parent2surname'] != '') {
                                $failParent2 = true;
                                $lock = true;
                                try {
                                    $sql = 'LOCK TABLES gibbonPerson WRITE';
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

                                        //Set username & password
                                        $username = '';
                                        if ($usernameFormat == '') {
                                            $username = substr(str_replace(' ', '', preg_replace('/[^A-Za-z ]/', '', strtolower(substr($row['parent2preferredName'], 0, 1).$row['parent2surname']))), 0, 12);
                                        } else {
                                            $username = $usernameFormat;
                                            $username = str_replace('[preferredNameInitial]', strtolower(substr($row['parent2preferredName'], 0, 1)), $username);
                                            $username = str_replace('[preferredName]', strtolower($row['parent2preferredName']), $username);
                                            $username = str_replace('[surname]', strtolower($row['parent2surname']), $username);
                                            $username = substr($username, 0, 12);
                                        }
                                        $usernameBase = $username;
                                        $count = 1;
                                        $continueLoop = true;
                                        while ($continueLoop == true and $count < 10000) {
                                            $gotUsername = true;
                                            try {
                                                $dataUsername = array('username' => $username);
                                                $sqlUsername = 'SELECT * FROM gibbonPerson WHERE username=:username';
                                                $resultUsername = $connection2->prepare($sqlUsername);
                                                $resultUsername->execute($dataUsername);
                                            } catch (PDOException $e) {
                                                $gotUsername = false;
                                                echo "<div class='error'>".$e->getMessage().'</div>';
                                            }

                                            if ($resultUsername->rowCount() == 0 and $gotUsername == true) {
                                                $continueLoop = false;
                                            } else {
                                                $username = $usernameBase.$count;
                                            }
                                            ++$count;
                                        }

                                        $password = randomPassword(8);
                                        $salt = getSalt();
                                        $passwordStrong = hash('sha256', $salt.$password);

                                        if ($continueLoop == false) {
                                            $insertOK = true;
                                            try {
                                                $data = array('username' => $username, 'passwordStrong' => $passwordStrong, 'passwordStrongSalt' => $salt, 'title' => $row['parent2title'], 'surname' => $row['parent2surname'], 'firstName' => $row['parent2firstName'], 'preferredName' => $row['parent2preferredName'], 'officialName' => $row['parent2officialName'], 'nameInCharacters' => $row['parent2nameInCharacters'], 'gender' => $row['parent2gender'], 'parent2languageFirst' => $row['parent2languageFirst'], 'parent2languageSecond' => $row['parent2languageSecond'], 'citizenship1' => $row['parent2citizenship1'], 'nationalIDCardNumber' => $row['parent2nationalIDCardNumber'], 'residencyStatus' => $row['parent2residencyStatus'], 'visaExpiryDate' => $row['parent2visaExpiryDate'], 'email' => $row['parent2email'], 'phone1Type' => $row['parent2phone1Type'], 'phone1CountryCode' => $row['parent2phone1CountryCode'], 'phone1' => $row['parent2phone1'], 'phone2Type' => $row['parent2phone2Type'], 'phone2CountryCode' => $row['parent2phone2CountryCode'], 'phone2' => $row['parent2phone2'], 'profession' => $row['parent2profession'], 'employer' => $row['parent2employer'], 'parent2fields' => $row['parent2fields']);
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
                                                    $informParentsArray[1]['email'] = $row['parent2email'];
                                                    $informParentsArray[1]['surname'] = $row['parent2surname'];
                                                    $informParentsArray[1]['preferredName'] = $row['parent2preferredName'];
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
                                    echo '<li><b>'.__($guid, 'Name').'</b>: '.formatName('', $row['parent2preferredName'], $row['parent2surname'], 'Parent').'</li>';
                                    echo '<li><b>'.__($guid, 'Email').'</b>: '.$row['parent2email'].'</li>';
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
                                            $data = array('gibbonFamilyID' => $gibbonFamilyID, 'gibbonPersonID1' => $gibbonPersonIDParent2, 'gibbonPersonID2' => $gibbonPersonID, 'relationship' => $row['parent2relationship']);
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
                        $notificationStudentMessage = getSettingByScope($connection2, 'Application Form', 'notificationStudentMessage');
                        foreach ($informStudentArray as $informStudentEntry) {
                            if ($informStudentEntry['email'] != '' and $informStudentEntry['surname'] != '' and $informStudentEntry['preferredName'] != '' and $informStudentEntry['username'] != '' and $informStudentEntry['password']) {
                                $to = $informStudentEntry['email'];
                                $subject = sprintf(__($guid, 'Welcome to %1$s at %2$s'), $_SESSION[$guid]['systemName'], $_SESSION[$guid]['organisationNameShort']);
                                if ($notificationStudentMessage != '') {
                                    $body = sprintf(__($guid, 'Dear %1$s,<br/><br/>Welcome to %2$s, %3$s\'s system for managing school information. You can access the system by going to %4$s and logging in with your new username (%5$s) and password (%6$s).<br/><br/>In order to maintain the security of your data, we highly recommend you change your password to something easy to remember but hard to guess. This can be done by using the Preferences page after logging in (top-right of the screen).<br/><br/>'), formatName('', $informStudentEntry['preferredName'], $informStudentEntry['surname'], 'Student'), $_SESSION[$guid]['systemName'], $_SESSION[$guid]['organisationNameShort'], $_SESSION[$guid]['absoluteURL'], $informStudentEntry['username'], $informStudentEntry['password']).$notificationStudentMessage.sprintf(__($guid, 'Please feel free to reply to this email should you have any questions.<br/><br/>%1$s,<br/><br/>%2$s Administrator'), $_SESSION[$guid]['organisationAdministratorName'], $_SESSION[$guid]['systemName']);
                                } else {
                                    $body = 'Dear '.formatName('', $informStudentEntry['preferredName'], $informStudentEntry['surname'], 'Student').",<br/><br/>Welcome to ".$_SESSION[$guid]['systemName'].', '.$_SESSION[$guid]['organisationNameShort']."'s system for managing school information. You can access the system by going to ".$_SESSION[$guid]['absoluteURL'].' and logging in with your new username ('.$informStudentEntry['username'].') and password ('.$informStudentEntry['password'].").<br/><br/>In order to maintain the security of your data, we highly recommend you change your password to something easy to remember but hard to guess. This can be done by using the Preferences page after logging in (top-right of the screen).<br/><br/>Please feel free to reply to this email should you have any questions.<br/><br/>".$_SESSION[$guid]['organisationAdministratorName'].",<br/><br/>".$_SESSION[$guid]['systemName'].' Administrator';
                                }
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
                                    echo __($guid, 'A welcome email was successfully sent to').' '.formatName('', $informStudentEntry['preferredName'], $informStudentEntry['surname'], 'Student').'.';
                                    echo '</div>';
                                } else {
                                    echo "<div class='error'>";
                                    echo __($guid, 'A welcome email could not be sent to').' '.formatName('', $informStudentEntry['preferredName'], $informStudentEntry['surname'], 'Student').'.';
                                    echo '</div>';
                                }
                            }
                        }
                    }

                    //SEND PARENTS EMAIL
                    if ($informParents == 'Y') {
                        echo '<h4>';
                        echo 'Parent Welcome Email';
                        echo '</h4>';
                        $notificationParentsMessage = getSettingByScope($connection2, 'Application Form', 'notificationParentsMessage');
                        foreach ($informParentsArray as $informParentsEntry) {
                            if ($informParentsEntry['email'] != '' and $informParentsEntry['surname'] != '' and $informParentsEntry['preferredName'] != '' and $informParentsEntry['username'] != '' and $informParentsEntry['password']) {
                                $to = $informParentsEntry['email'];
                                $subject = sprintf(__($guid, 'Welcome to %1$s at %2$s'), $_SESSION[$guid]['systemName'], $_SESSION[$guid]['organisationNameShort']);
                                if ($notificationParentsMessage != '') {
                                    $body = sprintf(__($guid, 'Dear %1$s,<br/><br/>Welcome to %2$s, %3$s\'s system for managing school information. You can access the system by going to %4$s and logging in with your new username (%5$s) and password (%6$s). You can learn more about using %7$s on the official support website (https://gibbonedu.org/support/parents).<br/><br/>In order to maintain the security of your data, we highly recommend you change your password to something easy to remember but hard to guess. This can be done by using the Preferences page after logging in (top-right of the screen).<br/><br/>'), formatName('', $informParentsEntry['preferredName'], $informParentsEntry['surname'], 'Student'), $_SESSION[$guid]['systemName'], $_SESSION[$guid]['organisationNameShort'], $_SESSION[$guid]['absoluteURL'], $informParentsEntry['username'], $informParentsEntry['password'], $_SESSION[$guid]['systemName']).$notificationParentsMessage.sprintf(__($guid, 'Please feel free to reply to this email should you have any questions.<br/><br/>%1$s,<br/><br/>%2$s Administrator'), $_SESSION[$guid]['organisationAdministratorName'], $_SESSION[$guid]['systemName']);
                                } else {
                                    $body = sprintf(__($guid, 'Dear %1$s,<br/><br/>Welcome to %2$s, %3$s\'s system for managing school information. You can access the system by going to %4$s and logging in with your new username (%5$s) and password (%6$s). You can learn more about using %7$s on the official support website (https://gibbonedu.org/support/parents).<br/><br/>In order to maintain the security of your data, we highly recommend you change your password to something easy to remember but hard to guess. This can be done by using the Preferences page after logging in (top-right of the screen).<br/><br/>'), formatName('', $informParentsEntry['preferredName'], $informParentsEntry['surname'], 'Student'), $_SESSION[$guid]['systemName'], $_SESSION[$guid]['organisationNameShort'], $_SESSION[$guid]['absoluteURL'], $informParentsEntry['username'], $informParentsEntry['password'], $_SESSION[$guid]['systemName']).sprintf(__($guid, 'Please feel free to reply to this email should you have any questions.<br/><br/>%1$s,<br/><br/>%2$s Administrator'), $_SESSION[$guid]['organisationAdministratorName'], $_SESSION[$guid]['systemName']);
                                }
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
                                    echo __($guid, 'A welcome email was successfully sent to').' '.formatName('', $informParentsEntry['preferredName'], $informParentsEntry['surname'], 'Student').'.';
                                    echo '</div>';
                                } else {
                                    echo "<div class='error'>";
                                    echo __($guid, 'A welcome email could not be sent to').' '.formatName('', $informParentsEntry['preferredName'], $informParentsEntry['surname'], 'Student').'.';
                                    echo '</div>';
                                }
                            }
                        }
                    }
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
                        echo __($guid, 'Applicant has been successfully accepted into ICHK.').' <i><u>'.__($guid, 'You may wish to now do the following:').'</u></i><br/>';
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
