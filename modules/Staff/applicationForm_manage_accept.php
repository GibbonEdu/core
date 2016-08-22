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
require $_SESSION[$guid]['absolutePath'].'/lib/PHPMailer/class.phpmailer.php';

if (isActionAccessible($guid, $connection2, '/modules/Staff/applicationForm_manage_accept.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Staff/applicationForm_manage.php'>".__($guid, 'Manage Applications')."</a> > </div><div class='trailEnd'>".__($guid, 'Accept Application').'</div>';
    echo '</div>';

    //Check if school year specified
    $gibbonStaffApplicationFormID = $_GET['gibbonStaffApplicationFormID'];
    $search = $_GET['search'];
    if ($gibbonStaffApplicationFormID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonStaffApplicationFormID' => $gibbonStaffApplicationFormID);
            $sql = "SELECT gibbonStaffApplicationForm.*, gibbonStaffJobOpening.jobTitle, gibbonStaffJobOpening.type FROM gibbonStaffApplicationForm JOIN gibbonStaffJobOpening ON (gibbonStaffApplicationForm.gibbonStaffJobOpeningID=gibbonStaffJobOpening.gibbonStaffJobOpeningID) LEFT JOIN gibbonPerson ON (gibbonStaffApplicationForm.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonStaffApplicationFormID=:gibbonStaffApplicationFormID AND gibbonStaffApplicationForm.status='Pending'";
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
                    echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Staff/applicationForm_manage.php&search=$search'>".__($guid, 'Back to Search Results').'</a>';
                }
                echo '</div>'; ?>
				<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/applicationForm_manage_accept.php&step=2&gibbonStaffApplicationFormID=$gibbonStaffApplicationFormID&search=$search" ?>">
					<table class='smallIntBorder fullWidth' cellspacing='0'>
						<tr>
							<td>
								<b><?php echo sprintf(__($guid, 'Are you sure you want to accept the application for %1$s?'), formatName('', $row['preferredName'], $row['surname'], 'Student')) ?></b><br/>
								<br/>
								<?php
                                $checkedapplicant = '';
								if (getSettingByScope($connection2, 'Staff', 'staffApplicationFormNotificationDefault') == 'Y') {
									$checkedapplicant = 'checked';
								}
								?>
								<input <?php echo $checkedapplicant ?> type='checkbox' name='informApplicant'/> <?php echo __($guid, 'Automatically inform <u>applicant</u> of their Gibbon login details by email?') ?><br/>

								<br/>
								<i><u><?php echo __($guid, 'The system will perform the following actions:') ?></u></i><br/>
								<ol>
									<?php
                                    if ($row['gibbonPersonID'] == '') {
                                        echo '<li>'.__($guid, 'Create a Gibbon user account for the applicant.').'</li>';
                                        echo '<li>'.__($guid, 'Register the user as a member of staff.').'</li>';
                                        echo '<li>'.__($guid, 'Set the status of the application to "Accepted".').'</li>';
                                    } else {
                                        echo '<li>'.__($guid, 'Register the user as a member of staff, if not already done.').'</li>';
                                        echo '<li>'.__($guid, 'Set the status of the application to "Accepted".').'</li>';
                                    }
                                    ?>
								</ol>
								<br/>
								<i><u><?php echo __($guid, 'But you may wish to manually do the following:') ?></u></i><br/>
								<ol>
									<li><?php echo __($guid, 'Adjust the user\'s roles within the system.') ?></li>
									<li><?php echo __($guid, 'Create a timetable for the applicant.') ?></li>
								</ol>
							</td>
						</tr>
						<tr>
							<td class='right'>
								<input name="gibbonStaffApplicationFormID" id="gibbonStaffApplicationFormID" value="<?php echo $gibbonStaffApplicationFormID ?>" type="hidden">
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
                    echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Staff/applicationForm_manage.php&search=$search'>".__($guid, 'Back to Search Results').'</a>';
                }
                echo '</div>';

                if ($row['gibbonPersonID'] == '') { //USER IS NEW TO THE SYSTEM
                    $informApplicant = 'N';
                    if (isset($_POST['informApplicant'])) {
                        if ($_POST['informApplicant'] == 'on') {
                            $informApplicant = 'Y';
                            $informApplicantArray = array();
                        }
                    }

                    //DETERMINE ROLE
                    $gibbonRoleID = '006'; //Support staff by default
                    if ($row['type'] == 'Teaching') {
                        $gibbonRoleID = '002';
                    } elseif ($row['type'] != 'Support') { //Work out role based on type, which appears to be drawn from role anyway
                        try {
                            $dataRole = array('name' => $row['type']);
                            $sqlRole = 'SELECT gibbonRoleID FROM gibbonRole WHERE name=:name';
                            $resultRole = $connection2->prepare($sqlRole);
                            $resultRole->execute($dataRole);
                        } catch (PDOException $e) {
                        }
                        if ($resultRole->rowCount() == 1) {
                            $rowRole = $resultRole->fetch();
                            $gibbonRoleID = $rowRole['gibbonRoleID'];
                        }
                    }

                    //CREATE APPLICANT
                    $failapplicant = true;
                    $lock = true;
                    try {
                        $sql = 'LOCK TABLES gibbonPerson WRITE, gibbonStaffApplicationForm WRITE, gibbonSetting WRITE, gibbonStaff WRITE, gibbonStaffJobOpening WRITE';
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
                            $usernameFormat = getSettingByScope($connection2, 'Staff', 'staffApplicationFormUsernameFormat');
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

                            //Set default email address for applicant
                            $email = $row['email'];
                            $emailAlternate = '';
                            $applicantDefaultEmail = getSettingByScope($connection2, 'Staff', 'staffApplicationFormDefaultEmail');
                            if ($applicantDefaultEmail != '') {
                                $emailAlternate = $email;
                                $email = str_replace('[username]', $username, $applicantDefaultEmail);
                            }

                            //Set default website address for applicant
                            $website = '';
                            $applicantDefaultWebsite = getSettingByScope($connection2, 'Staff', 'staffApplicationFormDefaultWebsite');
                            if ($applicantDefaultWebsite != '') {
                                $website = str_replace('[username]', $username, $applicantDefaultWebsite);
                            }

                            //Email website and email address to admin for creation
                            if ($applicantDefaultEmail != '' or $applicantDefaultWebsite != '') {
                                echo '<h4>';
                                echo __($guid, 'New Staff Member Email & Website');
                                echo '</h4>';
                                $to = $_SESSION[$guid]['organisationAdministratorEmail'];
                                $subject = sprintf(__($guid, 'Create applicant Email/Websites for %1$s at %2$s'), $_SESSION[$guid]['systemName'], $_SESSION[$guid]['organisationNameShort']);
                                $body = sprintf(__($guid, 'Please create the following for new staff member %1$s.'), formatName('', $row['preferredName'], $row['surname'], 'Student'))."<br/><br/>";
                                if ($applicantDefaultEmail != '') {
                                    $body .= __($guid, 'Email').': '.$email."<br/>";
                                }
                                if ($applicantDefaultWebsite != '') {
                                    $body .= __($guid, 'Website').': '.$website."<br/>";
                                }
                                if ($row['dateStart'] != '') {
                                    $body .= __($guid, 'Start Date').': '.dateConvertBack($guid, $row['dateStart'])."<br/>";
                                }
                                $body .= __($guid, 'Job Type').': '.dateConvertBack($guid, $row['type'])."<br/>";
                                $body .= __($guid, 'Job Title').': '.dateConvertBack($guid, $row['jobTitle'])."<br/>";
                                $bodyPlain = emailBodyConvert($body);

                                $mail = new PHPMailer();
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
                                    echo sprintf(__($guid, 'A request to create a applicant email address and/or website address was successfully sent to %1$s.'), $_SESSION[$guid]['organisationAdministratorName']);
                                    echo '</div>';
                                } else {
                                    echo "<div class='error'>";
                                    echo sprintf(__($guid, 'A request to create a applicant email address and/or website address failed. Please contact %1$s to request these manually.'), $_SESSION[$guid]['organisationAdministratorName']);
                                    echo '</div>';
                                }
                            }

                            if ($continueLoop == false) {
                                $insertOK = true;
                                try {
                                    $data = array('username' => $username, 'passwordStrong' => $passwordStrong, 'passwordStrongSalt' => $salt, 'surname' => $row['surname'], 'firstName' => $row['firstName'], 'preferredName' => $row['preferredName'], 'officialName' => $row['officialName'], 'nameInCharacters' => $row['nameInCharacters'], 'gender' => $row['gender'], 'dob' => $row['dob'], 'languageFirst' => $row['languageFirst'], 'languageSecond' => $row['languageSecond'], 'languageThird' => $row['languageThird'], 'countryOfBirth' => $row['countryOfBirth'], 'citizenship1' => $row['citizenship1'], 'citizenship1Passport' => $row['citizenship1Passport'], 'nationalIDCardNumber' => $row['nationalIDCardNumber'], 'residencyStatus' => $row['residencyStatus'], 'visaExpiryDate' => $row['visaExpiryDate'], 'email' => $email, 'emailAlternate' => $emailAlternate, 'website' => $website, 'phone1Type' => $row['phone1Type'], 'phone1CountryCode' => $row['phone1CountryCode'], 'phone1' => $row['phone1'], 'dateStart' => $row['dateStart'], 'fields' => $row['fields']);
                                    $sql = "INSERT INTO gibbonPerson SET username=:username, password='', passwordStrong=:passwordStrong, passwordStrongSalt=:passwordStrongSalt, gibbonRoleIDPrimary='$gibbonRoleID', gibbonRoleIDAll='$gibbonRoleID', status='Full', surname=:surname, firstName=:firstName, preferredName=:preferredName, officialName=:officialName, nameInCharacters=:nameInCharacters, gender=:gender, dob=:dob, languageFirst=:languageFirst, languageSecond=:languageSecond, languageThird=:languageThird, countryOfBirth=:countryOfBirth, citizenship1=:citizenship1, citizenship1Passport=:citizenship1Passport, nationalIDCardNumber=:nationalIDCardNumber, residencyStatus=:residencyStatus, visaExpiryDate=:visaExpiryDate, email=:email, emailAlternate=:emailAlternate, website=:website, phone1Type=:phone1Type, phone1CountryCode=:phone1CountryCode, phone1=:phone1, dateStart=:dateStart, fields=:fields";
                                    $result = $connection2->prepare($sql);
                                    $result->execute($data);
                                } catch (PDOException $e) {
                                    $insertOK = false;
                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                }
                                if ($insertOK == true) {
                                    $failapplicant = false;

                                    //Populate informApplicant array
                                    if ($informApplicant == 'Y') {
                                        $informApplicantArray[0]['email'] = $row['email'];
                                        $informApplicantArray[0]['surname'] = $row['surname'];
                                        $informApplicantArray[0]['preferredName'] = $row['preferredName'];
                                        $informApplicantArray[0]['username'] = $username;
                                        $informApplicantArray[0]['password'] = $password;
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

                    if ($failapplicant == true) {
                        echo "<div class='error'>";
                        echo __($guid, 'Applicant could not be created!');
                        echo '</div>';
                    } else {
                        echo '<h4>';
                        echo __($guid, 'Applicant Details');
                        echo '</h4>';
                        echo '<ul>';
                        echo "<li><b>gibbonPersonID</b>: $gibbonPersonID</li>";
                        echo '<li><b>'.__($guid, 'Name').'</b>: '.formatName('', $row['preferredName'], $row['surname'], 'Student').'</li>';
                        echo '<li><b>'.__($guid, 'Email').'</b>: '.$email.'</li>';
                        echo '<li><b>'.__($guid, 'Email Alternate').'</b>: '.$emailAlternate.'</li>';
                        echo '<li><b>'.__($guid, 'Username')."</b>: $username</li>";
                        echo '<li><b>'.__($guid, 'Password')."</b>: $password</li>";
                        echo '</ul>';

                        //Enrol applicant
                        $enrolmentOK = true;
                        try {
                            $data = array('gibbonPersonID' => $gibbonPersonID, 'type' => $row['type'], 'jobTitle' => $row['jobTitle']);
                            $sql = 'INSERT INTO gibbonStaff SET gibbonPersonID=:gibbonPersonID, type=:type, jobTitle=:jobTitle';
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            $enrolmentOK = false;
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }

                        //Report back
                        if ($enrolmentOK == false) {
                            echo "<div class='warning'>";
                            echo __($guid, 'Applicant could not be added to staff listing, so this will have to be done manually at a later date.');
                            echo '</div>';
                        } else {
                            echo '<h4>';
                            echo 'Applicant Enrolment';
                            echo '</h4>';
                            echo '<ul>';
                            echo '<li>'.__($guid, 'The applicant has successfully been added to staff listing.').'</li>';
                            echo '</ul>';
                        }

                        //SEND APPLICANT EMAIL
                        if ($informApplicant == 'Y') {
                            echo '<h4>';
                            echo __($guid, 'New Staff Member Welcome Email');
                            echo '</h4>';
                            $notificationApplicantMessage = getSettingByScope($connection2, 'Staff', 'staffApplicationFormNotificationMessage');
                            foreach ($informApplicantArray as $informApplicantEntry) {
                                if ($informApplicantEntry['email'] != '' and $informApplicantEntry['surname'] != '' and $informApplicantEntry['preferredName'] != '' and $informApplicantEntry['username'] != '' and $informApplicantEntry['password']) {
                                    $to = $informApplicantEntry['email'];
                                    $subject = sprintf(__($guid, 'Welcome to %1$s at %2$s'), $_SESSION[$guid]['systemName'], $_SESSION[$guid]['organisationNameShort']);
                                    if ($notificationApplicantMessage != '') {
                                        $body = sprintf(__($guid, 'Dear %1$s,<br/><br/>Welcome to %2$s, %3$s\'s system for managing school information. You can access the system by going to %4$s and logging in with your new username (%5$s) and password (%6$s).<br/><br/>In order to maintain the security of your data, we highly recommend you change your password to something easy to remember but hard to guess. This can be done by using the Preferences page after logging in (top-right of the screen).<br/><br/>'), formatName('', $informApplicantEntry['preferredName'], $informApplicantEntry['surname'], 'Student'), $_SESSION[$guid]['systemName'], $_SESSION[$guid]['organisationNameShort'], $_SESSION[$guid]['absoluteURL'], $informApplicantEntry['username'], $informApplicantEntry['password']).$notificationApplicantMessage.sprintf(__($guid, 'Please feel free to reply to this email should you have any questions.<br/><br/>%1$s,<br/><br/>%2$s Administrator'), $_SESSION[$guid]['organisationAdministratorName'], $_SESSION[$guid]['systemName']);
                                    } else {
                                        $body = 'Dear '.formatName('', $informApplicantEntry['preferredName'], $informApplicantEntry['surname'], 'Student').",<br/><br/>Welcome to ".$_SESSION[$guid]['systemName'].', '.$_SESSION[$guid]['organisationNameShort']."'s system for managing school information. You can access the system by going to ".$_SESSION[$guid]['absoluteURL'].' and logging in with your new username ('.$informApplicantEntry['username'].') and password ('.$informApplicantEntry['password'].").<br/><br/>In order to maintain the security of your data, we highly recommend you change your password to something easy to remember but hard to guess. This can be done by using the Preferences page after logging in (top-right of the screen).<br/><br/>Please feel free to reply to this email should you have any questions.<br/><br/>".$_SESSION[$guid]['organisationAdministratorName'].",<br/><br/>".$_SESSION[$guid]['systemName'].' Administrator';
                                    }
                                    $bodyPlain = emailBodyConvert($body);

                                    $mail = new PHPMailer();
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
                                        echo __($guid, 'A welcome email was successfully sent to').' '.formatName('', $informApplicantEntry['preferredName'], $informApplicantEntry['surname'], 'Student').'.';
                                        echo '</div>';
                                    } else {
                                        echo "<div class='error'>";
                                        echo __($guid, 'A welcome email could not be sent to').' '.formatName('', $informApplicantEntry['preferredName'], $informApplicantEntry['surname'], 'Student').'.';
                                        echo '</div>';
                                    }
                                }
                            }
                        }
                    }
                } else { //IF NOT IN THE SYSTEM AS STAFF, THEN ADD THEM
                    echo '<h4>';
                    echo 'Staff Listing';
                    echo '</h4>';

                    $alreadyEnroled = false;
                    $enrolmentCheckFail = false;
                    try {
                        $data = array('gibbonPersonID' => $row['gibbonPersonID']);
                        $sql = 'SELECT * FROM gibbonStaff WHERE gibbonPersonID=:gibbonPersonID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $enrolmentCheckFail = true;
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }
                    if ($result->rowCount() == 1) {
                        $alreadyEnroled = true;
                    }
                    if ($enrolmentCheckFail) { //Enrolment check did not work, so report error
                        echo "<div class='warning'>";
                        echo __($guid, 'Applicant could not be added to staff listing, so this will have to be done manually at a later date.');
                        echo '</div>';
                    } elseif ($alreadyEnroled) { //User is already enroled, so display message
                        echo "<div class='warning'>";
                        echo __($guid, 'Applicant already exists in staff listing.');
                        echo '</div>';
                    } else { //User is not yet enroled, so try and enrol them.
                        $enrolmentOK = true;

                        try {
                            $data = array('gibbonPersonID' => $row['gibbonPersonID'], 'type' => $row['type'], 'jobTitle' => $row['jobTitle']);
                            $sql = 'INSERT INTO gibbonStaff SET gibbonPersonID=:gibbonPersonID, type=:type, jobTitle=:jobTitle';
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            $enrolmentOK = false;
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }

                        //Report back
                        if ($enrolmentOK == false) {
                            echo "<div class='warning'>";
                            echo __($guid, 'Applicant could not be added to staff listing, so this will have to be done manually at a later date.');
                            echo '</div>';
                        } else {
                            echo '<ul>';
                            echo '<li>'.__($guid, 'The applicant has successfully been added to staff listing.').'</li>';
                            echo '</ul>';
                        }
                    }
                }

                //SET STATUS TO ACCEPTED
                $failStatus = false;
                try {
                    $data = array('gibbonStaffApplicationFormID' => $gibbonStaffApplicationFormID);
                    $sql = "UPDATE gibbonStaffApplicationForm SET status='Accepted' WHERE gibbonStaffApplicationFormID=:gibbonStaffApplicationFormID";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $failStatus = true;
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                if ($failStatus == true) {
                    echo "<div class='error'>";
                    echo __($guid, 'Applicant status could not be updated: applicant is in the system, but acceptance has failed.');
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
                    echo '<li>'.__($guid, 'Adjust the user\'s roles within the system.').'</li>';
                    echo '<li>'.__($guid, 'Create a timetable for the applicant.').'</li>';
                    echo '</ol>';
                    echo '</div>';
                }
            }
        }
    }
}
?>
