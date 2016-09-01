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

require getcwd().'/../config.php';
require getcwd().'/../functions.php';
require getcwd().'/../lib/PHPMailer/class.phpmailer.php';

$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

@session_start();

getSystemSettings($guid, $connection2);

setCurrentSchoolYear($guid, $connection2);

//Set up for i18n via gettext
if (isset($_SESSION[$guid]['i18n']['code'])) {
    if ($_SESSION[$guid]['i18n']['code'] != null) {
        putenv('LC_ALL='.$_SESSION[$guid]['i18n']['code']);
        setlocale(LC_ALL, $_SESSION[$guid]['i18n']['code']);
        bindtextdomain('gibbon', getcwd().'/../i18n');
        textdomain('gibbon');
    }
}

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]['timezone']);

//Check for CLI, so this cannot be run through browser
if (php_sapi_name()!="cli") {
	print __($guid, "This script cannot be run from a browser, only via CLI.") ;
}
else {
    //Check that one of the days in question is a school day
    $isSchoolOpen = false;
    for ($i = 0; $i < 7; ++$i) {
        if (isSchoolOpen($guid, date('Y-m-d', strtotime("-$i day")), $connection2, true) == true) {
            $isSchoolOpen = true;
        }
    }

    if ($isSchoolOpen == false) { //No school on any day in the last week
        echo __($guid, 'School is not open, so no emails will be sent.');
    } else { //Yes school, so go ahead.
        if ($_SESSION[$guid]['organisationEmail'] == '') {
            echo __($guid, 'This script cannot be run, as no school email address has been set.');
        } else {
            //Lock table
            $lock = true;
            try {
                $sqlLock = 'LOCK TABLE gibbonBehaviour WRITE, gibbonCourse WRITE, gibbonCourse AS gibbonCourse2 WRITE, gibbonCourseClass WRITE, gibbonCourseClass AS gibbonCourseClass2 WRITE, gibbonCourseClassPerson WRITE, gibbonCourseClassPerson AS gibbonCourseClassPerson2 WRITE, gibbonFamily WRITE, gibbonFamilyAdult WRITE, gibbonFamilyChild WRITE, gibbonPerson WRITE, gibbonPlannerEntry WRITE, gibbonPlannerEntry AS gibbonPlannerEntry2 WRITE, gibbonPlannerEntryStudentHomework WRITE, gibbonPlannerParentWeeklyEmailSummary WRITE, gibbonRollGroup WRITE, gibbonStudentEnrolment WRITE';
                $resultLock = $connection2->query($sqlLock);
            } catch (PDOException $e) {
                $lock = false;
            }

            if (!$lock) {
                echo __($guid, 'Your request failed due to a database error.');
            } else {
                //Get list of all current students
                try {
                    $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                    $sql = "SELECT gibbonPerson.gibbonPersonID, preferredName, surname, gibbonRollGroup.gibbonRollGroupID, gibbonRollGroup.name AS name, 'Student' AS role FROM gibbonPerson, gibbonStudentEnrolment, gibbonRollGroup WHERE gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID AND status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY surname, preferredName";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                }

                $studentCount = $result->rowCount();
                $sendSucceedCount = 0;
                $sendFailCount = 0;

                if ($studentCount < 1) { //No students to display
                    echo __($guid, 'There are no records to display.');
                } else { //Students to display so get going
                    while ($row = $result->fetch()) {
                        //Get all homework for the past week, ready for email
                        $homework = '';
                        $homework .= '<h2>'.__($guid, 'Homework').'</h2>';
                        try {
                            $dataHomework = array('gibbonPersonID1' => $row['gibbonPersonID'], 'gibbonSchoolYearID1' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID2' => $row['gibbonPersonID'], 'gibbonSchoolYearID2' => $_SESSION[$guid]['gibbonSchoolYearID']);
                            $sqlHomework = "
							(SELECT 'teacherRecorded' AS type, gibbonPlannerEntryID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, viewableStudents, viewableParents, homework, role, homeworkDueDateTime, homeworkDetails, homeworkSubmission, homeworkSubmissionRequired FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID1 AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND homework='Y' AND gibbonSchoolYearID=:gibbonSchoolYearID1 AND date>'".date('Y-m-d', strtotime('-1 week'))."' AND date<='".date('Y-m-d')."')
							UNION
							(SELECT 'studentRecorded' AS type, gibbonPlannerEntry2.gibbonPlannerEntryID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry2.gibbonCourseClassID, gibbonCourse2.nameShort AS course, gibbonCourseClass2.nameShort AS class, gibbonPlannerEntry2.name, date, timeStart, timeEnd, 'Y' AS viewableStudents, 'Y' AS viewableParents, 'Y' AS homework, role, gibbonPlannerEntryStudentHomework.homeworkDueDateTime AS homeworkDueDateTime, gibbonPlannerEntryStudentHomework.homeworkDetails AS homeworkDetails, 'N' AS homeworkSubmission, '' AS homeworkSubmissionRequired FROM gibbonPlannerEntry AS gibbonPlannerEntry2 JOIN gibbonCourseClass AS gibbonCourseClass2 ON (gibbonPlannerEntry2.gibbonCourseClassID=gibbonCourseClass2.gibbonCourseClassID) JOIN gibbonCourseClassPerson AS gibbonCourseClassPerson2 ON (gibbonCourseClass2.gibbonCourseClassID=gibbonCourseClassPerson2.gibbonCourseClassID) JOIN gibbonCourse AS gibbonCourse2 ON (gibbonCourse2.gibbonCourseID=gibbonCourseClass2.gibbonCourseID) JOIN gibbonPlannerEntryStudentHomework ON (gibbonPlannerEntryStudentHomework.gibbonPlannerEntryID=gibbonPlannerEntry2.gibbonPlannerEntryID AND gibbonPlannerEntryStudentHomework.gibbonPersonID=gibbonCourseClassPerson2.gibbonPersonID) WHERE gibbonCourseClassPerson2.gibbonPersonID=:gibbonPersonID2 AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND gibbonSchoolYearID=:gibbonSchoolYearID2 AND date>'".date('Y-m-d', strtotime('-1 week'))."' AND date<='".date('Y-m-d')."')
							ORDER BY date, timeStart";
                            $resultHomework = $connection2->prepare($sqlHomework);
                            $resultHomework->execute($dataHomework);
                        } catch (PDOException $e) {
                            $homework .= $e->getMessage();
                        }
                        if ($resultHomework->rowCount() > 0) {
                            $homework .= '<ul>';
                            while ($rowHomework = $resultHomework->fetch()) {
                                $homework .= '<li><b>'.$rowHomework['course'].'.'.$rowHomework['class'].'</b> - '.$rowHomework['name'].' - '.sprintf(__($guid, 'Due on %1$s at %2$s.'), dateConvertBack($guid, substr($rowHomework['homeworkDueDateTime'], 0, 10)), substr($rowHomework['homeworkDueDateTime'], 11, 5)).'</li>';
                            }
                            $homework .= '</ul><br/>';
                        } else {
                            $homework .= __($guid, 'There are no records to display.').'<br/><br/>';
                        }

                        $behaviour = '';
                        $parentWeeklyEmailSummaryIncludeBehaviour = getSettingByScope($connection2, 'Planner', 'parentWeeklyEmailSummaryIncludeBehaviour');
                        if ($parentWeeklyEmailSummaryIncludeBehaviour == 'Y') {
                            //Get behaviour records for the past week, ready for email
                            $behaviour .= '<h2>'.__($guid, 'Behaviour').'</h2>';
                            try {
                                $dataBehaviourPositive = array('gibbonPersonID' => $row['gibbonPersonID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                                $sqlBehaviourPositive = "SELECT * FROM gibbonBehaviour WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND type='Positive' AND date>'".date('Y-m-d', strtotime('-1 week'))."' AND date<='".date('Y-m-d')."'";
                                $resultBehaviourPositive = $connection2->prepare($sqlBehaviourPositive);
                                $resultBehaviourPositive->execute($dataBehaviourPositive);
                            } catch (PDOException $e) {
                            }
                            try {
                                $dataBehaviourNegative = array('gibbonPersonID' => $row['gibbonPersonID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                                $sqlBehaviourNegative = "SELECT * FROM gibbonBehaviour WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND type='Negative' AND date>'".date('Y-m-d', strtotime('-1 week'))."' AND date<='".date('Y-m-d')."'";
                                $resultBehaviourNegative = $connection2->prepare($sqlBehaviourNegative);
                                $resultBehaviourNegative->execute($dataBehaviourNegative);
                            } catch (PDOException $e) {
                            }
                            $behaviour .= '<ul>';
                            $behaviour .= '<li>'.__($guid, 'Positive behaviour records this week').': '.$resultBehaviourPositive->rowCount().'</li>';
                            $behaviour .= '<li>'.__($guid, 'Negative behaviour records this week').': '.$resultBehaviourNegative->rowCount().'</li>';
                            $behaviour .= '</ul><br/>';
                        }

                        //Get main form tutor email for reply-to
                        $replyTo = '';
                        $replyToName = '';
                        try {
                            $dataDetail = array('gibbonRollGroupID' => $row['gibbonRollGroupID']);
                            $sqlDetail = 'SELECT surname, preferredName, email FROM gibbonRollGroup LEFT JOIN gibbonPerson ON (gibbonRollGroup.gibbonPersonIDTutor=gibbonPerson.gibbonPersonID) WHERE gibbonRollGroupID=:gibbonRollGroupID';
                            $resultDetail = $connection2->prepare($sqlDetail);
                            $resultDetail->execute($dataDetail);
                        } catch (PDOException $e) {
                        }
                        if ($resultDetail->rowCount() == 1) {
                            $rowDetail = $resultDetail->fetch();
                            $replyTo = $rowDetail['email'];
                            $replyToName = $rowDetail['surname'].', '.$rowDetail['preferredName'];
                        }

                        //Get CP1 parent(s) email (might be multiples if in multiple families
                        try {
                            $dataFamily = array('gibbonPersonID' => $row['gibbonPersonID']);
                            $sqlFamily = 'SELECT * FROM gibbonFamily JOIN gibbonFamilyChild ON (gibbonFamily.gibbonFamilyID=gibbonFamilyChild.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID';
                            $resultFamily = $connection2->prepare($sqlFamily);
                            $resultFamily->execute($dataFamily);
                        } catch (PDOException $e) {
                        }

                        while ($rowFamily = $resultFamily->fetch()) { //Run through each CP! family member
                            try {
                                $dataMember = array('gibbonFamilyID' => $rowFamily['gibbonFamilyID']);
                                $sqlMember = 'SELECT * FROM gibbonFamilyAdult JOIN gibbonPerson ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID AND contactPriority=1 ORDER BY contactPriority, surname, preferredName';
                                $resultMember = $connection2->prepare($sqlMember);
                                $resultMember->execute($dataMember);
                            } catch (PDOException $e) {
                            }

                            while ($rowMember = $resultMember->fetch()) {
                                //Check for send this week, and only proceed if no prior send
                                $keyReadFail = false;
                                try {
                                    $dataKeyRead = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonIDStudent' => $row['gibbonPersonID'], 'gibbonPersonIDParent' => $rowMember['gibbonPersonID'], 'weekOfYear' => date('W'));
                                    $sqlKeyRead = 'SELECT * FROM gibbonPlannerParentWeeklyEmailSummary WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonIDStudent=:gibbonPersonIDStudent AND gibbonPersonIDParent=:gibbonPersonIDParent AND weekOfYear=:weekOfYear';
                                    $resultKeyRead = $connection2->prepare($sqlKeyRead);
                                    $resultKeyRead->execute($dataKeyRead);
                                } catch (PDOException $e) {
                                    $keyReadFail = true;
                                }

                                if ($keyReadFail == true) {
                                    ++$sendFailCount;
                                    error_log(sprintf(__($guid, 'Planner Wekly Summary Email: an error (%1$s) occured sending an email to %2$s.'), '1', $rowMember['preferredName'].' '.$rowMember['surname']));
                                } else {
                                    if ($resultKeyRead->rowCount() != 0) {
                                        ++$sendFailCount;
                                        error_log(sprintf(__($guid, 'Planner Wekly Summary Email: an error (%1$s) occured sending an email to %2$s.'), '2', $rowMember['preferredName'].' '.$rowMember['surname']));
                                    } else {
                                        //Make and store unique code for confirmation. add it to email text.
                                        $key = '';

                                        //Let's go! Create key, send the invite
                                        $continue = false;
                                        $count = 0;
                                        while ($continue == false and $count < 100) {
                                            $key = randomPassword(40);
                                            try {
                                                $dataUnique = array('key' => $key);
                                                $sqlUnique = 'SELECT * FROM gibbonPlannerParentWeeklyEmailSummary WHERE gibbonPlannerParentWeeklyEmailSummary.key=:key';
                                                $resultUnique = $connection2->prepare($sqlUnique);
                                                $resultUnique->execute($dataUnique);
                                            } catch (PDOException $e) {
                                            }

                                            if ($resultUnique->rowCount() == 0) {
                                                $continue = true;
                                            }
                                            ++$count;
                                        }

                                        if ($continue == false) {
                                            ++$sendFailCount;
                                            error_log(sprintf(__($guid, 'Planner Wekly Summary Email: an error (%1$s) occured sending an email to %2$s.'), '3', $rowMember['preferredName'].' '.$rowMember['surname']));
                                        } else {
                                            //Write key to database
                                            $keyWriteFail = false;
                                            try {
                                                $dataKeyWrite = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonIDStudent' => $row['gibbonPersonID'], 'gibbonPersonIDParent' => $rowMember['gibbonPersonID'], 'key' => $key, 'weekOfYear' => date('W'));
                                                $sqlKeyWrite = "INSERT INTO gibbonPlannerParentWeeklyEmailSummary SET gibbonSchoolYearID=:gibbonSchoolYearID, gibbonPlannerParentWeeklyEmailSummary.key=:key, gibbonPersonIDStudent=:gibbonPersonIDStudent, gibbonPersonIDParent=:gibbonPersonIDParent, weekOfYear=:weekOfYear, confirmed='N'";
                                                $resultKeyWrite = $connection2->prepare($sqlKeyWrite);
                                                $resultKeyWrite->execute($dataKeyWrite);
                                            } catch (PDOException $e) {
                                                $keyWriteFail = true;
                                            }

                                            if ($keyWriteFail == true) {
                                                ++$sendFailCount;
                                                error_log(sprintf(__($guid, 'Planner Wekly Summary Email: an error (%1$s) occured sending an email to %2$s.'), '4', $rowMember['preferredName'].' '.$rowMember['surname']));
                                            } else {
                                                //Prep email
                                                $body = sprintf(__($guid, 'Dear %1$s'), $rowMember['preferredName'].' '.$rowMember['surname']).',<br/><br/>';
                                                $body .= sprintf(__($guid, 'Please find below a summary of homework and behaviour for %1$s.'), $row['preferredName'].' '.$row['surname']).'<br/><br/>';
                                                $body .= $homework;
                                                $body .= $behaviour;
                                                $body .= sprintf(__($guid, 'Please %1$sclick here%2$s to confirm that you have received and read this summary email.'), "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/planner_parentWeeklyEmailSummaryConfirm.php&key=$key&gibbonPersonIDStudent=".$row['gibbonPersonID'].'&gibbonPersonIDParent='.$rowMember['gibbonPersonID'].'&gibbonSchoolYearID='.$_SESSION[$guid]['gibbonSchoolYearID']."'>", '</a>');
                                                $body .= "<p class='emphasis'>".sprintf(__($guid, 'Email sent via %1$s at %2$s.'), $_SESSION[$guid]['systemName'], $_SESSION[$guid]['organisationName']).'</p>';
                                                $bodyPlain = emailBodyConvert($body);

                                                $mail = getGibbonMailer($guid);
                                                if ($replyTo != '') {
                                                    $mail->AddReplyTo($replyTo, $replyToName);
                                                }
                                                $mail->AddAddress($rowMember['email'], $rowMember['surname'].', '.$rowMember['preferredName']);
                                                $mail->SetFrom($_SESSION[$guid]['organisationEmail'], $_SESSION[$guid]['organisationName']);
                                                $mail->CharSet = 'UTF-8';
                                                $mail->Encoding = 'base64';
                                                $mail->IsHTML(true);
                                                $mail->Subject = sprintf(__($guid, 'Weekly Planner Summary for %1$s via %2$s at %3$s'), $row['surname'].', '.$row['preferredName'].' ('.$row['name'].')', $_SESSION[$guid]['systemName'], $_SESSION[$guid]['organisationName']);
                                                $mail->Body = $body;
                                                $mail->AltBody = $bodyPlain;

                                                //Send email
                                                if ($mail->Send()) {
                                                    ++$sendSucceedCount;
                                                } else {
                                                    error_log(sprintf(__($guid, 'Planner Wekly Summary Email: an error (%1$s) occured sending an email to %2$s.'), '5', $rowMember['preferredName'].' '.$rowMember['surname']));
                                                    ++$sendFailCount;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            //Unlock module table
            try {
                $sql = 'UNLOCK TABLES';
                $result = $connection2->query($sql);
            } catch (PDOException $e) {
            }

            //Notify administrator
            $body = __($guid, 'Week').': '.date('W').'<br/>';
            $body .= __($guid, 'Student Count').': '.$studentCount.'<br/>';
            $body .= __($guid, 'Send Succeed Count').': '.$sendSucceedCount.'<br/>';
            $body .= __($guid, 'Send Fail Count').': '.$sendFailCount.'<br/><br/>';
            $notificationText = __($guid, 'A Planner CLI script has run.').'<br/>'.$body;
            setNotification($connection2, $guid, $_SESSION[$guid]['organisationAdministrator'], $notificationText, 'Planner', '/index.php?q=/modules/Planner/report_parentWeeklyEmailSummaryConfirmation.php');
        }
    }
//}
;
