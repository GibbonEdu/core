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

use Gibbon\Http\Url;
use Gibbon\Forms\Form;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\Activities\ActivityGateway;
use Gibbon\Domain\School\SchoolYearTermGateway;
use Gibbon\Services\Format;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Activities/activities_view_register.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        $page->breadcrumbs
            ->add(__('View Activities'), 'activities_view.php')
            ->add(__('Activity Registration'));

        if (isActionAccessible($guid, $connection2, '/modules/Activities/activities_view_register') == false) {
            //Acess denied
            $page->addError(__('You do not have access to this action.'));
        } else {

            $settingGateway = $container->get(SettingGateway::class);
            $activityGateway = $container->get(ActivityGateway::class);

            //Get current role category
            $roleCategory = $session->get('gibbonRoleIDCurrentCategory');

            //Check access controls
            $access = $settingGateway->getSettingByScope('Activities', 'access');

            $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
            $search = $_GET['search'] ?? '';

            if ($access != 'Register') {
                echo "<div class='error'>";
                echo __('Registration is closed, or you do not have permission to register.');
                echo '</div>';
            } else {
                //Check if gibbonActivityID specified
                $gibbonActivityID = $_GET['gibbonActivityID'] ?? '';
                if ($gibbonActivityID == 'Y') {
                    $page->addError(__('You have not specified one or more required parameters.'));
                } else {
                    $mode = $_GET['mode'] ?? '';

                    if ($_GET['search'] != '' or $gibbonPersonID != '') {
                        $params = [
                            "gibbonPersonID" => $gibbonPersonID,
                            "search" => $_GET['search'] ?? ''
                        ];
                        $page->navigator->addSearchResultsAction(Url::fromModuleRoute('Activities', 'activities_view.php')->withQueryParams($params));
                    }

                    //Check Access
                    $continue = false;
                    //Student
                    if ($roleCategory == 'Student' and $highestAction == 'View Activities_studentRegister') {

                            $dataStudent = array('gibbonPersonID' => $gibbonPersonID, 'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
                            $sqlStudent = 'SELECT * FROM gibbonStudentEnrolment WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID';
                            $resultStudent = $connection2->prepare($sqlStudent);
                            $resultStudent->execute($dataStudent);
                        if ($resultStudent->rowCount() == 1) {
                            $rowStudent = $resultStudent->fetch();
                            $gibbonYearGroupID = intval($rowStudent['gibbonYearGroupID']);
                            if ($gibbonYearGroupID != '') {
                                $continue = true;
                                $and = " AND gibbonYearGroupIDList LIKE '%$gibbonYearGroupID%'";
                            }
                        }
                    }
                    //Parent
                    else if ($roleCategory == 'Parent' and $highestAction == 'View Activities_studentRegisterByParent' and $gibbonPersonID != '') {

                            $data = array('gibbonPersonID' => $session->get('gibbonPersonID'));
                            $sql = "SELECT * FROM gibbonFamilyAdult WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y'";
                            $result = $connection2->prepare($sql);
                            $result->execute($data);

                        if ($result->rowCount() < 1) {
                            echo $page->getBlankSlate();
                        } else {
                            $countChild = 0;
                            while ($values = $result->fetch()) {

                                    $dataChild = array('gibbonFamilyID' => $values['gibbonFamilyID'], 'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $gibbonPersonID);
                                    $sqlChild = "SELECT * FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID) WHERE gibbonFamilyID=:gibbonFamilyID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.gibbonPersonID=:gibbonPersonID ORDER BY surname, preferredName ";
                                    $resultChild = $connection2->prepare($sqlChild);
                                    $resultChild->execute($dataChild);
                                while ($rowChild = $resultChild->fetch()) {
                                    ++$countChild;
                                    $gibbonYearGroupID = intval($rowChild['gibbonYearGroupID']);
                                }
                            }

                            if ($countChild > 0) {
                                if ($gibbonYearGroupID != '') {
                                    $continue = true;
                                    $and = " AND gibbonYearGroupIDList LIKE '%$gibbonYearGroupID%'";
                                }
                            }
                        }
                    }

                    if ($mode == 'register') {
                        if ($continue == false) {
                            $page->addError(__('Your request failed due to a database error.'));
                        } else {
                            $today = date('Y-m-d');

                            //Should we show date as term or date?
                            $dateType = $settingGateway->getSettingByScope('Activities', 'dateType');
                            if ($dateType == 'Term') {
                                $maxPerTerm = $settingGateway->getSettingByScope('Activities', 'maxPerTerm');
                            }

                            try {
                                if ($dateType != 'Date') {
                                    $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonActivityID' => $gibbonActivityID);
                                    $sql = "SELECT gibbonActivity.*, gibbonActivityType.access, gibbonActivityType.maxPerStudent, gibbonActivityType.waitingList, gibbonActivityType.enrolmentType, gibbonActivityType.backupChoice FROM gibbonActivity LEFT JOIN gibbonActivityType ON (gibbonActivity.type=gibbonActivityType.name) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' AND NOT gibbonSchoolYearTermIDList='' AND gibbonActivityID=:gibbonActivityID AND registration='Y' $and";
                                } else {
                                    $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonActivityID' => $gibbonActivityID, 'listingStart' => $today, 'listingEnd' => $today);
                                    $sql = "SELECT gibbonActivity.*, gibbonActivityType.access, gibbonActivityType.maxPerStudent, gibbonActivityType.waitingList, gibbonActivityType.enrolmentType, gibbonActivityType.backupChoice FROM gibbonActivity LEFT JOIN gibbonActivityType ON (gibbonActivity.type=gibbonActivityType.name) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' AND listingStart<=:listingStart AND listingEnd>=:listingEnd AND gibbonActivityID=:gibbonActivityID AND registration='Y' $and";
                                }
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                            }

                            if ($result->rowCount() != 1) {
                                $page->addError(__('The selected record does not exist, or you do not have access to it.'));
                            } else {
                                $values = $result->fetch();

                                //Check for existing registration

                                    $dataReg = array('gibbonActivityID' => $gibbonActivityID, 'gibbonPersonID' => $session->get('gibbonPersonID'));
                                    $sqlReg = 'SELECT * FROM gibbonActivityStudent WHERE gibbonActivityID=:gibbonActivityID AND gibbonPersonID=:gibbonPersonID';
                                    $resultReg = $connection2->prepare($sqlReg);
                                    $resultReg->execute($dataReg);

                                if (!empty($values['access']) && $values['access'] != 'Register') {
                                    echo "<div class='error'>";
                                    echo __('Registration is closed, or you do not have permission to register.');
                                    echo '</div>';
                                } else if ($resultReg->rowCount() > 0) {
                                    echo "<div class='error'>";
                                    echo __('You are already registered for this activity and so cannot register again.');
                                    echo '</div>';
                                } else {
                                    $page->return->addReturns(['error3' => __('Registration failed because you are already registered in this activity.')]);

                                    //Check registration limit...
                                    $proceed = true;
                                    if ($dateType == 'Term' and $maxPerTerm > 0) {
                                        $termsList = explode(',', $values['gibbonSchoolYearTermIDList']);
                                        foreach ($termsList as $term) {

                                                $dataActivityCount = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $gibbonPersonID, 'gibbonSchoolYearTermIDList' => '%'.$term.'%');
                                                $sqlActivityCount = "SELECT * FROM gibbonActivityStudent JOIN gibbonActivity ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearTermIDList LIKE :gibbonSchoolYearTermIDList AND NOT status='Not Accepted'";
                                                $resultActivityCount = $connection2->prepare($sqlActivityCount);
                                                $resultActivityCount->execute($dataActivityCount);
                                            if ($resultActivityCount->rowCount() >= $maxPerTerm) {
                                                $proceed = false;
                                            }
                                        }
                                    }

                                    $overlapCheck = $activityGateway->getOverlappingActivityTimeSlot($gibbonActivityID, $gibbonPersonID, $dateType)->fetchKeyPair();

                                    if (!empty($overlapCheck)) {
                                        echo Format::alert(__('The timing of this activity conflicts with one or more currently enrolled activities:').' '.Format::bold(implode(',', $overlapCheck)), 'warning');
                                    }

                                    $activityCountByType = $activityGateway->getStudentActivityCountByType($values['type'], $gibbonPersonID);
                                    if ($values['maxPerStudent'] > 0 && $activityCountByType >= $values['maxPerStudent']) {
                                        echo "<div class='error'>";
                                        echo __('You have subscribed for the maximum number of activities of this type, and so cannot register for this activity.');
                                        echo '</div>';
                                    } elseif ($proceed == false) {
                                        echo "<div class='error'>";
                                        echo __('You have subscribed for the maximum number of activities in a term, and so cannot register for this activity.');
                                        echo '</div>';
                                    } else {
                                        // Load the enrolmentType system setting, optionally override with the Activity Type setting
                                        $enrolment = $settingGateway->getSettingByScope('Activities', 'enrolmentType');
                                        $enrolment = !empty($values['enrolmentType'])? $values['enrolmentType'] : $enrolment;

                                        echo '<p>';
                                        if ($enrolment == 'Selection') {
                                            echo __('After you press the Register button below, your application will be considered by a member of staff who will decide whether or not there is space for you in this program.');
                                        } else if ($values['waitingList'] == 'Y') {
                                            echo __('If there is space on this program you will be accepted immediately upon pressing the Register button below. If there is not, then you will be placed on a waiting list.');
                                        }
                                        echo '</p>';

                                        $form = Form::create('courseEdit', $session->get('absoluteURL').'/modules/'.$session->get('module').'/activities_view_registerProcess.php?search='.$search);

                                        $form->addHiddenValue('address', $session->get('address'));
                                        $form->addHiddenValue('mode', $mode);
                                        $form->addHiddenValue('gibbonPersonID', $gibbonPersonID);
                                        $form->addHiddenValue('gibbonActivityID', $gibbonActivityID);

                                        $row = $form->addRow();
                                            $row->addLabel('nameLabel', __('Activity'));
                                            $row->addTextField('name')->readonly();

                                        if ($dateType != 'Date') {
                                            /**
                                             * @var SchoolYearTermGateway
                                             */
                                            $schoolYearTermGateway = $container->get(SchoolYearTermGateway::class);
                                            $termList = $schoolYearTermGateway->getTermNamesByID($values['gibbonSchoolYearTermIDList']);

                                            $row = $form->addRow();
                                                $row->addLabel('terms', __('Terms'));
                                                $row->addTextField('terms')->readonly()->setValue(!empty($termList) ? implode(', ', $termList) : '-');
                                        } else {
                                            $row = $form->addRow();
                                                $row->addLabel('programStartLabel', __('Program Start Date'));
                                                $row->addDate('programStart')->readonly();

                                            $row = $form->addRow();
                                                $row->addLabel('programEndLabel', __('Program End Date'));
                                                $row->addDate('programEnd')->readonly();
                                        }

                                        $paymentType = $settingGateway->getSettingByScope('Activities', 'payment');
                                        if ($paymentType != 'None' && $paymentType != 'Single') {
                                            if ($values['payment'] > 0) {
                                                $row = $form->addRow();
                                                $row->addLabel('paymentLabel', __('Cost'))->description(__('For entire programme'));
                                                $row->addCurrency('payment')->readonly();
                                            }
                                        }

                                        // Load the backupChoice system setting, optionally override with the Activity Type setting
                                        $backupChoice = $settingGateway->getSettingByScope('Activities', 'backupChoice');
                                        $backupChoice = !empty($values['backupChoice'])? $values['backupChoice'] : $backupChoice;

                                        if ($backupChoice == 'Y') {
                                            if ($dateType != 'Date') {
                                                $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $gibbonPersonID, 'gibbonActivityID' => $gibbonActivityID);
                                                $sql = "SELECT DISTINCT gibbonActivity.gibbonActivityID as value, gibbonActivity.name FROM gibbonActivity JOIN gibbonStudentEnrolment ON (gibbonActivity.gibbonYearGroupIDList LIKE concat( '%', gibbonStudentEnrolment.gibbonYearGroupID, '%' )) WHERE gibbonActivity.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND NOT gibbonActivityID=:gibbonActivityID AND NOT gibbonSchoolYearTermIDList='' AND active='Y' $and ORDER BY name";
                                            } else {
                                                $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $gibbonPersonID, 'gibbonActivityID' => $gibbonActivityID, 'listingStart' => $today, 'listingEnd' => $today);
                                                $sql = "SELECT DISTINCT gibbonActivity.gibbonActivityID as value, gibbonActivity.name FROM gibbonActivity JOIN gibbonStudentEnrolment ON (gibbonActivity.gibbonYearGroupIDList LIKE concat( '%', gibbonStudentEnrolment.gibbonYearGroupID, '%' )) WHERE gibbonActivity.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND NOT gibbonActivityID=:gibbonActivityID AND listingStart<=:listingStart AND listingEnd>=:listingEnd AND active='Y' $and ORDER BY name";
                                            }
                                            $result = $pdo->executeQuery($data, $sql);

                                            $row = $form->addRow();
                                                $row->addLabel('gibbonActivityIDBackup', __('Backup Choice'))
                                                    ->description(sprintf(__('In case %1$s is full.'), $values['name']));
                                                $row->addSelect('gibbonActivityIDBackup')
                                                    ->fromResults($result)
                                                    ->required($result->rowCount() > 0)
                                                    ->placeholder();
                                        }

                                        $row = $form->addRow();
                                            $row->addSubmit(__('Register'));

                                        $form->loadAllValuesFrom($values);

                                        echo $form->getOutput();
                                    }
                                }
                            }
                        }
                    } elseif ($mode = 'unregister') {
                        if ($continue == false) {
                            $page->addError(__('Your request failed due to a database error.'));
                        } else {
                            $today = date('Y-m-d');

                            //Should we show date as term or date?
                            $dateType = $settingGateway->getSettingByScope('Activities', 'dateType');

                            try {
                                if ($dateType != 'Date') {
                                    $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $gibbonPersonID, 'gibbonActivityID' => $gibbonActivityID);
                                    $sql = "SELECT DISTINCT gibbonActivity.*, gibbonActivityType.access FROM gibbonActivity JOIN gibbonStudentEnrolment ON (gibbonActivity.gibbonYearGroupIDList LIKE concat( '%', gibbonStudentEnrolment.gibbonYearGroupID, '%' )) LEFT JOIN gibbonActivityType ON (gibbonActivity.type=gibbonActivityType.name) WHERE gibbonActivity.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND gibbonActivityID=:gibbonActivityID AND NOT gibbonSchoolYearTermIDList='' AND active='Y' $and";
                                } else {
                                    $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $gibbonPersonID, 'gibbonActivityID' => $gibbonActivityID, 'listingStart' => $today, 'listingEnd' => $today);
                                    $sql = "SELECT DISTINCT gibbonActivity.*, gibbonActivityType.access FROM gibbonActivity JOIN gibbonStudentEnrolment ON (gibbonActivity.gibbonYearGroupIDList LIKE concat( '%', gibbonStudentEnrolment.gibbonYearGroupID, '%' )) LEFT JOIN gibbonActivityType ON (gibbonActivity.type=gibbonActivityType.name) WHERE gibbonActivity.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND gibbonActivityID=:gibbonActivityID AND listingStart<=:listingStart AND listingEnd>=:listingEnd AND active='Y' $and";
                                }
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                            }

                            if ($result->rowCount() != 1) {
                                $page->addError(__('The selected record does not exist, or you do not have access to it.'));
                            } else {
                                $values = $result->fetch();

                                //Check for existing registration

                                    $dataReg = array('gibbonActivityID' => $gibbonActivityID, 'gibbonPersonID' => $gibbonPersonID);
                                    $sqlReg = 'SELECT * FROM gibbonActivityStudent WHERE gibbonActivityID=:gibbonActivityID AND gibbonPersonID=:gibbonPersonID';
                                    $resultReg = $connection2->prepare($sqlReg);
                                    $resultReg->execute($dataReg);

                                if (!empty($values['access']) && $values['access'] != 'Register') {
                                    echo "<div class='error'>";
                                    echo __('Registration is closed, or you do not have permission to register.');
                                    echo '</div>';
                                } elseif ($resultReg->rowCount() < 1) {
                                    echo "<div class='error'>";
                                    echo __('You are not currently registered for this activity and so cannot unregister.');
                                    echo '</div>';
                                } else {
                                    $form = Form::create('courseEdit', $session->get('absoluteURL').'/modules/'.$session->get('module').'/activities_view_registerProcess.php?search='.$search);
                                    $form->removeClass('smallIntBorder');

                                    $form->addHiddenValue('address', $session->get('address'));
                                    $form->addHiddenValue('mode', $mode);
                                    $form->addHiddenValue('gibbonPersonID', $gibbonPersonID);
                                    $form->addHiddenValue('gibbonActivityID', $gibbonActivityID);

                                    $form->addRow()->addContent(sprintf(__('Are you sure you want to unregister from activity "%1$s"? If you try to reregister later you may lose a space already assigned to you.'), $values['name']))->wrap('<strong>', '</strong>');

                                    $row = $form->addRow();
                                        $row->addSubmit(__('Unregister'));

                                    echo $form->getOutput();
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
?>
