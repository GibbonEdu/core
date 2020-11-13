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

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/tt_edit_day_edit_class_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Check if school year specified
    $gibbonTTDayID = $_GET['gibbonTTDayID'] ?? '';
    $gibbonTTID = $_GET['gibbonTTID'] ?? '';
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
    $gibbonTTColumnRowID = $_GET['gibbonTTColumnRowID'] ?? '';
    $gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';

    if ($gibbonTTDayID == '' or $gibbonTTID == '' or $gibbonSchoolYearID == '' or $gibbonTTColumnRowID == '' or $gibbonCourseClassID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        
            $data = array('gibbonTTColumnRowID' => $gibbonTTColumnRowID, 'gibbonTTDayID' => $gibbonTTDayID, 'gibbonTTColumnRowID' => $gibbonTTColumnRowID, 'gibbonCourseClassID' => $gibbonCourseClassID);
            $sql = 'SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonTTDayRowClassID, gibbonSpaceID FROM gibbonTTDayRowClass JOIN gibbonCourseClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonTTColumnRowID=:gibbonTTColumnRowID AND gibbonTTDayID=:gibbonTTDayID AND gibbonTTColumnRowID=:gibbonTTColumnRowID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID';
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() < 1) {
            $page->addError(__('The specified record cannot be found.'));
        } else {
            //Let's go!
            $row = $result->fetch();
            $course = $row['course'];
            $class = $row['class'];
            $gibbonSpaceID = $row['gibbonSpaceID'];
            $gibbonTTDayRowClassID = $row['gibbonTTDayRowClassID'];

            
                $data = array('gibbonTTDayID' => $gibbonTTDayID, 'gibbonTTID' => $gibbonTTID, 'gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonTTColumnRowID' => $gibbonTTColumnRowID);
                $sql = 'SELECT gibbonTT.name AS ttName, gibbonTTDay.name AS dayName, gibbonTTColumnRow.name AS rowName, gibbonYearGroupIDList FROM gibbonTT JOIN gibbonTTDay ON (gibbonTT.gibbonTTID=gibbonTTDay.gibbonTTID) JOIN gibbonTTColumn ON (gibbonTTDay.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) JOIN gibbonTTColumnRow ON (gibbonTTColumn.gibbonTTColumnID=gibbonTTColumnRow.gibbonTTColumnID) WHERE gibbonTTDay.gibbonTTDayID=:gibbonTTDayID AND gibbonTT.gibbonTTID=:gibbonTTID AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonTTColumnRowID=:gibbonTTColumnRowID';
                $result = $connection2->prepare($sql);
                $result->execute($data);

            if ($result->rowCount() != 1) {
                echo "<div class='error'>";
                echo __('The specified record cannot be found.');
                echo '</div>';
            } else {
                $values = $result->fetch();

                $urlParams = ['gibbonTTDayID' => $gibbonTTDayID, 'gibbonTTID' => $gibbonTTID, 'gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonTTColumnRowID' => $gibbonTTColumnRowID];

                $page->breadcrumbs
                    ->add(__('Manage Timetables'), 'tt.php', $urlParams)
                    ->add(__('Edit Timetable'), 'tt_edit.php', $urlParams)
                    ->add(__('Edit Timetable Day'), 'tt_edit_day_edit.php', $urlParams)
                    ->add(__('Classes in Period'), 'tt_edit_day_edit_class.php', $urlParams)
                    ->add(__('Edit Class in Period'));

                if (isset($_GET['return'])) {
                    returnProcess($guid, $_GET['return'], null, null);
                }

                $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/tt_edit_day_edit_class_editProcess.php?&gibbonTTDayID=$gibbonTTDayID&gibbonTTID=$gibbonTTID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonTTColumnRowID=$gibbonTTColumnRowID&gibbonTTDayRowClassID=$gibbonTTDayRowClassID&gibbonCourseClassID=$gibbonCourseClassID");

                $form->addHiddenValue('address', $_SESSION[$guid]['address']);
                $form->addHiddenValue('gibbonTTID', $gibbonTTID);
                $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);

                $row = $form->addRow();
                    $row->addLabel('ttName', __('Timetable'));
                    $row->addTextField('ttName')->maxLength(20)->required()->readonly()->setValue($values['ttName']);

                $row = $form->addRow();
                    $row->addLabel('dayName', __('Day'));
                    $row->addTextField('dayName')->maxLength(20)->required()->readonly()->setValue($values['dayName']);

                $row = $form->addRow();
                    $row->addLabel('rowName', __('Period'));
                    $row->addTextField('rowName')->maxLength(20)->required()->readonly()->setValue($values['rowName']);

                $row = $form->addRow();
                    $row->addLabel('class', __('Class'));
                    $row->addTextField('class')->maxLength(20)->required()->readonly()->setValue($course.'.'.$class);

                $locations = array() ;
                
                    $dataSelect = array();
                    $sqlSelect = 'SELECT * FROM gibbonSpace ORDER BY name';
                    $resultSelect = $connection2->prepare($sqlSelect);
                    $resultSelect->execute($dataSelect);
                while ($rowSelect = $resultSelect->fetch()) {
                    
                        $dataUnique = array('gibbonTTDayID' => $gibbonTTDayID, 'gibbonTTColumnRowID' => $gibbonTTColumnRowID, 'gibbonSpaceID' => $rowSelect['gibbonSpaceID']);
                        $sqlUnique = 'SELECT * FROM gibbonTTDayRowClass WHERE gibbonTTDayID=:gibbonTTDayID AND gibbonTTColumnRowID=:gibbonTTColumnRowID AND gibbonSpaceID=:gibbonSpaceID';
                        $resultUnique = $connection2->prepare($sqlUnique);
                        $resultUnique->execute($dataUnique);
                    if ($resultUnique->rowCount() < 1 || $rowSelect['gibbonSpaceID'] == $gibbonSpaceID) {
                        $locations[$rowSelect['gibbonSpaceID']] = htmlPrep($rowSelect['name']);
                    }
                }
                $row = $form->addRow();
                    $row->addLabel('gibbonSpaceID', __('Location'));
                    $row->addSelect('gibbonSpaceID')->fromArray($locations)->placeholder()->selected($gibbonSpaceID);

                $row = $form->addRow();
                    $row->addFooter();
                    $row->addSubmit();

                echo $form->getOutput();
            }
        }
    }
}
