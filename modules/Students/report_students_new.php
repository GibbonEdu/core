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

if (isActionAccessible($guid, $connection2, '/modules/Students/report_students_new') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'New Students').'</div>';
    echo '</div>';

    echo '<h2>';
    echo __($guid, 'Choose Options');
    echo '</h2>';

    $type = null;
    if (isset($_GET['type'])) {
        $type = $_GET['type'];
    }
    $ignoreEnrolment = null;
    if (isset($_GET['ignoreEnrolment'])) {
        $ignoreEnrolment = $_GET['ignoreEnrolment'];
    }
    $startDateFrom = null;
    if (isset($_GET['startDateFrom'])) {
        $startDateFrom = $_GET['startDateFrom'];
    }
    $startDateTo = null;
    if (isset($_GET['startDateTo'])) {
        $startDateTo = $_GET['startDateTo'];
    }

    $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/index.php','get');

    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q', "/modules/".$_SESSION[$guid]['module']."/report_students_new.php");

    $row = $form->addRow();
        $row->addLabel('type', __('Type'));
        $row->addSelect('type')->fromArray(array('Current School Year' => __('Current School Year'), 'Date Range' => __('Date Range')))->selected($type)->isRequired();

    $form->toggleVisibilityByClass('dateRange')->onSelect('type')->when('Date Range');

    $row = $form->addRow()->addClass('dateRange');
        $row->addLabel('startDateFrom', __('From Date'))->description('Earliest student start date to include.')->append('<br/>')->append(__('Format:').' ')->append($_SESSION[$guid]['i18n']['dateFormat']);
        $row->addDate('startDateFrom')->setValue($startDateFrom)->isRequired();

    $row = $form->addRow()->addClass('dateRange');
        $row->addLabel('startDateTo', __('To Date'))->description('Latest student start date to include.')->append('<br/>')->append(__('Format:').' ')->append($_SESSION[$guid]['i18n']['dateFormat']);
        $row->addDate('startDateTo')->setValue($startDateTo)->isRequired();

    $row = $form->addRow()->addClass('dateRange');
        $row->addLabel('ignoreEnrolment', __('Ignore Enrolment'))->description('This is useful for picking up students who are set to Full, have a start date but are not yet enroled.');
        $row->addCheckbox('ignoreEnrolment')->checked($ignoreEnrolment);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit(__('Go'))->prepend(sprintf('<a href="%s" class="right">%s</a> &nbsp;', $_SESSION[$guid]['absoluteURL'].'/index.php?q='.$_GET['q'], __('Clear Form')));

    echo $form->getOutput();

    if ($type != '') {
        echo '<h2>';
        echo __($guid, 'Report Data');
        echo '</h2>';

        $proceed = true;
        if ($type == 'Date Range') {
            echo '<p>';
            echo __($guid, 'This report shows all students whose Start Date is on or between the indicated dates.');
            echo '</p>';

            if ($startDateFrom == '' or $startDateTo == '') {
                $proceed = false;
            }
        } elseif ($type == 'Current School Year') {
            echo '<p>';
            echo __($guid, 'This report shows all students who are newly arrived in the school during the current academic year (e.g. they were not enroled in the previous academic year).');
            echo '</p>';
        }

        if ($proceed == false) {
            echo "<div class='error'>";
            echo __($guid, 'Your request failed because your inputs were invalid.');
            echo '</div>';
        } else {
            try {
                if ($type == 'Date Range') {
                    if ($ignoreEnrolment != 'on') {
                        $data = array('startDateFrom' => dateConvert($guid, $startDateFrom), 'startDateTo' => dateConvert($guid, $startDateTo));
                        $sql = "SELECT DISTINCT gibbonPerson.gibbonPersonID, surname, preferredName, username, dateStart, lastSchool, (SELECT nameShort FROM gibbonRollGroup JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) JOIN gibbonSchoolYear ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID ORDER BY gibbonSchoolYear.sequenceNumber LIMIT 0, 1) AS rollGroup FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE dateStart>=:startDateFrom AND dateStart<=:startDateTo AND status='Full' ORDER BY dateStart, surname, preferredName";
                    } else {
                        $data = array('startDateFrom' => dateConvert($guid, $startDateFrom), 'startDateTo' => dateConvert($guid, $startDateTo));
                        $sql = "SELECT DISTINCT gibbonPerson.gibbonPersonID, surname, preferredName, username, dateStart, lastSchool, (SELECT nameShort FROM gibbonRollGroup JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) JOIN gibbonSchoolYear ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID ORDER BY gibbonSchoolYear.sequenceNumber LIMIT 0, 1) AS rollGroup FROM gibbonPerson WHERE dateStart>=:startDateFrom AND dateStart<=:startDateTo AND status='Full' ORDER BY dateStart, surname, preferredName";
                    }
                } elseif ($type == 'Current School Year') {
                    $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                    $sql = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName, gibbonRollGroup.nameShort AS rollGroup, username, dateStart, lastSchool FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND status='Full' ORDER BY rollGroup, surname, preferredName";
                }
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }
            if ($result->rowCount() > 0) {
                if ($type == 'Current School Year') {
                    echo "<table cellspacing='0' style='width: 100%'>";
                    echo "<tr class='head'>";
                    echo '<th>';
                    echo __($guid, 'Count');
                    echo '</th>';
                    echo '<th>';
                    echo __($guid, 'Name');
                    echo '</th>';
                    echo '<th>';
                    echo __($guid, 'Roll Group');
                    echo '</th>';
                    echo '<th>';
                    echo __($guid, 'Username');
                    echo '</th>';
                    echo '<th>';
                    echo __($guid, 'Start Date');
                    echo '</th>';
                    echo '<th>';
                    echo __($guid, 'Last School');
                    echo '</th>';
                    echo '<th>';
                    echo __($guid, 'Parents');
                    echo '</th>';
                    echo '</tr>';

                    $count = 0;
                    $rowNum = 'odd';
                    while ($row = $result->fetch()) {
                        if ($count % 2 == 0) {
                            $rowNum = 'even';
                        } else {
                            $rowNum = 'odd';
                        }
                        try {
                            $data2 = array('gibbonSchoolYearID' => getPreviousSchoolYearID($_SESSION[$guid]['gibbonSchoolYearID'], $connection2), 'gibbonPersonID' => $row['gibbonPersonID']);
                            $sql2 = "SELECT surname, preferredName, gibbonRollGroup.nameShort AS rollGroup, username FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND status='Full' AND gibbonPerson.gibbonPersonID=:gibbonPersonID ORDER BY rollGroup, surname, preferredName";
                            $result2 = $connection2->prepare($sql2);
                            $result2->execute($data2);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }
                        if ($result2->rowCount() == 0) {
                            ++$count;
                            echo "<tr class=$rowNum>";
                            echo '<td>';
                            echo $count;
                            echo '</td>';
                            echo '<td>';
                            echo formatName('', $row['preferredName'], $row['surname'], 'Student', true);
                            echo '</td>';
                            echo '<td>';
                            echo $row['rollGroup'];
                            echo '</td>';
                            echo '<td>';
                            echo $row['username'];
                            echo '</td>';
                            echo '<td>';
                            echo dateConvertBack($guid, $row['dateStart']);
                            echo '</td>';
                            echo '<td>';
                            echo $row['lastSchool'];
                            echo '</td>';
                            echo '<td>';
                            try {
                                $dataFamily = array('gibbonPersonID' => $row['gibbonPersonID']);
                                $sqlFamily = 'SELECT gibbonFamilyID FROM gibbonFamilyChild WHERE gibbonPersonID=:gibbonPersonID';
                                $resultFamily = $connection2->prepare($sqlFamily);
                                $resultFamily->execute($dataFamily);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }
                            while ($rowFamily = $resultFamily->fetch()) {
                                try {
                                    $dataFamily2 = array('gibbonFamilyID' => $rowFamily['gibbonFamilyID']);
                                    $sqlFamily2 = 'SELECT gibbonPerson.* FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonPerson.gibbonPersonID=gibbonFamilyAdult.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID ORDER BY contactPriority, surname, preferredName';
                                    $resultFamily2 = $connection2->prepare($sqlFamily2);
                                    $resultFamily2->execute($dataFamily2);
                                } catch (PDOException $e) {
                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                }
                                while ($rowFamily2 = $resultFamily2->fetch()) {
                                    echo '<u>'.formatName($rowFamily2['title'], $rowFamily2['preferredName'], $rowFamily2['surname'], 'Parent').'</u><br/>';
                                    $numbers = 0;
                                    for ($i = 1; $i < 5; ++$i) {
                                        if ($rowFamily2['phone'.$i] != '') {
                                            if ($rowFamily2['phone'.$i.'Type'] != '') {
                                                echo '<i>'.$rowFamily2['phone'.$i.'Type'].':</i> ';
                                            }
                                            if ($rowFamily2['phone'.$i.'CountryCode'] != '') {
                                                echo '+'.$rowFamily2['phone'.$i.'CountryCode'].' ';
                                            }
                                            echo $rowFamily2['phone'.$i].'<br/>';
                                            ++$numbers;
                                        }
                                    }
                                    if ($rowFamily2['citizenship1'] != '' or $rowFamily2['citizenship1Passport'] != '') {
                                        echo '<i>Passport</i>: '.$rowFamily2['citizenship1'].' '.$rowFamily2['citizenship1Passport'].'<br/>';
                                    }
                                    if ($rowFamily2['nationalIDCardNumber'] != '') {
                                        if ($_SESSION[$guid]['country'] == '') {
                                            echo '<i>National ID Card</i>: ';
                                        } else {
                                            echo '<i>'.$_SESSION[$guid]['country'].' ID Card</i>: ';
                                        }
                                        echo $rowFamily2['nationalIDCardNumber'].'<br/>';
                                    }
                                }
                            }
                            echo '</td>';
                            echo '</tr>';
                        }
                    }
                    echo '</table>';
                } elseif ($type == 'Date Range') {
                    echo "<table cellspacing='0' style='width: 100%'>";
                    echo "<tr class='head'>";
                    echo '<th>';
                    echo 'Count';
                    echo '</th>';
                    echo '<th>';
                    echo __($guid, 'Name');
                    echo '</th>';
                    echo '<th>';
                    echo __($guid, 'Roll Group');
                    echo '</th>';
                    echo '<th>';
                    echo __($guid, 'Username');
                    echo '</th>';
                    echo '<th>';
                    echo 'Start Date';
                    echo '</th>';
                    echo '<th>';
                    echo __($guid, 'Last School');
                    echo '</th>';
                    echo '<th>';
                    echo __($guid, 'Parents');
                    echo '</th>';
                    echo '</tr>';

                    $count = 0;
                    $rowNum = 'odd';
                    while ($row = $result->fetch()) {
                        if ($count % 2 == 0) {
                            $rowNum = 'even';
                        } else {
                            $rowNum = 'odd';
                        }

                        ++$count;
                        echo "<tr class=$rowNum>";
                        echo '<td>';
                        echo $count;
                        echo '</td>';
                        echo '<td>';
                        echo formatName('', $row['preferredName'], $row['surname'], 'Student', true);
                        echo '</td>';
                        echo '<td>';
                        echo $row['rollGroup'];
                        echo '</td>';
                        echo '<td>';
                        echo $row['username'];
                        echo '</td>';
                        echo '<td>';
                        echo dateConvertBack($guid, $row['dateStart']);
                        echo '</td>';
                        echo '<td>';
                        echo $row['lastSchool'];
                        echo '</td>';
                        echo '<td>';
                        try {
                            $dataFamily = array('gibbonPersonID' => $row['gibbonPersonID']);
                            $sqlFamily = 'SELECT gibbonFamilyID FROM gibbonFamilyChild WHERE gibbonPersonID=:gibbonPersonID';
                            $resultFamily = $connection2->prepare($sqlFamily);
                            $resultFamily->execute($dataFamily);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }
                        while ($rowFamily = $resultFamily->fetch()) {
                            try {
                                $dataFamily2 = array('gibbonFamilyID' => $rowFamily['gibbonFamilyID']);
                                $sqlFamily2 = 'SELECT gibbonPerson.* FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonPerson.gibbonPersonID=gibbonFamilyAdult.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID ORDER BY contactPriority, surname, preferredName';
                                $resultFamily2 = $connection2->prepare($sqlFamily2);
                                $resultFamily2->execute($dataFamily2);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }
                            while ($rowFamily2 = $resultFamily2->fetch()) {
                                echo '<u>'.formatName($rowFamily2['title'], $rowFamily2['preferredName'], $rowFamily2['surname'], 'Parent').'</u><br/>';
                                $numbers = 0;
                                for ($i = 1; $i < 5; ++$i) {
                                    if ($rowFamily2['phone'.$i] != '') {
                                        if ($rowFamily2['phone'.$i.'Type'] != '') {
                                            echo '<i>'.$rowFamily2['phone'.$i.'Type'].':</i> ';
                                        }
                                        if ($rowFamily2['phone'.$i.'CountryCode'] != '') {
                                            echo '+'.$rowFamily2['phone'.$i.'CountryCode'].' ';
                                        }
                                        echo $rowFamily2['phone'.$i].'<br/>';
                                        ++$numbers;
                                    }
                                }
                                if ($rowFamily2['citizenship1'] != '' or $rowFamily2['citizenship1Passport'] != '') {
                                    echo '<i>Passport</i>: '.$rowFamily2['citizenship1'].' '.$rowFamily2['citizenship1Passport'].'<br/>';
                                }
                                if ($rowFamily2['nationalIDCardNumber'] != '') {
                                    if ($_SESSION[$guid]['country'] == '') {
                                        echo '<i>National ID Card</i>: ';
                                    } else {
                                        echo '<i>'.$_SESSION[$guid]['country'].' ID Card</i>: ';
                                    }
                                    echo $rowFamily2['nationalIDCardNumber'].'<br/>';
                                }
                            }
                        }
                        echo '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                }
            } else {
                echo "<div class='warning'>";
                echo __($guid, 'There are no records to display.');
                echo '</div>';
            }
        }
    }
}
?>
