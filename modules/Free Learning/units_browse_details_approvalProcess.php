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
along with this program. If not, see <http:// www.gnu.org/licenses/>.
*/

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\System\DiscussionGateway;
use Gibbon\Domain\Markbook\MarkbookEntryGateway;
use Gibbon\Module\FreeLearning\Domain\UnitStudentGateway;

require_once '../../gibbon.php';

require_once  './moduleFunctions.php';

$settingGateway = $container->get(SettingGateway::class);
$publicUnits = $settingGateway->getSettingByScope('Free Learning', 'publicUnits');

$highestAction = getHighestGroupedAction($guid, '/modules/Free Learning/units_browse_details_approval.php', $connection2);

// Get params
$freeLearningUnitID = $_GET['freeLearningUnitID'] ?? '';
$canManage = false;
if (isActionAccessible($guid, $connection2, '/modules/Free Learning/units_manage.php') and $highestAction == 'Browse Units_all') {
    $canManage = true;
}
$showInactive = ($canManage and isset($_GET['showInactive'])) ? $_GET['showInactive'] : 'N';
$gibbonDepartmentID = $_REQUEST['gibbonDepartmentID'] ?? '';
$difficulty = $_GET['difficulty'] ?? '';
$name = $_GET['name'] ?? '';
$view = $_GET['view'] ?? '';
if ($view != 'grid' and $view != 'map') {
    $view = 'list';
}
$gibbonPersonID = '';
if ($canManage and isset($_GET['gibbonPersonID'])) {
    $gibbonPersonID = $_GET['gibbonPersonID'];
}

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Free Learning/units_browse_details.php&freeLearningUnitID='.$_POST['freeLearningUnitID'].'&freeLearningUnitStudentID='.$_POST['freeLearningUnitStudentID'].'&gibbonDepartmentID='.$gibbonDepartmentID.'&difficulty='.$difficulty.'&name='.$name.'&showInactive='.$showInactive.'&gibbonPersonID='.$gibbonPersonID.'&sidebar=true&tab=2&view='.$view;

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/units_browse_details_approval.php') == false) {
    // Fail 0
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    if ($highestAction == false) {
        // Fail 0
        $URL .= '&return=error0';
        header("Location: {$URL}");
    } else {
        $roleCategory = getRoleCategory($session->get('gibbonRoleIDCurrent'), $connection2);

        $freeLearningUnitID = $_POST['freeLearningUnitID'] ?? '';
        $freeLearningUnitStudentID = $_POST['freeLearningUnitStudentID'] ?? '';

        if ($freeLearningUnitID == '' or $freeLearningUnitStudentID == '') {
            // Fail 3
            $URL .= '&return=error3';
            header("Location: {$URL}");
        } else {
            try {
                $data = array('freeLearningUnitID' => $freeLearningUnitID, 'freeLearningUnitStudentID' => $freeLearningUnitStudentID);
                $sql = "SELECT * FROM freeLearningUnit JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID) WHERE freeLearningUnitStudent.freeLearningUnitID=:freeLearningUnitID AND freeLearningUnitStudentID=:freeLearningUnitStudentID AND (status='Complete - Pending' OR status='Complete - Approved' OR status='Evidence Not Yet Approved')";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                // Fail 2
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            if ($result->rowCount() != 1) {
                // Fail 2
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            } else {
                // Proceed!
                $row = $result->fetch();
                $name = $row['name'];
                $statusOriginal = $row['status'];
                $commentApprovalOriginal = trim($row['commentApproval']);

                $proceed = false;
                // Check to see if we have access to manage all enrolments, or only those belonging to ourselves
                $manageAll = isActionAccessible($guid, $connection2, '/modules/Free Learning/enrolment_manage.php', 'Manage Enrolment_all');
                if ($manageAll == true) {
                    $proceed = true;
                } else {
                    // Check to see if we can set enrolmentType to "staffEdit" if user has rights in relevant department(s)
                    $learningAreas = getLearningAreas($connection2, $guid, true);
                    if ($learningAreas != '') {
                        for ($i = 0; $i < count($learningAreas); $i = $i + 2) {
                            if (is_numeric(strpos($row['gibbonDepartmentIDList'], $learningAreas[$i]))) {
                                $proceed = true;
                            }
                        }
                    }
                }

                // Check to see if class is in one teacher teachers
                if ($row['enrolmentMethod'] == 'class') { // Is teacher of this class?
                    try {
                        $dataClasses = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $session->get('gibbonPersonID'), 'gibbonCourseClassID' => $row['gibbonCourseClassID']);
                        $sqlClasses = "SELECT gibbonCourseClass.gibbonCourseClassID FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID AND (role='Teacher' OR role='Assistant')";
                        $resultClasses = $connection2->prepare($sqlClasses);
                        $resultClasses->execute($dataClasses);
                    } catch (PDOException $e) {}
                    if ($resultClasses->rowCount() > 0) {
                        $proceed = true;
                    }
                }

                // Check to see if we are a mentor of this student
                if ($row['enrolmentMethod'] == 'schoolMentor') { // Is teacher of this class?
                    try {
                        $dataMentor = array('gibbonPersonID' => $session->get('gibbonPersonID'), 'freeLearningUnitStudentID' => $freeLearningUnitStudentID);
                        $sqlMentor = "SELECT freeLearningUnitStudentID FROM freeLearningUnitStudent WHERE freeLearningUnitStudentID=:freeLearningUnitStudentID AND enrolmentMethod='schoolMentor' AND gibbonPersonIDSchoolMentor=:gibbonPersonID";
                        $resultMentor = $connection2->prepare($sqlMentor);
                        $resultMentor->execute($dataMentor);
                    } catch (PDOException $e) {}
                    if ($resultMentor->rowCount() > 0) {
                        $proceed = true;
                    }
                }

                if ($proceed == false) {
                    // Fail 0
                    $URL .= '&return=error0';
                    header("Location: {$URL}");
                } else {
                    // Get Inputs
                    $status = $_POST['status'] ?? '';

                    // Get the comment and strip the wrapping paragraph tags
                    $commentApproval = $_POST['commentApproval'] ?? '';
                    $commentApproval = trim(preg_replace('/^<p>|<\/p>$/i', '', $commentApproval));

                    $gibbonPersonIDStudent = $row['gibbonPersonIDStudent'];
                    $disableExemplarWork = $settingGateway->getSettingByScope('Free Learning', 'disableExemplarWork');
                    $exemplarWork = (!empty($_POST['exemplarWork']) && $disableExemplarWork != 'Y') ? $_POST['exemplarWork'] : 'N';
                    $exemplarWorkLicense = (!empty($_POST['exemplarWorkLicense']) && $disableExemplarWork != 'Y') ? $_POST['exemplarWorkLicense'] : '';
                    $exemplarWorkEmbed = (!empty($_POST['exemplarWorkEmbed']) && $disableExemplarWork != 'Y') ? $_POST['exemplarWorkEmbed'] : '';
                    $attachment = '';
                    $badgesBadgeID = $_POST['badgesBadgeID'] ?? '';

                    // Validation
                    if ($commentApproval == '' or $exemplarWork == '') {
                        // Fail 3
                        $URL .= '&return=error3';
                        header("Location: {$URL}");
                    } else {
                        $partialFail = false;

                        // Insert discussion records
                        if ($statusOriginal != $status or $commentApprovalOriginal != $commentApproval) {
                            $collaborativeAssessment = $settingGateway->getSettingByScope('Free Learning', 'collaborativeAssessment');
                            $discussionGateway = $container->get(DiscussionGateway::class);
                            $unitStudentGateway = $container->get(UnitStudentGateway::class);

                            $data = [
                                'foreignTable'         => 'freeLearningUnitStudent',
                                'foreignTableID'       => $freeLearningUnitStudentID,
                                'gibbonModuleID'       => getModuleIDFromName($connection2, 'Free Learning'),
                                'gibbonPersonID'       => $session->get('gibbonPersonID'),
                                'gibbonPersonIDTarget' => $gibbonPersonIDStudent,
                                'comment'              => $commentApproval,
                                'type'                 => $status,
                                'tag'                  => $status == 'Complete - Approved' ? 'success' : 'warning',
                            ];

                            if ($collaborativeAssessment == 'Y' AND !empty($row['collaborationKey'])) {
                                $collaborators = $unitStudentGateway->selectBy(['collaborationKey' => $row['collaborationKey']])->fetchAll();
                                foreach ($collaborators as $collaborator) {
                                    $data['foreignTableID'] = $collaborator['freeLearningUnitStudentID'];
                                    $data['gibbonPersonIDTarget'] = $collaborator['gibbonPersonIDStudent'];
                                    $discussionGateway->insert($data);
                                }
                            } else {
                                $discussionGateway->insert($data);
                            }
                        }

                        // Attempt to assemble list of students for notification and badges
                        $gibbonPersonIDStudents = [$gibbonPersonIDStudent];
                        if ($collaborativeAssessment == 'Y' AND  !empty($row['collaborationKey'])) {
                            $dataNotification = array('freeLearningUnitID' => $freeLearningUnitID, 'freeLearningUnitStudentID' => $freeLearningUnitStudentID, 'collaborationKey' => $row['collaborationKey']);
                            $sqlNotification = "SELECT gibbonPersonIDStudent FROM freeLearningUnit JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID) WHERE freeLearningUnitStudent.freeLearningUnitID=:freeLearningUnitID AND NOT freeLearningUnitStudentID=:freeLearningUnitStudentID AND (status='Complete - Pending' OR status='Complete - Approved' OR status='Evidence Not Yet Approved') AND collaborationKey=:collaborationKey";
                            $resultNotification = $pdo->select($sqlNotification, $dataNotification)->fetchAll();

                            foreach ($resultNotification as $rowNotification) {
                                $gibbonPersonIDStudents[] = $rowNotification['gibbonPersonIDStudent'];
                            }
                        }

                        $notificationGateway = new \Gibbon\Domain\System\NotificationGateway($pdo);
                        $notificationSender = new \Gibbon\Comms\NotificationSender($notificationGateway, $session);

                        if ($status == 'Complete - Approved') { // APPROVED!
                            // Move attached file, if there is one
                            if ($exemplarWork == 'Y') {
                                $attachment = $row['exemplarWorkThumb'];
                                $time = time();

                                // Move attached image  file, if there is one
                                if (!empty($_FILES['file']['tmp_name'])) {
                                    $fileUploader = new Gibbon\FileUploader($pdo, $session);
                                    $fileUploader->getFileExtensions('Graphics/Design');

                                    $file = $_FILES['file'] ?? null;

                                    // Upload the file, return the /uploads relative path
                                    $attachment = $fileUploader->uploadFromPost($file, $name);

                                    if (empty($attachment)) {
                                        $partialFail = true;
                                    }
                                }
                            }

                            // Write to database
                            $unitStudentGateway = $container->get(UnitStudentGateway::class);

                            $data = [
                                'status' => $status,
                                'exemplarWork' => $exemplarWork,
                                'exemplarWorkThumb' => $attachment,
                                'exemplarWorkLicense' => $exemplarWorkLicense,
                                'exemplarWorkEmbed' => $exemplarWorkEmbed,
                                'commentApproval' => $commentApproval,
                                'gibbonPersonIDApproval' => $session->get('gibbonPersonID'),
                                'timestampCompleteApproved' => date('Y-m-d H:i:s')
                            ];

                            if ($collaborativeAssessment == 'Y' AND !empty($row['collaborationKey'])) {
                                $updated = $unitStudentGateway->updateWhere(['collaborationKey' => $row['collaborationKey']], $data);
                            } else {
                                $updated = $unitStudentGateway->update($freeLearningUnitStudentID, $data);
                            }

                            // Attempt to notify the student and grant badges
                            if ($statusOriginal != $status or $commentApprovalOriginal != $commentApproval) { // Only if status or comment has changed.
                                $text = sprintf(__m('A teacher has approved your request for unit completion (%1$s).'), $name);
                                $actionLink = "/index.php?q=/modules/Free Learning/units_browse_details.php&freeLearningUnitID=$freeLearningUnitID&gibbonDepartmentID=&difficulty=&name=&showInactive=&sidebar=true&tab=1";
                                foreach ($gibbonPersonIDStudents AS $gibbonPersonIDStudent) {
                                    $notificationSender->addNotification($gibbonPersonIDStudent, $text, 'Free Learning', $actionLink);
                                    if (isActionAccessible($guid, $connection2, '/modules/Badges/badges_grant.php')) {
                                        grantBadges($connection2, $guid, $gibbonPersonIDStudent, $settingGateway);
                                    }
                                }
                                $notificationSender->sendNotifications();
                            }

                            // Copy to Markbook
                            $copyToMarkbook = $_POST['copyToMarkbook'] ?? 'N';
                            $gibbonMarkbookColumnID = $_POST['gibbonMarkbookColumnID'] ?? null;

                            if ($copyToMarkbook == "Y" && !empty($gibbonMarkbookColumnID) && !empty($gibbonPersonIDStudent)) {
                                $markbookEntryGateway = $container->get(MarkbookEntryGateway::class);

                                foreach ($gibbonPersonIDStudents AS $gibbonPersonIDStudent) {
                                    $gibbonMarkbookEntry = $markbookEntryGateway->selectBy(['gibbonMarkbookColumnID' => $gibbonMarkbookColumnID, 'gibbonPersonIDStudent' => $gibbonPersonIDStudent]);

                                    if ($gibbonMarkbookEntry->rowCount() == 1) { // Update existing row
                                        $gibbonMarkbookEntryID = $gibbonMarkbookEntry->fetch()['gibbonMarkbookEntryID'];
                                        $markbookEntryGateway->update($gibbonMarkbookEntryID, ['comment' => html_entity_decode(strip_tags($commentApproval))]);
                                    } else { //Insert new row, overwriting comment
                                        $markbookEntryGateway->insert(['gibbonMarkbookColumnID' => $gibbonMarkbookColumnID, 'gibbonPersonIDStudent' => $gibbonPersonIDStudent, 'comment' => html_entity_decode(strip_tags($commentApproval)), 'gibbonPersonIDLastEdit' => $session->get('gibbonPersonID')]);
                                    }
                                }
                            }

                            // Deal with manually granted badges
                            $enableManualBadges = $settingGateway->getSettingByScope('Free Learning', 'enableManualBadges');
                            if ($enableManualBadges == 'Y' && isModuleAccessible($guid, $connection2, '/modules/Badges/badges_grant.php') && !is_null($badgesBadgeID)) {
                                foreach ($gibbonPersonIDStudents AS $gibbonPersonIDStudent) {
                                    $data = array('badgesBadgeID' => $badgesBadgeID, 'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'date' => date('Y-m-d'), 'gibbonPersonID' => $gibbonPersonIDStudent, 'comment' => '', 'gibbonPersonIDCreator' => $session->get('gibbonPersonID',''));
                                    $sql = 'INSERT INTO badgesBadgeStudent SET badgesBadgeID=:badgesBadgeID, gibbonSchoolYearID=:gibbonSchoolYearID, date=:date, gibbonPersonID=:gibbonPersonID, comment=:comment, gibbonPersonIDCreator=:gibbonPersonIDCreator';
                                    $result = $connection2->prepare($sql);
                                    $result->execute($data);
                                }
                            }

                            if ($partialFail == true) {
                                $URL .= '&return=warning1';
                                header("Location: {$URL}");
                            } else {
                                $URL .= "&return=success0";
                                header("Location: {$URL}");
                            }
                        } elseif ($status == 'Evidence Not Yet Approved') { // NOT YET APPROVED
                            // Write to database
                            $collaborativeAssessment = $settingGateway->getSettingByScope('Free Learning', 'collaborativeAssessment');
                            try {
                                $data = array('status' => $status, 'exemplarWork' => $exemplarWork, 'exemplarWorkThumb' => '', 'exemplarWorkLicense' => '', 'commentApproval' => $commentApproval, 'commentApproval' => $commentApproval, 'gibbonPersonIDApproval' => $session->get('gibbonPersonID'), 'timestampCompleteApproved' => date('Y-m-d H:i:s'));
                                if ($collaborativeAssessment == 'Y' AND  !empty($row['collaborationKey'])) {
                                    $data['collaborationKey'] = $row['collaborationKey'];
                                    $sql = 'UPDATE freeLearningUnitStudent SET exemplarWork=:exemplarWork, exemplarWorkThumb=:exemplarWorkThumb, exemplarWorkLicense=:exemplarWorkLicense, status=:status, commentApproval=:commentApproval, gibbonPersonIDApproval=:gibbonPersonIDApproval, timestampCompleteApproved=:timestampCompleteApproved WHERE collaborationKey=:collaborationKey';
                                }
                                else {
                                    $data['freeLearningUnitStudentID'] = $freeLearningUnitStudentID;
                                    $sql = 'UPDATE freeLearningUnitStudent SET exemplarWork=:exemplarWork, exemplarWorkThumb=:exemplarWorkThumb, exemplarWorkLicense=:exemplarWorkLicense, status=:status, commentApproval=:commentApproval, gibbonPersonIDApproval=:gibbonPersonIDApproval, timestampCompleteApproved=:timestampCompleteApproved WHERE freeLearningUnitStudentID=:freeLearningUnitStudentID';

                                }
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                // Fail 2
                                $URL .= '&return=error2';
                                header("Location: {$URL}");
                                exit;
                            }

                            // Attempt to notify the student
                            if ($statusOriginal != $status or $commentApprovalOriginal != $commentApproval) { // Only if status or comment has changed.
                            	$text = sprintf(__('A teacher has responded to your request for unit completion, but your evidence has not been approved (%1$s).', 'Free Learning'), $name);
                                $actionLink = "/index.php?q=/modules/Free Learning/units_browse_details.php&freeLearningUnitID=$freeLearningUnitID&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&showInactive=$showInactive&gibbonPersonID=$gibbonPersonID&sidebar=true&tab=1&view=$view";
								foreach ($gibbonPersonIDStudents AS $gibbonPersonIDStudent) {
									$notificationSender->addNotification($gibbonPersonIDStudent, $text, 'Free Learning', $actionLink);
								}
								$notificationSender->sendNotifications();
                            }

                            // Success 0
                            $URL .= '&return=success0';
                            header("Location: {$URL}");
                        } else {
                            // Fail 3
                            $URL .= '&return=error3';
                            header("Location: {$URL}");
                        }
                    }
                }
            }
        }
    }
}
