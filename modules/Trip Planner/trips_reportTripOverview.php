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

use Gibbon\View\View;
use Gibbon\Services\Format;
use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Tables\Prefab\ReportTable;
use Gibbon\Domain\User\FamilyGateway;
use Gibbon\Domain\Students\MedicalGateway;
use Gibbon\Domain\Students\StudentReportGateway;

//Module includes
include './modules/Trip Planner/moduleFunctions.php';

if (!isActionAccessible($guid, $connection2, '/modules/Trip Planner/trips_manage.php')) {
    //Acess denied
    echo "<div class='error'>";
    echo __m('You do not have access to this action.');
    echo '</div>';
} else {
    $highestAction = getHighestGroupedAction($guid, '/modules/Trip Planner/trips_manage.php', $connection2);
    if ($highestAction != false) {
        if (isset($_GET["tripPlannerRequestID"])) {
            $tripPlannerRequestID = $_GET["tripPlannerRequestID"];

            $request = getTrip($connection2, $tripPlannerRequestID);

            if ($request == null) {
                echo "<div class='error'>";
                echo __('The specified record does not exist.');
                echo '</div>';
            }
            else {
                $gibbonPersonID = $_SESSION[$guid]["gibbonPersonID"];
                $departments = getHOD($connection2, $gibbonPersonID);
                $departments2 = getDepartments($connection2, getOwner($connection2, $tripPlannerRequestID));
                $isHOD = false;

                foreach ($departments as $department) {
                    if (in_array($department["gibbonDepartmentID"], $departments2)) {
                        $isHOD = true;
                        break;
                    }
                }

                if (isApprover($connection2, $gibbonPersonID) || isOwner($connection2, $tripPlannerRequestID, $gibbonPersonID) || isInvolved($connection2, $tripPlannerRequestID, $gibbonPersonID) || $isHOD || $highestAction == "Manage Trips_full") {
                    $viewMode = $_REQUEST['format'] ?? '';

                    $students = array();
                    $peopleInTrip = getPeopleInTrip($connection2, array($tripPlannerRequestID), "Student");
                    while ($people = $peopleInTrip->fetch()) {
                        $students[] = $people['gibbonPersonID'];
                    }
                    $gibbonSchoolYearID = $gibbon->session->get('gibbonSchoolYearID');

                    if (empty($students)) {
                        return;
                    }

                    $cutoffDate = getSettingByScope($connection2, 'Data Updater', 'cutoffDate');
                    if (empty($cutoffDate)) $cutoffDate = Format::dateFromTimestamp(time() - (604800 * 26));

                    //EVENT DATA
                    //Prep dates
                    $dates = '';
                    if (!empty($request["multiDay"])) {
                        $days = explode(", ", $request["multiDay"]);
                        asort($days);
                        foreach ($days as $day) {
                            $day = explode(";", $day);
                            $days[] = $day;
                            $dates .= DateTime::createFromFormat("Y-m-d", $day[0])->format("d/m/Y");
                            if ($day[0] != $day[1]) {
                                $dates .= " - ".DateTime::createFromFormat("Y-m-d", $day[1])->format("d/m/Y");
                            }
                            if ($day[2]) {
                                $dates .= " (" . __("All Day") . ")<br/>";
                            } else {
                                $dates .= " (" . DateTime::createFromFormat("H:i:s", $day[3])->format("H:i") . " - ";
                                $dates .= DateTime::createFromFormat("H:i:s", $day[4])->format("H:i") . ")<br/>";
                            }
                        }
                    } else {
                        $endDate = $request["endDate"] == null ? $request["date"] : $request["endDate"];

                        $dates .= DateTime::createFromFormat("Y-m-d", $request["date"])->format("d/m/Y");
                        if ($request["date"] != $endDate) {
                            $dates .= " - ".DateTime::createFromFormat("Y-m-d", $endDate)->format("d/m/Y");
                        }
                        if ($request["startTime"] == null || $request["endTime"] == null) {
                            $dates .= " (" . __("All Day") . ")";
                        } else {
                            $dates .= " (" . DateTime::createFromFormat("H:i:s", $request["startTime"])->format("H:i") . " - ";
                            $dates .= DateTime::createFromFormat("H:i:s", $request["startTime"])->format("H:i") . ")<br/>";
                        }
                        $days[] = array($request["date"], $endDate, $request["startTime"], $request["endTime"]);
                    }

                    //Prep lead teacher
                    $lead = '';
                    $leadResult = getNameFromID($connection2, $request['creatorPersonID']);
                    if (is_array($leadResult)) {
                        $lead =  Format::name('', $leadResult['preferredName'], $leadResult['surname'], 'Student');
                        if (!empty($leadResult['phone1'])) {
                            $lead .= " (".$leadResult['phone1'].")";
                        }
                    }

                    echo "<h2>".__('Trip Overview')."</h2>";
                    echo $page->fetchFromTemplate('event.twig.html', [
                        'event' => $request['title'],
                        'dates' => $dates,
                        'location' => $request['location'],
                        'lead' => $lead,
                    ]);

                    //Gateways
                    $reportGateway = $container->get(StudentReportGateway::class);
                    $familyGateway = $container->get(FamilyGateway::class);
                    $medicalGateway = $container->get(MedicalGateway::class);

                    //Emergency query
                    $criteria = $reportGateway->newQueryCriteria(true)
                        ->sortBy(['gibbonPerson.surname', 'gibbonPerson.preferredName'])
                        ->pageSize(!empty($viewMode) ? 0 : 50)
                        ->fromPOST();
                    $students = $reportGateway->queryStudentDetails($criteria, $students);

                    //Medial criteria
                    $criteria = $reportGateway->newQueryCriteria(true)
                        ->sortBy(['gibbonPerson.surname', 'gibbonPerson.preferredName'])
                        ->pageSize(!empty($viewMode) ? 0 : 50)
                        ->fromPOST();

                   // Join a set of medical records per student
                   $people = $students->getColumn('gibbonPersonID');
                   $medical = $medicalGateway->queryMedicalFormsBySchoolYear($criteria, $gibbonSchoolYearID)->toArray(); //->fetchGrouped()
                   $students->joinColumn('gibbonPersonID', 'medical', $medical);

                   // Join a set of medical conditions per student
                   $medicalIDs = $students->getColumn('gibbonPersonMedicalID');
                   $medicalConditions = $medicalGateway->selectMedicalConditionsByID($medicalIDs)->fetchGrouped();
                   $students->joinColumn('gibbonPersonMedicalID', 'medicalConditions', $medicalConditions);

                   // Join a set of family adults per student
                   $people = $students->getColumn('gibbonPersonID');
                   $familyAdults = $familyGateway->selectFamilyAdultsByStudent($people, true)->fetchGrouped();
                   $students->joinColumn('gibbonPersonID', 'familyAdults', $familyAdults);

                    // DATA TABLE
                    $table = ReportTable::createPaginated('studentEmergencySummary', $criteria)->setViewMode($viewMode, $gibbon->session);
                    $table->setTitle(__('Participants'));

                    $table->addMetaData('post', ['gibbonPersonID' => $students]);

                    $table->addColumn('student', __('Student'))
                        ->width('12%')
                        ->description(__('Last Personal Update'))
                        ->sortable(['gibbonPerson.surname', 'gibbonPerson.preferredName'])
                        ->format(function ($student) use ($cutoffDate) {
                            $output = Format::name('', $student['preferredName'], $student['surname'], 'Student', true, true).'<br/><br/>';

                            $output .= ($student['lastPersonalUpdate'] < $cutoffDate) ? '<span style="color: #ff0000; font-weight: bold"><i>' : '<span><i>';
                            $output .= !empty($student['lastPersonalUpdate']) ? Format::date($student['lastPersonalUpdate']) : __('N/A');
                            $output .= '</i></span>';

                            return $output;
                        });

                    $view = new View($container->get('twig'));
                    $table->addColumn('contacts', __('Parents'))
                        ->width('15%')
                        ->notSortable()
                        ->format(function ($student) use ($view) {
                            return $view->fetchFromTemplate(
                                'formats/familyContacts.twig.html',
                                ['familyAdults' => $student['familyAdults']]
                            );
                        });

                    $table->addColumn('emergency1', __('Emergency Contact 1'))
                        ->width('15%')
                        ->sortable('emergency1Name')
                        ->format(function ($student) use ($view) {
                            return $view->fetchFromTemplate(
                                'formats/emergencyContact.twig.html',
                                [
                                    'name'         => $student['emergency1Name'],
                                    'number1'      => $student['emergency1Number1'],
                                    'number2'      => $student['emergency1Number2'],
                                    'relationship' => $student['emergency1Relationship'],
                                ]
                            );
                        });

                    $table->addColumn('emergency2', __('Emergency Contact 2'))
                        ->width('15%')
                        ->sortable('emergency2Name')
                        ->format(function ($student) use ($view) {
                            return $view->fetchFromTemplate(
                                'formats/emergencyContact.twig.html',
                                [
                                    'name'         => $student['emergency2Name'],
                                    'number1'      => $student['emergency2Number1'],
                                    'number2'      => $student['emergency2Number2'],
                                    'relationship' => $student['emergency2Relationship'],
                                ]
                            );
                        });

                    $view = new View($container->get('twig'));

                    $table->addColumn('medicalForm', __('Medical Form?'))
                        ->width('16%')
                        ->sortable('gibbonPersonMedicalID')
                        ->format(function ($student) use ($view) {
                            return $view->fetchFromTemplate('formats/medicalForm.twig.html', $student);
                        });

                    $table->addColumn('conditions', __('Medical Conditions'))
                        ->width('60%')
                        ->notSortable()
                        ->format(function ($student) use ($view) {
                            return $view->fetchFromTemplate('formats/medicalConditions.twig.html', $student);
                        });

                    echo $table->render($students);

                } else {
                    print "<div class='error'>";
                        print "You do not have access to this action.";
                    print "</div>";
                }
            }
        } else {
            print "<div class='error'>";
                print "No request selected.";
            print "</div>";
        }
    }
}
