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

use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\School\SchoolYearSpecialDayGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Timetable/spaceBooking_manage_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        //Proceed!
        $page->breadcrumbs
            ->add(__('Manage Facility Bookings'), 'spaceBooking_manage.php')
            ->add(__('Add Facility Booking'));

        $step = null;
        if (isset($_GET['step'])) {
            $step = $_GET['step'] ?? '';
        }
        if ($step != 1 and $step != 2) {
            $step = 1;
        }

        //Step 1
        if ($step == 1) {
            echo '<h2>';
            echo __('Step 1 - Choose Facility');
            echo '</h2>';

            $form = Form::create('spaceBookingStep1', $session->get('absoluteURL').'/index.php?q=/modules/'.$session->get('module').'/spaceBooking_manage_add.php&step=2');
            $form->setFactory(DatabaseFormFactory::create($pdo));

            $form->addHiddenValue('address', $session->get('address'));
            $form->addHiddenValue('source', isset($_REQUEST['source'])? $_REQUEST['source'] : '');

            $facilities = array();

            $foreignKeyID = isset($_GET['gibbonSpaceID'])? 'gibbonSpaceID-'.$_GET['gibbonSpaceID'] : '';
            $date = isset($_GET['date'])? Format::date($_GET['date']) : '';
            $timeStart = isset($_GET['timeStart'])? $_GET['timeStart'] : '';
            $timeEnd = isset($_GET['timeEnd'])? $_GET['timeEnd'] : '';

            // Collect facilities
            $sql = "SELECT CONCAT('gibbonSpaceID-', gibbonSpaceID) as value, name FROM gibbonSpace WHERE active='Y' ORDER BY name";
            $results = $pdo->executeQuery(array(), $sql);
            if ($results->rowCOunt() > 0) {
                $facilities['--'.__('Facilities').'--'] = $results->fetchAll(\PDO::FETCH_KEY_PAIR);
            }

            // Collect bookable library items
            $sql = "SELECT CONCAT('gibbonLibraryItemID-', gibbonLibraryItemID) as value, name FROM gibbonLibraryItem WHERE bookable='Y' ORDER BY name";
            $results = $pdo->executeQuery(array(), $sql);
            if ($results->rowCOunt() > 0) {
                $facilities['--'.__('Library').'--'] = $results->fetchAll(\PDO::FETCH_KEY_PAIR);
            }

            $row = $form->addRow();
                $row->addLabel('foreignKeyID', __('Facility'));
                $row->addSelect('foreignKeyID')->fromArray($facilities)->required()->placeholder()->selected($foreignKeyID);

            if ($highestAction == 'Manage Facility Bookings_allBookings') {
                $row = $form->addRow();
                    $row->addLabel('gibbonPersonID', __('Booked For'));
                    $row->addSelectStaff('gibbonPersonID')->selected($session->get('gibbonPersonID'))->photo(true, 'small')->required();
            } else {
                $form->addHiddenValue('gibbonPersonID', $session->get('gibbonPersonID'));
            }

            $row = $form->addRow();
                $row->addLabel('reason', __('Reason'));
                $row->addTextField('reason');

            $row = $form->addRow();
                $row->addLabel('date', __('Date'));
                $row->addDate('date')->required()->setValue($date);

            $row = $form->addRow();
                $row->addLabel('timeStart', __('Start Time'));
                $row->addTime('timeStart')->required()->setValue($timeStart);

            $row = $form->addRow();
                $row->addLabel('timeEnd', __('End Time'));
                $row->addTime('timeEnd')->required()->chainedTo('timeStart')->setValue($timeEnd);

            $repeatOptions = array('No' => __('No'), 'Daily' => __('Daily'), 'Weekly' => __('Weekly'));
            $row = $form->addRow();
                $row->addLabel('repeat', __('Repeat?'));
                $row->addRadio('repeat')->fromArray($repeatOptions)->inline()->checked('No');

            $form->toggleVisibilityByClass('repeatDaily')->onRadio('repeat')->when('Daily');
            $row = $form->addRow()->addClass('repeatDaily');
                $row->addLabel('repeatDaily', __('Repeat Daily'))->description(__('Repeat daily for this many days.').'<br/>'.__('Does not include non-school days.'));
                $row->addNumber('repeatDaily')->maxLength(2)->minimum(2)->maximum(20);

            $form->toggleVisibilityByClass('repeatWeekly')->onRadio('repeat')->when('Weekly');
            $row = $form->addRow()->addClass('repeatWeekly');
                $row->addLabel('repeatWeekly', __('Repeat Weekly'))->description(__('Repeat weekly for this many days.').'<br/>'.__('Does not include non-school days.'));
                $row->addNumber('repeatWeekly')->maxLength(2)->minimum(2)->maximum(20);

            $row = $form->addRow();
                $row->addSubmit(__('Proceed'));

            echo $form->getOutput();

        } elseif ($step == 2) {
            echo '<h2>';
            echo __('Step 2 - Availability Check');
            echo '</h2>';

            $foreignKey = null;
            $foreignKeyID = null;
            if (isset($_POST['foreignKeyID'])) {
                if (substr($_POST['foreignKeyID'], 0, 13) == 'gibbonSpaceID') { //It's a facility
                    $foreignKey = 'gibbonSpaceID';
                    $foreignKeyID = substr($_POST['foreignKeyID'], 14);
                } elseif (substr($_POST['foreignKeyID'], 0, 19) == 'gibbonLibraryItemID') { //It's a library item
                    $foreignKey = 'gibbonLibraryItemID';
                    $foreignKeyID = substr($_POST['foreignKeyID'], 20);
                }
            }
            $date = Format::dateConvert($_POST['date']);
            $timeStart = $_POST['timeStart'] ?? '';
            $timeEnd = $_POST['timeEnd'] ?? '';
            $reason = $_POST['reason'] ?? '';
            $repeat = $_POST['repeat'] ?? '';
            $gibbonPersonID = $_POST['gibbonPersonID'] ?? '';
            $repeatDaily = null;
            $repeatWeekly = null;
            if ($repeat == 'Daily') {
                $repeatDaily = $_POST['repeatDaily'] ?? '';
            } elseif ($repeat == 'Weekly') {
                $repeatWeekly = $_POST['repeatWeekly'] ?? '';
            }

            //Check for required fields
            if ($foreignKey == null or $foreignKeyID == null or $foreignKey == '' or $foreignKeyID == '' or $date == '' or $timeStart == '' or $timeEnd == '' or $repeat == '') {
                $page->addError(__('Your request failed because your inputs were invalid.'));
            } else {
                try {
                    if ($foreignKey == 'gibbonSpaceID') {
                        $dataSelect = array('gibbonSpace' => $foreignKeyID);
                        $sqlSelect = 'SELECT * FROM gibbonSpace WHERE gibbonSpaceID=:gibbonSpace';
                    } elseif ($foreignKey == 'gibbonLibraryItemID') {
                        $dataSelect = array('gibbonLibraryItemID' => $foreignKeyID);
                        $sqlSelect = 'SELECT * FROM gibbonLibraryItem WHERE gibbonLibraryItemID=:gibbonLibraryItemID';
                    }
                    $resultSelect = $connection2->prepare($sqlSelect);
                    $resultSelect->execute($dataSelect);
                } catch (PDOException $e) {
                    $page->addError(__('Your request failed due to a database error.'));
                }

                if ($resultSelect->rowCount() != 1) {
                    $page->addError(__('Your request failed due to a database error.'));
                } else {
                    $rowSelect = $resultSelect->fetch();

                    $specialDayGateway = $container->get(SchoolYearSpecialDayGateway::class);
                    $available = false;

                    $form = Form::create('spaceBookingStep1', $session->get('absoluteURL').'/modules/'.$session->get('module').'/spaceBooking_manage_addProcess.php');

                    $form->addHiddenValue('address', $session->get('address'));
                    $form->addHiddenValue('source', isset($_REQUEST['source'])? $_REQUEST['source'] : '');

                    $form->addHiddenValue('foreignKey', $foreignKey);
                    $form->addHiddenValue('foreignKeyID', $foreignKeyID);
                    $form->addHiddenValue('date', $date);
                    $form->addHiddenValue('timeStart', $timeStart);
                    $form->addHiddenValue('timeEnd', $timeEnd);
                    $form->addHiddenValue('reason', $reason);
                    $form->addHiddenValue('repeat', $repeat);
                    $form->addHiddenValue('repeatDaily', $repeatDaily);
                    $form->addHiddenValue('repeatWeekly', $repeatWeekly);
                    $form->addHiddenValue('gibbonPersonID', $gibbonPersonID);

                    if ($repeat == 'No') {
                        $gibbonCourseClassID = null;
                        $available = isSpaceFree($guid, $connection2, $foreignKey, $foreignKeyID, $date, $timeStart, $timeEnd, $gibbonCourseClassID);

                        if (!$available && !empty($gibbonCourseClassID)) {
                            $offTimetable = $specialDayGateway->getIsClassOffTimetableByDate($session->get('gibbonSchoolYearID'), $gibbonCourseClassID, $date);

                            if ($offTimetable) {
                                $available = true;
                            }
                        }

                        if ($available == true) {
                            $row = $form->addRow()->addClass('current');
                            $row->addLabel('dates[]', Format::date($date))->description(__('Available') . ($offTimetable? ' ('.__('Off Timetable').')' : ''));
                            $row->addCheckbox('dates[]')->setValue($date)->checked($date);
                        } else {
                            $row = $form->addRow()->addClass('error');
                            $row->addLabel('dates[]', Format::date($date))->description(__('Not Available'));
                            $row->addCheckbox('dates[]')->setValue($date)->disabled();
                        }
                    } elseif ($repeat == 'Daily' and $repeatDaily >= 2 and $repeatDaily <= 20) {
                        $continue = true;
                        $failCount = 0;
                        $successCount = 0;
                        $count = 0;
                        while ($continue) {
                            $dateTemp = date('Y-m-d', strtotime($date) + (86400 * $count));
                            if (isSchoolOpen($guid, $dateTemp, $connection2)) {
                                $available = true;
                                ++$successCount;
                                $failCount = 0;
                                if ($successCount >= $repeatDaily) {
                                    $continue = false;
                                }
                                //Print days
                                if (isSpaceFree($guid, $connection2, $foreignKey, $foreignKeyID, $dateTemp, $timeStart, $timeEnd) == true) {
                                    $row = $form->addRow()->addClass('current');
                                    $row->addLabel('dates[]', Format::date($dateTemp))->description(__('Available'));
                                    $row->addCheckbox('dates[]')->setValue($dateTemp)->checked($dateTemp);
                                } else {
                                    $row = $form->addRow()->addClass('error');
                                    $row->addLabel('dates[]', Format::date($dateTemp))->description(__('Not Available'));
                                    $row->addCheckbox('dates[]')->setValue($dateTemp)->disabled();
                                }
                            } else {
                                ++$failCount;
                                if ($failCount > 100) {
                                    $continue = false;
                                }
                            }
                            ++$count;
                        }
                    } elseif ($repeat == 'Weekly' and $repeatWeekly >= 2 and $repeatWeekly <= 20) {
                        $continue = true;
                        $failCount = 0;
                        $successCount = 0;
                        $count = 0;
                        while ($continue) {
                            $dateTemp = date('Y-m-d', strtotime($date) + (86400 * 7 * $count));
                            if (isSchoolOpen($guid, $dateTemp, $connection2)) {
                                $available = true;
                                ++$successCount;
                                $failCount = 0;
                                if ($successCount >= $repeatWeekly) {
                                    $continue = false;
                                }
                                //Print days
                                if (isSpaceFree($guid, $connection2, $foreignKey, $foreignKeyID, $dateTemp, $timeStart, $timeEnd) == true) {
                                    $row = $form->addRow()->addClass('current');
                                    $row->addLabel('dates[]', Format::date($dateTemp))->description(__('Available'));
                                    $row->addCheckbox('dates[]')->setValue($dateTemp)->checked($dateTemp);
                                } else {
                                    $row = $form->addRow()->addClass('error');
                                    $row->addLabel('dates[]', Format::date($dateTemp))->description(__('Not Available'));
                                    $row->addCheckbox('dates[]')->setValue($dateTemp)->disabled();
                                }
                            } else {
                                ++$failCount;
                                if ($failCount > 100) {
                                    $continue = false;
                                }
                            }
                            ++$count;
                        }
                    } else {
                        $row = $form->addRow();
                        $row->addAlert(__('Your request failed because your inputs were invalid.'), 'error');
                    }

                    if ($available == true) {
                        $row = $form->addRow();
                            $row->addSubmit();
                    } else {
                        $row = $form->addRow();
                            $row->addAlert(__('There are no sessions available, and so this form cannot be submitted.'), 'error');
                    }

                    echo $form->getOutput();
                }
            }
        }
    }
}
