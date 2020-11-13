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

use Gibbon\Forms\Prefab\DeleteForm;

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/tt_edit_day_edit_class_delete.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Check if school year specified
    $gibbonTTDayID = $_GET['gibbonTTDayID'];
    $gibbonTTID = $_GET['gibbonTTID'];
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
    $gibbonTTColumnRowID = $_GET['gibbonTTColumnRowID'];
    $gibbonCourseClassID = $_GET['gibbonCourseClassID'];

    if ($gibbonTTDayID == '' or $gibbonTTID == '' or $gibbonSchoolYearID == '' or $gibbonTTColumnRowID == '' or $gibbonCourseClassID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        
            $data = array('gibbonTTColumnRowID' => $gibbonTTColumnRowID, 'gibbonTTDayID' => $gibbonTTDayID, 'gibbonTTColumnRowID' => $gibbonTTColumnRowID, 'gibbonCourseClassID' => $gibbonCourseClassID);
            $sql = 'SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonTTDayRowClassID FROM gibbonTTDayRowClass JOIN gibbonCourseClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonTTColumnRowID=:gibbonTTColumnRowID AND gibbonTTDayID=:gibbonTTDayID AND gibbonTTColumnRowID=:gibbonTTColumnRowID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID';
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() < 1) {
            $page->addError(__('The specified record cannot be found.'));
        } else {
            //Let's go!
            $row = $result->fetch();
            $gibbonTTDayRowClassID = $row['gibbonTTDayRowClassID'];

            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return'], null, null);
            }

            $form = DeleteForm::createForm($_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/tt_edit_day_edit_class_deleteProcess.php?&gibbonTTDayID=$gibbonTTDayID&gibbonTTID=$gibbonTTID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonTTColumnRowID=$gibbonTTColumnRowID&gibbonTTDayRowClassID=$gibbonTTDayRowClassID&gibbonCourseClassID=$gibbonCourseClassID");
            echo $form->getOutput();
        }
    }
}
