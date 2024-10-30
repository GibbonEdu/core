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

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/tt_edit_day_edit_class_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Check if gibbonTTDayID, gibbonTTID, gibbonSchoolYearID, and gibbonTTColumnRowID specified
    $gibbonTTDayID = $_GET['gibbonTTDayID'] ?? '';
    $gibbonTTID = $_GET['gibbonTTID'] ?? '';
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
    $gibbonTTColumnRowID = $_GET['gibbonTTColumnRowID'] ?? '';

    if ($gibbonTTDayID == '' or $gibbonTTID == '' or $gibbonSchoolYearID == '' or $gibbonTTColumnRowID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        
            $data = array('gibbonTTDayID' => $gibbonTTDayID, 'gibbonTTID' => $gibbonTTID, 'gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonTTColumnRowID' => $gibbonTTColumnRowID);
            $sql = 'SELECT gibbonTT.name AS ttName, gibbonTTDay.name AS dayName, gibbonTTColumnRow.name AS rowName, gibbonYearGroupIDList FROM gibbonTT JOIN gibbonTTDay ON (gibbonTT.gibbonTTID=gibbonTTDay.gibbonTTID) JOIN gibbonTTColumn ON (gibbonTTDay.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) JOIN gibbonTTColumnRow ON (gibbonTTColumn.gibbonTTColumnID=gibbonTTColumnRow.gibbonTTColumnID) WHERE gibbonTTDay.gibbonTTDayID=:gibbonTTDayID AND gibbonTT.gibbonTTID=:gibbonTTID AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonTTColumnRowID=:gibbonTTColumnRowID';
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            $page->addError(__('The specified record cannot be found.'));
        } else {
            //Let's go!
            $values = $result->fetch();

            $urlParams = ['gibbonTTDayID' => $gibbonTTDayID, 'gibbonTTID' => $gibbonTTID, 'gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonTTColumnRowID' => $gibbonTTColumnRowID];

            $page->breadcrumbs
                ->add(__('Manage Timetables'), 'tt.php', $urlParams)
                ->add(__('Edit Timetable'), 'tt_edit.php', $urlParams)
                ->add(__('Edit Timetable Day'), 'tt_edit_day_edit.php', $urlParams)
                ->add(__('Classes in Period'), 'tt_edit_day_edit_class.php', $urlParams)
                ->add(__('Add Class to Period'));

            $form = Form::create('action', $session->get('absoluteURL').'/modules/'.$session->get('module')."/tt_edit_day_edit_class_addProcess.php?&gibbonTTDayID=$gibbonTTDayID&gibbonTTID=$gibbonTTID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonTTColumnRowID=$gibbonTTColumnRowID");

            $form->addHiddenValue('address', $session->get('address'));
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
                        $dataSelect["year$i"] = '%'.$years[$i].'%';
                        $sqlSelectWhere = $sqlSelectWhere."(gibbonYearGroupIDList LIKE :year$i)";
                    }
                    $sqlSelectWhere = $sqlSelectWhere.')';
                }
                $sqlSelect = "SELECT gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID $sqlSelectWhere ORDER BY course, class";
                $resultSelect = $connection2->prepare($sqlSelect);
                $resultSelect->execute($dataSelect);
            } catch (PDOException $e) {}
            while ($rowSelect = $resultSelect->fetch()) {
                
                    $dataUnique = array('gibbonTTDayID' => $gibbonTTDayID, 'gibbonTTColumnRowID' => $gibbonTTColumnRowID, 'gibbonCourseClassID' => $rowSelect['gibbonCourseClassID']);
                    $sqlUnique = 'SELECT * FROM gibbonTTDayRowClass WHERE gibbonTTDayID=:gibbonTTDayID AND gibbonTTColumnRowID=:gibbonTTColumnRowID AND gibbonCourseClassID=:gibbonCourseClassID';
                    $resultUnique = $connection2->prepare($sqlUnique);
                    $resultUnique->execute($dataUnique);
                if ($resultUnique->rowCount() < 1) {
                    $classes[$rowSelect['gibbonCourseClassID']] = htmlPrep($rowSelect['course']).'.'.htmlPrep($rowSelect['class']);
                }
            }
            $row = $form->addRow();
                $row->addLabel('gibbonCourseClassID', __('Class'));
                $row->addSelect('gibbonCourseClassID')->fromArray($classes)->required()->placeholder();

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
