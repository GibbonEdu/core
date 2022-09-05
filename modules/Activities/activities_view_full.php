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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Services\Format;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Activities/activities_view_full.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('Your request failed because you do not have access to this action.');
    echo '</div>';
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __('The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        //Check access controls
        $settingGateway = $container->get(SettingGateway::class);
        $access = $settingGateway->getSettingByScope('Activities', 'access');
        $hideExternalProviderCost = $settingGateway->getSettingByScope('Activities', 'hideExternalProviderCost');

        if (!($access == 'View' or $access == 'Register')) {
            echo "<div class='error'>";
            echo __('Activity listing is currently closed.');
            echo '</div>';
        } else {
            //Should we show date as term or date?
            $dateType = $settingGateway->getSettingByScope('Activities', 'dateType');

            //Proceed!
            //Get class variable
            $gibbonActivityID = $_GET['gibbonActivityID'];
            if ($gibbonActivityID == '') {
                echo "<div class='warning'>";
                echo __('Your request failed because your inputs were invalid.');
                echo '</div>';
            }
            //Check existence of and access to this class.
            else {
                $today = date('Y-m-d');

                try {
                    if ($dateType != 'Date') {
                        $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonActivityID' => $gibbonActivityID);
                        $sql = "SELECT gibbonActivity.*, gibbonActivityType.description as activityTypeDescription FROM gibbonActivity LEFT JOIN gibbonActivityType ON (gibbonActivity.type=gibbonActivityType.name) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' AND NOT gibbonSchoolYearTermIDList='' AND gibbonActivityID=:gibbonActivityID";
                    } else {
                        $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonActivityID' => $gibbonActivityID, 'listingStart' => $today, 'listingEnd' => $today);
                        $sql = "SELECT gibbonActivity.*, gibbonActivityType.description as activityTypeDescription FROM gibbonActivity LEFT JOIN gibbonActivityType ON (gibbonActivity.type=gibbonActivityType.name) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' AND listingStart<=:listingStart AND listingEnd>=:listingEnd AND gibbonActivityID=:gibbonActivityID";
                    }
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                if ($result->rowCount() != 1) {
                    echo "<div class='warning'>";
                    echo __('The selected record does not exist, or you do not have access to it.');
                    echo '</div>';
                } else {
                    $row = $result->fetch();
                    //Should we show date as term or date?
                    echo '<h1>';
                    echo $row['name'].'<br/>';
                    if (!empty($row['type'])) {
                        echo "<div style='padding-top: 5px; font-size: 65%; font-style: italic'>";
                        echo trim($row['type']);
                        echo '</div>';
                    }
                    echo '</h1>';

                    echo "<table class='blank' cellspacing='0' style='width: 550px; float: left;'>";
                    echo '<tr>';
                    if ($dateType != 'Date') {
                        echo "<td style='width: 33%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Terms').'</span><br/>';
                        $terms = getTerms($connection2, $session->get('gibbonSchoolYearID'));
                        $termList = '';
                        for ($i = 0; $i < count($terms); $i = $i + 2) {
                            if (is_numeric(strpos($row['gibbonSchoolYearTermIDList'], $terms[$i]))) {
                                $termList .= $terms[($i + 1)].', ';
                            }
                        }
                        if ($termList == '') {
                            echo '<i>'.__('NA').'</i>';
                        } else {
                            echo substr($termList, 0, -2);
                        }
                        echo '</td>';
                    } else {
                        echo "<td style='width: 33%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Start Date').'</span><br/>';
                        echo Format::date($row['programStart']);
                        echo '</td>';
                        echo "<td style='width: 33%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('End Date').'</span><br/>';
                        echo Format::date($row['programEnd']);
                        echo '</td>';
                    }
                    echo "<td style='width: 33%; vertical-align: top'>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".__('Year Groups').'</span><br/>';
                    echo getYearGroupsFromIDList($guid, $connection2, $row['gibbonYearGroupIDList']);
                    echo '</td>';
                    echo '</tr>';
                    echo '<tr>';
                    echo "<td style='padding-top: 15px; width: 33%; vertical-align: top'>";
                    if ($hideExternalProviderCost == 'Y' and $row['provider'] == 'External') {
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Cost').'</span><br/>';
                        echo '<i>'.__('See Description below.').'</i>';
                    } else {
                        if ($row['paymentFirmness'] == 'Finalised') {
                            echo "<span style='font-size: 115%; font-weight: bold'>".sprintf(__('Cost (%1$s)'), __($row['paymentType'])).'</span><br/>';
                        }
                        else {
                            echo "<span style='font-size: 115%; font-weight: bold'>".sprintf(__('%1$s Cost (%2$s)'), __($row['paymentFirmness']), __($row['paymentType'])).'</span><br/>';
                        }
                        if ($row['payment'] == 0) {
                            echo '<i>'.__('None').'</i>';
                        } else {
                            if (substr($session->get('currency'), 4) != '') {
                                echo substr($session->get('currency'), 4);
                            }
                            echo $row['payment'];
                        }
                    }
                    echo '</td>';
                    echo "<td style='padding-top: 15px; width: 33%; vertical-align: top'>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".__('Maximum Participants').'</span><br/>';
                    echo $row['maxParticipants'];
                    echo '</td>';
                    echo "<td style='padding-top: 15px; width: 33%; vertical-align: top'>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".__('Staff').'</span><br/>';

                        $dataStaff = array('gibbonActivityID' => $row['gibbonActivityID']);
                        $sqlStaff = "SELECT title, preferredName, surname, role FROM gibbonActivityStaff JOIN gibbonPerson ON (gibbonActivityStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonActivityID=:gibbonActivityID AND gibbonPerson.status='Full' ORDER BY surname, preferredName";
                        $resultStaff = $connection2->prepare($sqlStaff);
                        $resultStaff->execute($dataStaff);

                    if ($resultStaff->rowCount() < 1) {
                        echo '<i>'.__('None').'</i>';
                    } else {
                        echo "<ul style='margin-left: 15px'>";
                        while ($rowStaff = $resultStaff->fetch()) {
                            echo '<li>'.Format::name($rowStaff['title'], $rowStaff['preferredName'], $rowStaff['surname'], 'Staff').'</li>';
                        }
                        echo '</ul>';
                    }
                    echo '</td>';
                    echo '</tr>';
                    echo '<tr>';
                    echo "<td style='padding-top: 15px; width: 33%; vertical-align: top' colspan=3>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".__('Provider').'</span><br/>';
                    echo '<i>';
                    if ($row['provider'] == 'School') {
                        echo $session->get('organisationNameShort');
                    } else {
                        echo __('External');
                    };
                    echo '</i>';
                    echo '</td>';
                    echo '</tr>';
                    if (!empty($row['activityTypeDescription'])) {
                        echo '<tr>';
                        echo "<td style='text-align: justify; padding-top: 15px; width: 33%; vertical-align: top' colspan=3>";
                        echo '<h2>'.$row['type'].'</h2>';
                        echo $row['activityTypeDescription'];
                        echo '</td>';
                        echo '</tr>';
                    }
                    if ($row['description'] != '') {
                        echo '<tr>';
                        echo "<td style='text-align: justify; padding-top: 15px; width: 33%; vertical-align: top' colspan=3>";
                        echo '<h2>'.__('Description').'</h2>';
                        echo $row['description'];
                        echo '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';

                    //Slots & Participants
                    echo "<div style='width:400px; float: right; font-size: 115%; padding-top: 6px'>";
                    echo "<h3 style='padding-top: 0px; margin-top: 5px'>".__('Time Slots').'</h3>';

                        $dataSlots = array('gibbonActivityID' => $row['gibbonActivityID']);
                        $sqlSlots = 'SELECT gibbonActivitySlot.*, gibbonDaysOfWeek.name AS day, gibbonSpace.name AS space FROM gibbonActivitySlot JOIN gibbonDaysOfWeek ON (gibbonActivitySlot.gibbonDaysOfWeekID=gibbonDaysOfWeek.gibbonDaysOfWeekID) LEFT JOIN gibbonSpace ON (gibbonActivitySlot.gibbonSpaceID=gibbonSpace.gibbonSpaceID) WHERE gibbonActivityID=:gibbonActivityID ORDER BY sequenceNumber';
                        $resultSlots = $connection2->prepare($sqlSlots);
                        $resultSlots->execute($dataSlots);

                    $count = 0;
                    while ($rowSlots = $resultSlots->fetch()) {
                        echo '<h4>'.__($rowSlots['day']).'</h4>';
                        echo '<p>';
                        echo '<i>'.__('Time').'</i>: '.substr($rowSlots['timeStart'], 0, 5).' - '.substr($rowSlots['timeEnd'], 0, 5).'<br/>';
                        if ($rowSlots['gibbonSpaceID'] != '') {
                            echo '<i>'.__('Location').'</i>: '.$rowSlots['space'];
                        } else {
                            echo '<i>'.__('Location').'</i>: '.$rowSlots['locationExternal'];
                        }
                        echo '</p>';

                        ++$count;
                    }
                    if ($count == 0) {
                        echo '<i>'.__('None').'</i>';
                    }

                    $role = getRoleCategory($session->get('gibbonRoleIDCurrent'), $connection2);
                    if ($role == 'Staff') {
                        echo '<h3>'.__('Participants').'</h3>';


                            $dataStudents = array('gibbonActivityID' => $row['gibbonActivityID']);
                            $sqlStudents = "SELECT title, preferredName, surname FROM gibbonActivityStudent JOIN gibbonPerson ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonActivityID=:gibbonActivityID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonActivityStudent.status='Accepted' ORDER BY surname, preferredName";
                            $resultStudents = $connection2->prepare($sqlStudents);
                            $resultStudents->execute($dataStudents);

                        if ($resultStudents->rowCount() < 1) {
                            echo '<i>'.__('None').'</i>';
                        } else {
                            echo "<ul style='margin-left: 15px'>";
                            while ($rowStudent = $resultStudents->fetch()) {
                                echo '<li>'.Format::name('', $rowStudent['preferredName'], $rowStudent['surname'], 'Student').'</li>';
                            }
                            echo '</ul>';
                        }


                            $dataStudents = array('gibbonActivityID' => $row['gibbonActivityID']);
                            $sqlStudents = "SELECT title, preferredName, surname FROM gibbonActivityStudent JOIN gibbonPerson ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonActivityID=:gibbonActivityID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonActivityStudent.status='Waiting List' ORDER BY timestamp";
                            $resultStudents = $connection2->prepare($sqlStudents);
                            $resultStudents->execute($dataStudents);

                        if ($resultStudents->rowCount() > 0) {
                            echo '<h3>'.__('Waiting List').'</h3>';
                            echo "<ol style='margin-left: 15px'>";
                            while ($rowStudent = $resultStudents->fetch()) {
                                echo '<li>'.Format::name('', $rowStudent['preferredName'], $rowStudent['surname'], 'Student').'</li>';
                            }
                            echo '</ol>';
                        }
                    }
                    echo '</div>';
                }
            }
        }
    }
}
