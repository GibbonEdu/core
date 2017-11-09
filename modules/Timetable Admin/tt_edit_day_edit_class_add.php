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

use Gibbon\Forms\Form;

@session_start();

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/tt_edit_day_edit_class_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Check if school year specified
    $gibbonTTDayID = $_GET['gibbonTTDayID'];
    $gibbonTTID = $_GET['gibbonTTID'];
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
    $gibbonTTColumnRowID = $_GET['gibbonTTColumnRowID'];

    if ($gibbonTTDayID == '' or $gibbonTTID == '' or $gibbonSchoolYearID == '' or $gibbonTTColumnRowID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonTTDayID' => $gibbonTTDayID, 'gibbonTTID' => $gibbonTTID, 'gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonTTColumnRowID' => $gibbonTTColumnRowID);
            $sql = 'SELECT gibbonTT.name AS ttName, gibbonTTDay.name AS dayName, gibbonTTColumnRow.name AS rowName, gibbonYearGroupIDList FROM gibbonTT JOIN gibbonTTDay ON (gibbonTT.gibbonTTID=gibbonTTDay.gibbonTTID) JOIN gibbonTTColumn ON (gibbonTTDay.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) JOIN gibbonTTColumnRow ON (gibbonTTColumn.gibbonTTColumnID=gibbonTTColumnRow.gibbonTTColumnID) WHERE gibbonTTDay.gibbonTTDayID=:gibbonTTDayID AND gibbonTT.gibbonTTID=:gibbonTTID AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonTTColumnRowID=:gibbonTTColumnRowID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __($guid, 'The specified record cannot be found.');
            echo '</div>';
        } else {
            //Let's go!
            $values = $result->fetch();

            echo "<div class='trail'>";
            echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > ... > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/tt.php&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID']."'>".__($guid, 'Manage Timetables')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/tt_edit.php&gibbonTTID=$gibbonTTID&gibbonSchoolYearID=".$_GET['gibbonSchoolYearID']."'>".__($guid, 'Edit Timetable')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/tt_edit_day_edit.php&gibbonTTDayID=$gibbonTTDayID&gibbonTTID=$gibbonTTID&gibbonSchoolYearID=$gibbonSchoolYearID'>".__($guid, 'Edit Timetable Day')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/tt_edit_day_edit_class.php&gibbonTTDayID=$gibbonTTDayID&gibbonTTID=$gibbonTTID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonTTColumnRowID=$gibbonTTColumnRowID'>".__($guid, 'Classes in Period')."</a> > </div><div class='trailEnd'>".__($guid, 'Add Class to Period').'</div>';
            echo '</div>';

            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return'], null, null);
            }

            $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/tt_edit_day_edit_class_addProcess.php?&gibbonTTDayID=$gibbonTTDayID&gibbonTTID=$gibbonTTID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonTTColumnRowID=$gibbonTTColumnRowID");

            $form->addHiddenValue('address', $_SESSION[$guid]['address']);
            $form->addHiddenValue('gibbonTTID', $gibbonTTID);
            $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);

            $row = $form->addRow();
                $row->addLabel('ttName', __('Timetable'));
                $row->addTextField('ttName')->maxLength(20)->isRequired()->readonly()->setValue($values['ttName']);

            $row = $form->addRow();
                $row->addLabel('dayName', __('Day'));
                $row->addTextField('dayName')->maxLength(20)->isRequired()->readonly()->setValue($values['dayName']);

            $row = $form->addRow();
                $row->addLabel('rowName', __('Period'));
                $row->addTextField('rowName')->maxLength(20)->isRequired()->readonly()->setValue($values['rowName']);

            $classes = array();
            $years = explode(',', $values['gibbonYearGroupIDList']);
            try {
                $dataSelect = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
                if (count($years) > 0) {
                    $sqlSelectWhere = ' AND (';
                    for ($i = 0; $i < count($years); ++$i) {
                        if ($i > 0) {
                            $sqlSelectWhere = $sqlSelectWhere.' OR ';
                        }
                        $dataSelect[$years[$i]] = '%'.$years[$i].'%';
                        $sqlSelectWhere = $sqlSelectWhere.'(gibbonYearGroupIDList LIKE :'.$years[$i].')';
                    }
                    $sqlSelectWhere = $sqlSelectWhere.')';
                }
                $sqlSelect = "SELECT gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID $sqlSelectWhere ORDER BY course, class";
                $resultSelect = $connection2->prepare($sqlSelect);
                $resultSelect->execute($dataSelect);
            } catch (PDOException $e) {}
            while ($rowSelect = $resultSelect->fetch()) {
                try {
                    $dataUnique = array('gibbonTTDayID' => $gibbonTTDayID, 'gibbonTTColumnRowID' => $gibbonTTColumnRowID, 'gibbonCourseClassID' => $rowSelect['gibbonCourseClassID']);
                    $sqlUnique = 'SELECT * FROM gibbonTTDayRowClass WHERE gibbonTTDayID=:gibbonTTDayID AND gibbonTTColumnRowID=:gibbonTTColumnRowID AND gibbonCourseClassID=:gibbonCourseClassID';
                    $resultUnique = $connection2->prepare($sqlUnique);
                    $resultUnique->execute($dataUnique);
                } catch (PDOException $e) {}
                if ($resultUnique->rowCount() < 1) {
                    $classes[$rowSelect['gibbonCourseClassID']] = htmlPrep($rowSelect['course']).'.'.htmlPrep($rowSelect['class']);
                }
            }
            $row = $form->addRow();
                $row->addLabel('gibbonCourseClassID', __('Class'));
                $row->addSelect('gibbonCourseClassID')->fromArray($classes)->isRequired()->placeholder();

            $locations = array() ;
            try {
                $dataSelect = array();
                $sqlSelect = 'SELECT * FROM gibbonSpace ORDER BY name';
                $resultSelect = $connection2->prepare($sqlSelect);
                $resultSelect->execute($dataSelect);
            } catch (PDOException $e) {}
            while ($rowSelect = $resultSelect->fetch()) {
                try {
                    $dataUnique = array('gibbonTTDayID' => $gibbonTTDayID, 'gibbonTTColumnRowID' => $gibbonTTColumnRowID, 'gibbonSpaceID' => $rowSelect['gibbonSpaceID']);
                    $sqlUnique = 'SELECT * FROM gibbonTTDayRowClass WHERE gibbonTTDayID=:gibbonTTDayID AND gibbonTTColumnRowID=:gibbonTTColumnRowID AND gibbonSpaceID=:gibbonSpaceID';
                    $resultUnique = $connection2->prepare($sqlUnique);
                    $resultUnique->execute($dataUnique);
                } catch (PDOException $e) {}
                if ($resultUnique->rowCount() < 1) {
                    $locations[$rowSelect['gibbonSpaceID']] = htmlPrep($rowSelect['name']);
                }
            }
            $row = $form->addRow();
                $row->addLabel('gibbonSpaceID', __('Location'));
                $row->addSelect('gibbonSpaceID')->fromArray($locations)->placeholder();

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            echo $form->getOutput();
        }
    }
}
?>
