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

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Timetable/tt_master.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'View Master Timetable').'</div>';
    echo '</div>';

    echo '<h2>';
    echo __($guid, 'Choose Timetable');
    echo '</h2>';

    $gibbonTTID = null;
    if (isset($_GET['gibbonTTID'])) {
        $gibbonTTID = $_GET['gibbonTTID'];
    }
    if ($gibbonTTID == null) { //If TT not set, get the first timetable in the current year, and display that
        try {
            $dataSelect = array();
            $sqlSelect = "SELECT gibbonTTID FROM gibbonTT JOIN gibbonSchoolYear ON (gibbonTT.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonSchoolYear.status='Current' ORDER BY gibbonTT.name LIMIT 0, 1";
            $resultSelect = $connection2->prepare($sqlSelect);
            $resultSelect->execute($dataSelect);
        } catch (PDOException $e) {
        }
        if ($resultSelect->rowCount() == 1) {
            $rowSelect = $resultSelect->fetch();
            $gibbonTTID = $rowSelect['gibbonTTID'];
        }
    }

    $form = Form::create('ttMaster', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');

    $form->addHiddenValue('q', '/modules/'.$_SESSION[$guid]['module'].'/tt_master.php');

    $sql = "SELECT gibbonTTID as value, gibbonTT.name AS name FROM gibbonTT JOIN gibbonSchoolYear ON (gibbonTT.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) ORDER BY gibbonSchoolYear.sequenceNumber, gibbonTT.name";
    $row = $form->addRow();
        $row->addLabel('gibbonTTID', __('Timetable'));
        $row->addSelect('gibbonTTID')->fromQuery($pdo, $sql)->isRequired()->selected($gibbonTTID);

    $row = $form->addRow();
        $row->addSubmit();

    echo $form->getOutput();

    if ($gibbonTTID != '') {
        //CHECK FOR TT
        try {
            $data = array('gibbonTTID' => $gibbonTTID);
            $sql = 'SELECT gibbonTTID, gibbonTT.name AS TT, gibbonSchoolYear.name AS year FROM gibbonTT JOIN gibbonSchoolYear ON (gibbonTT.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonTTID=:gibbonTTID ORDER BY gibbonSchoolYear.sequenceNumber, gibbonTT.name';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>";
            echo $e->getMessage();
            echo '</div>';
        }

        if ($result->rowCount() < 1) {
            echo "<div class='error'>";
            echo __($guid, 'There are no records to display.');
            echo '</div>';
        } else {
            //GET TT DAYS
            try {
                $dataDays = array('gibbonTTID' => $gibbonTTID);
                $sqlDays = 'SELECT gibbonTTDay.name AS name, gibbonTTColumn.gibbonTTColumnID, gibbonTTDayID FROM gibbonTTDay JOIN gibbonTTColumn ON (gibbonTTDay.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) WHERE gibbonTTID=:gibbonTTID ORDER BY gibbonTTID';
                $resultDays = $connection2->prepare($sqlDays);
                $resultDays->execute($dataDays);
            } catch (PDOException $e) {
                echo "<div class='error'>";
                echo $e->getMessage();
                echo '</div>';
            }

            if ($resultDays->rowCount() < 1) {
                echo "<div class='error'>";
                echo __($guid, 'There are no records to display.');
                echo '</div>';
            } else {
                //Output days
                while ($rowDays = $resultDays->fetch()) {
                    echo "<h2 style='margin-top: 40px'>";
                    echo __($guid, $rowDays['name']);
                    echo '</h2>';

                    //GET PERIODS/ROWS
                    try {
                        $dataPeriods = array('gibbonTTColumnID' => $rowDays['gibbonTTColumnID']);
                        $sqlPeriods = 'SELECT * FROM gibbonTTColumnRow WHERE gibbonTTColumnID=:gibbonTTColumnID ORDER BY timeStart, name';
                        $resultPeriods = $connection2->prepare($sqlPeriods);
                        $resultPeriods->execute($dataPeriods);
                    } catch (PDOException $e) {
                        echo "<div class='error'>";
                        echo $e->getMessage();
                        echo '</div>';
                    }

                    if ($resultPeriods->rowCount() < 1) {
                        echo "<div class='error'>";
                        echo __($guid, 'There are no records to display.');
                        echo '</div>';
                    } else {
                        //Output periods/rows
                        while ($rowPeriods = $resultPeriods->fetch()) {
                            echo "<h5 style='margin-top: 25px'>";
                            echo __($guid, $rowPeriods['name']).'<span style=\'font-weight: normal\'> ('.substr($rowPeriods['timeStart'], 0, 5).' - '.substr($rowPeriods['timeEnd'], 0, 5).')</span>';
                            echo '</h5>';

                            //GET CLASSES
                            try {
                                $dataClasses = array('gibbonTTColumnRowID' => $rowPeriods['gibbonTTColumnRowID'], 'gibbonTTDayID' => $rowDays['gibbonTTDayID']);
                                $sqlClasses = 'SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonTTDayRowClassID, gibbonSpace.name AS space FROM gibbonTTDayRowClass JOIN gibbonCourseClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) LEFT JOIN gibbonSpace ON (gibbonTTDayRowClass.gibbonSpaceID=gibbonSpace.gibbonSpaceID) WHERE gibbonTTColumnRowID=:gibbonTTColumnRowID AND gibbonTTDayID=:gibbonTTDayID ORDER BY course, class';
                                $resultClasses = $connection2->prepare($sqlClasses);
                                $resultClasses->execute($dataClasses);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }

                            if ($resultClasses->rowCount() < 1) {
                                echo "<div class='error'>";
                                echo __($guid, 'There are no classes associated with this period on this day.');
                                echo '</div>';
                            } else {
                                //Let's go!
                                echo "<table cellspacing='0' style='width: 100%'>";
                                echo "<tr class='head'>";
                                echo "<th style='width: 34%'>";
                                echo __($guid, 'Class');
                                echo '</th>';
                                echo "<th style='width: 33%'>";
                                echo __($guid, 'Location');
                                echo '</th>';
                                echo "<th style='width: 33%'>";
                                echo __($guid, 'Teachers');
                                echo '</th>';
                                echo '</tr>';

                                $count = 0;
                                $rowNum = 'odd';
                                while ($rowClasses = $resultClasses->fetch()) {
                                    if ($count % 2 == 0) {
                                        $rowNum = 'even';
                                    } else {
                                        $rowNum = 'odd';
                                    }

									//COLOR ROW BY STATUS!
									echo "<tr class=$rowNum>";
                                    echo "<td style='padding-top: 3px; padding-bottom: 4px'>";
                                    echo $rowClasses['course'].'.'.$rowClasses['class'];
                                    echo '</td>';
                                    echo "<td style='padding-top: 3px; padding-bottom: 4px'>";
                                    if ($rowClasses['space'] != '') {
                                        echo $rowClasses['space'];
                                    }
                                    echo '</td>';
                                    echo "<td style='padding-top: 3px; padding-bottom: 4px'>";
                                                //Get teachers (accounting for exemptions)
                                                try {
                                                    $dataTeachers = array('gibbonCourseClassID' => $rowClasses['gibbonCourseClassID'], 'gibbonTTDayRowClassID' => $rowClasses['gibbonTTDayRowClassID']);
                                                    $sqlTeachers = "SELECT DISTINCT surname, preferredName, gibbonTTDayRowClassException.gibbonPersonID AS exception FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonTTDayRowClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) LEFT JOIN gibbonTTDayRowClassException ON (gibbonTTDayRowClassException.gibbonTTDayRowClassID=gibbonTTDayRowClass.gibbonTTDayRowClassID AND gibbonTTDayRowClassException.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE role='Teacher' AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID AND gibbonTTDayRowClass.gibbonTTDayRowClassID=:gibbonTTDayRowClassID ORDER BY surname, preferredName";
                                                    $resultTeachers = $connection2->prepare($sqlTeachers);
                                                    $resultTeachers->execute($dataTeachers);
                                                } catch (PDOException $e) {
                                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                                }
                                    while ($rowTeachers = $resultTeachers->fetch()) {
                                        if ($rowTeachers['exception'] == null) {
                                            echo formatName('', $rowTeachers['preferredName'], $rowTeachers['surname'], 'Staff', false, true);
                                            echo '<br/>';
                                        }
                                    }
                                    echo '</td>';
                                    echo '</tr>';

                                    ++$count;
                                }
                                echo '</table>';
                            }
                        }
                    }
                }
            }
        }
    }
}
?>
