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

if (isActionAccessible($guid, $connection2, '/modules/Students/report_students_left.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Left Students').'</div>';
    echo '</div>';

    echo '<h2>';
    echo __($guid, 'Choose Options');
    echo '</h2>';

    $type = null;
    if (isset($_GET['type'])) {
        $type = $_GET['type'];
    }
    $endDateFrom = null;
    if (isset($_GET['endDateFrom'])) {
        $endDateFrom = $_GET['endDateFrom'];
    }
    $endDateTo = null;
    if (isset($_GET['endDateTo'])) {
        $endDateTo = $_GET['endDateTo'];
    }
    $ignoreStatus = null;
    if (isset($_GET['ignoreStatus'])) {
        $ignoreStatus = $_GET['ignoreStatus'];
    }

    $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/index.php','get');

    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q', "/modules/".$_SESSION[$guid]['module']."/report_students_left.php");

    $row = $form->addRow();
        $row->addLabel('type', __('Type'));
        $row->addSelect('type')->fromArray(array('Current School Year' => __('Current School Year'), 'Date Range' => __('Date Range')))->selected($type)->isRequired();

    $form->toggleVisibilityByClass('dateRange')->onSelect('type')->when('Date Range');

    $row = $form->addRow()->addClass('dateRange');
        $row->addLabel('endDateFrom', __('From Date'))->description('Earliest student end date to include.')->append('<br/>')->append(__('Format:').' ')->append($_SESSION[$guid]['i18n']['dateFormat']);
        $row->addDate('endDateFrom')->setValue($endDateFrom)->isRequired();

    $row = $form->addRow()->addClass('dateRange');
        $row->addLabel('endDateTo', __('To Date'))->description('Latest student end date to include.')->append('<br/>')->append(__('Format:').' ')->append($_SESSION[$guid]['i18n']['dateFormat']);
        $row->addDate('endDateTo')->setValue($endDateTo)->isRequired();

    $row = $form->addRow()->addClass('dateRange');
        $row->addLabel('ignoreStatus', __('Ignore Status'))->description('This is useful for picking up students who have not yet left, but have an End Date set.');
        $row->addCheckbox('ignoreStatus')->checked($ignoreStatus);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSearchSubmit($gibbon->session);

    echo $form->getOutput();

    if ($type != '') {
        echo '<h2>';
        echo __($guid, 'Report Data');
        echo '</h2>';

        $proceed = true;
        if ($type == 'Date Range') {
            echo '<p>';
            echo __($guid, 'This report shows all students whose End Date is on or between the indicated dates.');
            echo '</p>';

            if ($endDateFrom == '' or $endDateTo == '') {
                $proceed = false;
            }
        } elseif ($type == 'Current School Year') {
            echo '<p>';
            echo __($guid, 'This report shows all students who left the school during the current academic year.');
            echo '</p>';
        }

        if ($proceed == false) {
            echo "<div class='error'>";
            echo __($guid, 'Your request failed because your inputs were invalid.');
            echo '</div>';
        } else {
            try {
                if ($type == 'Date Range') {
                    $data = array('endDateFrom' => dateConvert($guid, $endDateFrom), 'endDateTo' => dateConvert($guid, $endDateTo));
                    if ($ignoreStatus == 'on') {
                        $sql = 'SELECT DISTINCT gibbonPerson.gibbonPersonID, surname, preferredName, username, dateEnd, nextSchool, departureReason FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE dateEnd>=:endDateFrom AND dateEnd<=:endDateTo ORDER BY surname, preferredName';
                    } else {
                        $sql = "SELECT DISTINCT gibbonPerson.gibbonPersonID, surname, preferredName, username, dateEnd, nextSchool, departureReason FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE dateEnd>=:endDateFrom AND dateEnd<=:endDateTo AND status='Left' ORDER BY surname, preferredName";
                    }
                } elseif ($type == 'Current School Year') {
                    $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                    $sql = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName, gibbonRollGroup.nameShort AS rollGroup, username, dateStart, lastSchool FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND status='Left' ORDER BY rollGroup, surname, preferredName";
                }
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }
            if ($result->rowCount() > 0) {
                echo "<table cellspacing='0' style='width: 100%'>";
                echo "<tr class='head'>";
                echo '<th>';
                echo __($guid, 'Count');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Name');
                echo "<span style='font-style: italic; font-size: 85%'>".__($guid, 'Roll Group').'</span>';
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Username');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'End Date').'<br/>';
                echo "<span style='font-style: italic; font-size: 85%'>".__($guid, 'Departure Reason').'</span>';
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Next School');
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
                    echo formatName('', $row['preferredName'], $row['surname'], 'Student', true).'<br/>';
                    try {
                        $dataCurrent = array('gibbonPersonID' => $row['gibbonPersonID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                        $sqlCurrent = 'SELECT name FROM gibbonStudentEnrolment JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonPersonID=:gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID';
                        $resultCurrent = $connection2->prepare($sqlCurrent);
                        $resultCurrent->execute($dataCurrent);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }
                    if ($resultCurrent->rowCount() == 1) {
                        $rowCurrent = $resultCurrent->fetch();
                        echo "<span style='font-style: italic; font-size: 85%'>".$rowCurrent['name'].'</span>';
                    }
                    echo '</td>';
                    echo '<td>';
                    echo $row['username'];
                    echo '</td>';
                    echo '<td>';
                    echo dateConvertBack($guid, $row['dateEnd']).'<br/>';
                    echo "<span style='font-style: italic; font-size: 85%'>".$row['departureReason'].'</span>';
                    echo '</td>';
                    echo '<td>';
                    echo $row['nextSchool'];
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
                                echo '<i>'.__($guid, 'Passport').'</i>: '.$rowFamily2['citizenship1'].' '.$rowFamily2['citizenship1Passport'].'<br/>';
                            }
                            if ($rowFamily2['nationalIDCardNumber'] != '') {
                                if ($_SESSION[$guid]['country'] == '') {
                                    echo '<i>'.__($guid, 'National ID Card').'</i>: ';
                                } else {
                                    echo '<i>'.$_SESSION[$guid]['country'].' '.__($guid, 'ID Card').'</i>: ';
                                }
                                echo $rowFamily2['nationalIDCardNumber'].'<br/>';
                            }
                        }
                    }
                    echo '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            } else {
                echo "<div class='warning'>";
                echo __($guid, 'There are no records to display.');
                echo '</div>';
            }
        }
    }
}
?>
