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

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Timetable/spaceBooking_manage_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __($guid, 'The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        //Proceed!
        echo "<div class='trail'>";
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/spaceBooking_manage.php'>".__($guid, 'Manage Facility Bookings')."</a> > </div><div class='trailEnd'>".__($guid, 'Add Facility Booking').'</div>';
        echo '</div>';

        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, null);
        }

        $step = null;
        if (isset($_GET['step'])) {
            $step = $_GET['step'];
        }
        if ($step != 1 and $step != 2) {
            $step = 1;
        }

        //Step 1
        if ($step == 1) {
            echo '<h2>';
            echo __($guid, 'Step 1 - Choose Facility');
            echo '</h2>';

            $form = Form::create('spaceBookingStep1', $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/spaceBooking_manage_add.php&step=2');
            $form->setFactory(DatabaseFormFactory::create($pdo));

            $form->addHiddenValue('address', $_SESSION[$guid]['address']);

            $facilities = array();

            // Collect facilities
            $sql = "SELECT CONCAT('gibbonSpaceID-', gibbonSpaceID) as value, name FROM gibbonSpace ORDER BY name";
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
                $row->addSelect('foreignKeyID')->fromArray($facilities)->isRequired()->placeholder();

            $row = $form->addRow();
                $row->addLabel('date', __('Date'))->description($_SESSION[$guid]['i18n']['dateFormat'])->prepend(__('Format:'));
                $row->addDate('date')->isRequired();

            $row = $form->addRow();
                $row->addLabel('timeStart', __('Start Time'));
                $row->addTime('timeStart')->isRequired();

            $row = $form->addRow();
                $row->addLabel('timeEnd', __('End Time'));
                $row->addTime('timeEnd')->isRequired()->chainedTo('timeStart');

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
            echo __($guid, 'Step 2 - Availability Check');
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
            $date = dateConvert($guid, $_POST['date']);
            $timeStart = $_POST['timeStart'];
            $timeEnd = $_POST['timeEnd'];
            $repeat = $_POST['repeat'];
            $repeatDaily = null;
            $repeatWeekly = null;
            if ($repeat == 'Daily') {
                $repeatDaily = $_POST['repeatDaily'];
            } elseif ($repeat == 'Weekly') {
                $repeatWeekly = $_POST['repeatWeekly'];
            }

            //Check for required fields
            if ($foreignKey == null or $foreignKeyID == null or $foreignKey == '' or $foreignKeyID == '' or $date == '' or $timeStart == '' or $timeEnd == '' or $repeat == '') {
                echo "<div class='error'>";
                echo __($guid, 'Your request failed because your inputs were invalid.');
                echo '</div>';
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
                    echo "<div class='error'>";
                    echo __($guid, 'Your request failed due to a database error.');
                    echo '</div>';
                }

                if ($resultSelect->rowCount() != 1) {
                    echo "<div class='error'>";
                    echo __($guid, 'Your request failed due to a database error.');
                    echo '</div>';
                } else {
                    $rowSelect = $resultSelect->fetch();

                    $available = false;

                    $form = Form::create('spaceBookingStep1', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/spaceBooking_manage_addProcess.php');

                    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

                    $form->addHiddenValue('foreignKey', $foreignKey);
                    $form->addHiddenValue('foreignKeyID', $foreignKeyID);
                    $form->addHiddenValue('date', $date);
                    $form->addHiddenValue('timeStart', $timeStart);
                    $form->addHiddenValue('timeEnd', $timeEnd);
                    $form->addHiddenValue('repeat', $repeat);
                    $form->addHiddenValue('repeatDaily', $repeatDaily);
                    $form->addHiddenValue('repeatWeekly', $repeatWeekly);

                    if ($repeat == 'No') {
                        $available = isSpaceFree($guid, $connection2, $foreignKey, $foreignKeyID, $date, $timeStart, $timeEnd);
                        if ($available == true) {
                            $row = $form->addRow()->addClass('current');
                            $row->addLabel('dates[]', dateConvertBack($guid, $date))->description(__('Available'));
                            $row->addCheckbox('dates[]')->setValue($date)->checked($date);
                        } else {
                            $row = $form->addRow()->addClass('error');
                            $row->addLabel('dates[]', dateConvertBack($guid, $date))->description(__('Not Available'));
                            $row->addCheckbox('dates[]')->setValue($date)->isDisabled();
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
                                    $row->addLabel('dates[]', dateConvertBack($guid, $dateTemp))->description(__('Available'));
                                    $row->addCheckbox('dates[]')->setValue($dateTemp)->checked($dateTemp);
                                } else {
                                    $row = $form->addRow()->addClass('error');
                                    $row->addLabel('dates[]', dateConvertBack($guid, $dateTemp))->description(__('Not Available'));
                                    $row->addCheckbox('dates[]')->setValue($dateTemp)->isDisabled();
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
                                    $row->addLabel('dates[]', dateConvertBack($guid, $dateTemp))->description(__('Available'));
                                    $row->addCheckbox('dates[]')->setValue($dateTemp)->checked($dateTemp);
                                } else {
                                    $row = $form->addRow()->addClass('error');
                                    $row->addLabel('dates[]', dateConvertBack($guid, $dateTemp))->description(__('Not Available'));
                                    $row->addCheckbox('dates[]')->setValue($dateTemp)->isDisabled();
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
