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

use Gibbon\View\View;
use Gibbon\Services\Format;
use Gibbon\Contracts\Comms\Mailer;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\System\DiscussionGateway;
use Gibbon\Module\FreeLearning\Domain\UnitStudentGateway;

require_once '../../gibbon.php';

$settingGateway = $container->get(SettingGateway::class);
$publicUnits = $settingGateway->getSettingByScope('Free Learning', 'publicUnits');

$highestAction = getHighestGroupedAction($guid, $session->get('address'), $connection2);

//Get params
$freeLearningUnitID = $_REQUEST['freeLearningUnitID'] ?? '';

$canManage = isActionAccessible($guid, $connection2, '/modules/Free Learning/units_manage.php') and $highestAction == 'Browse Units_all';

$showInactive = $canManage and isset($_GET['showInactive']) ? $_GET['showInactive'] : $showInactive;
$gibbonDepartmentID = $_REQUEST['gibbonDepartmentID'] ?? '';
$difficulty = $_GET['difficulty'] ?? '';
$name = $_GET['name'] ?? '';
$view = $_GET['view'] ?? '';

if ($view != 'grid' and $view != 'map') {
    $view = 'list';
}
$gibbonPersonID = $session->get('gibbonPersonID');

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Free Learning/units_browse_details.php&freeLearningUnitID='.$freeLearningUnitID.'&gibbonDepartmentID='.$gibbonDepartmentID.'&difficulty='.$difficulty.'&name='.$name.'&showInactive='.$showInactive.'&sidebar=true&tab=1&view='.$view;

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/units_browse_details.php') == false) {
    // Fail 0
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    if (empty($_POST)) {
        // Fail 6
        $URL .= '&return=error6';
        header("Location: {$URL}");
    } else {
        $roleCategory = getRoleCategory($session->get('gibbonRoleIDCurrent'), $connection2);
        if ($highestAction == false || empty($roleCategory)) {
            // Fail 0
            $URL .= '&return=error0';
            header("Location: {$URL}");
        } else {
            $freeLearningUnitID = $_POST['freeLearningUnitID'] ?? '';
            $freeLearningUnitStudentID = $_POST['freeLearningUnitStudentID'] ?? '';

            if ($freeLearningUnitID == '' or $freeLearningUnitStudentID == '') {
                //Fail 3
                $URL .= '&return=error3';
                header("Location: {$URL}");
            } else {
                try {
                    $data = array('freeLearningUnitID' => $freeLearningUnitID, 'freeLearningUnitStudentID' => $freeLearningUnitStudentID);
                    $sql = "SELECT freeLearningUnit.*, freeLearningUnitStudent.*, surname, preferredName, email
                        FROM freeLearningUnit
                            JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID)
                            JOIN gibbonPerson ON (freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID)
                        WHERE freeLearningUnitStudent.freeLearningUnitID=:freeLearningUnitID
                            AND freeLearningUnitStudentID=:freeLearningUnitStudentID
                            AND (freeLearningUnitStudent.status='Current' OR freeLearningUnitStudent.status='Evidence Not Yet Approved')";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    //Fail 2
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                if ($result->rowCount() != 1) {
                    //Fail 2
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                } else {
                    //Proceed!
                    $row = $result->fetch();
                    $name = $row['name'];
                    $confirmationKey = $row['confirmationKey'];
                    $studentName = Format::name('', $row['preferredName'], $row['surname'], 'Student', false, true);
                    $studentEmail = $row['email'];
                    $enrolmentMethod = $row['enrolmentMethod'];
                    $gibbonPersonIDSchoolMentor = $row['gibbonPersonIDSchoolMentor'];
                    $emailExternalMentor = $row['emailExternalMentor'];
                    $nameExternalMentor = $row['nameExternalMentor'];

                    //Get Inputs
                    $status = 'Complete - Pending';
                    $commentStudent = $_POST['commentStudent'] ?? '';
                    $commentStudent = nl2br($commentStudent);
                    $type = $_POST['type'] ?? '';
                    $link = (!empty($_POST['link'])) ? trim($_POST['link']) : null;
                    $gibbonCourseClassID = $row['gibbonCourseClassID'];

                    //Validation
                    if ($commentStudent == '' || $type == '' || ($type == 'File' && empty($_FILES['file']['name'])) || ($type == 'Link' && $link == '')) {
                        //Fail 3
                        $URL .= '&return=error3';
                        header("Location: {$URL}");
                    } else {
                        $partialFail = false;
                        if ($type == 'Link') {
                            if (substr($link, 0, 7) != 'http://' and substr($link, 0, 8) != 'https://') {
                                $partialFail = true;
                            } else {
                                $location = $link;
                            }
                        }
                        if ($type == 'File') {
                            //Check extension to see if allow
                            try {
                                @$extension = end(explode('.', $_FILES['file']['name']));
                                $dataExt = array('extension' => $extension);
                                $sqlExt = 'SELECT * FROM gibbonFileExtension WHERE extension=:extension';
                                $resultExt = $connection2->prepare($sqlExt);
                                $resultExt->execute($dataExt);
                            } catch (PDOException $e) {
                                $partialFail = true;
                            }

                            if ($resultExt->rowCount() != 1) {
                                $partialFail = true;
                            } else {
                                //Attempt file upload
                                $partialFail = false;

                                //Move attached image  file, if there is one
                                if (!empty($_FILES['file']['tmp_name'])) {
                                    $fileUploader = new Gibbon\FileUploader($pdo, $session);

                                    $file = $_FILES['file'] ?? null;

                                    // Upload the file, return the /uploads relative path
                                    $location = $fileUploader->uploadFromPost($file, $session->get('username'));

                                    if (empty($location)) {
                                        $partialFail = true;
                                    }
                                }
                                else {
                                    $partialFail = true;
                                }
                            }
                        }

                        //Deal with partial fail
                        if ($partialFail == true) {
                            //Fail 6
                            $URL .= '&return=error6';
                            header("Location: {$URL}");
                        } else {
                            // Write to database
                            $unitStudentGateway = $container->get(UnitStudentGateway::class);
                            $collaborativeAssessment = $settingGateway->getSettingByScope('Free Learning', 'collaborativeAssessment');

                            $data = [
                                'status' => $status,
                                'commentStudent' => $commentStudent,
                                'evidenceType' => $type,
                                'evidenceLocation' => $location,
                                'timestampCompletePending' => date('Y-m-d H:i:s')
                            ];
                            if ($collaborativeAssessment == 'Y' AND !empty($row['collaborationKey'])) {
                                $updated = $unitStudentGateway->updateWhere(['collaborationKey' => $row['collaborationKey']], $data);
                            } else {
                                $updated = $unitStudentGateway->update($freeLearningUnitStudentID, $data);
                            }

                            // Insert discussion records
                            $discussionGateway = $container->get(DiscussionGateway::class);

                            $data = [
                                'foreignTable'         => 'freeLearningUnitStudent',
                                'foreignTableID'       => $freeLearningUnitStudentID,
                                'gibbonModuleID'       => getModuleIDFromName($connection2, 'Free Learning'),
                                'gibbonPersonID'       => $session->get('gibbonPersonID'),
                                'gibbonPersonIDTarget' => $session->get('gibbonPersonID'),
                                'comment'              => $commentStudent,
                                'type'                 => 'Complete - Pending',
                                'tag'                  => 'pending',
                                'attachmentType'       => $type,
                                'attachmentLocation'   => $location,
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

							$notificationGateway = new \Gibbon\Domain\System\NotificationGateway($pdo);
							$notificationSender = new \Gibbon\Comms\NotificationSender($notificationGateway, $session);

                            if ($enrolmentMethod == 'class') { //Attempt to notify teacher(s) of class
                                $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                                $sql = "SELECT gibbonPersonID FROM gibbonCourseClassPerson WHERE gibbonCourseClassID=:gibbonCourseClassID AND (role='Teacher' OR role='Assistant') AND reportable='Y'";
                                $result = $pdo->select($sql, $data);

                                $text = __m('{student} has requested unit completion approval and feedback ({unit}).', ['student' => $studentName, 'unit' => $name]);
                                $actionLink = "/index.php?q=/modules/Free Learning/units_browse_details_approval.php&freeLearningUnitStudentID=$freeLearningUnitStudentID&freeLearningUnitID=$freeLearningUnitID&sidebar=true";
                                while ($row = $result->fetch()) {
                                	$notificationSender->addNotification($row['gibbonPersonID'], $text, 'Free Learning', $actionLink);
                                }
                                $notificationSender->sendNotifications();
                            }
                            else if ($enrolmentMethod == 'schoolMentor' && $gibbonPersonIDSchoolMentor != '') { //Attempt to notify school mentor
                                $text = __m('{student} has requested unit completion approval and feedback ({unit}).', ['student' => $studentName, 'unit' => $name]);
                                $actionLink = "/index.php?q=/modules/Free Learning/units_mentor_approval.php&freeLearningUnitStudentID=$freeLearningUnitStudentID&confirmationKey=$confirmationKey";
                                $notificationSender->addNotification($gibbonPersonIDSchoolMentor, $text, 'Free Learning', $actionLink);
                                $notificationSender->sendNotifications();
                            }
                            elseif ($enrolmentMethod == 'externalMentor' && $emailExternalMentor != '') {
                                // Attempt to notify external mentors
                                $subject = sprintf(__m('Request For Mentor Feedback via %1$s at %2$s'), $session->get('systemName'), $session->get('organisationNameShort'));
                                $buttonURL = "/index.php?q=/modules/Free Learning/units_mentor_approval.php&freeLearningUnitStudentID=$freeLearningUnitStudentID&confirmationKey=$confirmationKey";

                                $body = $container->get(View::class)->fetchFromTemplate('mentorSubmit.twig.html', [
                                    'roleCategoryFull' => $roleCategory == 'Staff' ? __m('member of staff') : __(strtolower($roleCategory)),
                                    'unitName' => $name,
                                    'studentName' => $studentName,
                                    'organisationNameShort' => $session->get('organisationNameShort'),
                                    'organisationAdministratorName' => $session->get('organisationAdministratorName'),
                                    'organisationAdministratorEmail' => $session->get('organisationAdministratorEmail'),
                                ]);

                                // Attempt email send
                                $mail = $container->get(Mailer::class);
                                $mail->AddReplyTo($studentEmail, $studentName);
                                $mail->AddAddress($emailExternalMentor, $nameExternalMentor);
                                $mail->setDefaultSender($subject);
                                $mail->renderBody('mail/message.twig.html', [
                                    'title'  => __m('Request For Mentor Feedback'),
                                    'body'   => $body,
                                    'button' => [
                                        'url'  => $buttonURL,
                                        'text' => __('View Evidence'),
                                    ],
                                ]);

                                $sent = $mail->Send();
                                $partialFail &= !$sent;
                            } else {
                                $partialFail = true;
                            }

                            //Success 0
                            if ($partialFail == true) {
                                $URL .= '&return=warning1';
                                header("Location: {$URL}");
                            } else {
                                $URL .= "&return=success0";
                                header("Location: {$URL}");
                            }
                        }
                    }
                }
            }
        }
    }
}
