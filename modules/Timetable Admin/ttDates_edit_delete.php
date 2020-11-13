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

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/ttDates_edit_delete.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Check if school year specified
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
    $dateStamp = $_GET['dateStamp'] ?? '';
    $gibbonTTDayID = $_GET['gibbonTTDayID'] ?? '';
    if ($gibbonSchoolYearID == '' or $dateStamp == '' or $gibbonTTDayID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        
            $data = array('date' => date('Y-m-d', $dateStamp), 'gibbonTTDayID' => $gibbonTTDayID);
            $sql = 'SELECT * FROM gibbonTTDayDate JOIN gibbonTTDay ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) JOIN gibbonTT ON (gibbonTTDay.gibbonTTID=gibbonTT.gibbonTTID) WHERE date=:date AND gibbonTTDay.gibbonTTDayID=:gibbonTTDayID';
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() < 1) {
            $page->addError(__('The specified record cannot be found.'));
        } else {
            //Let's go!
            $row = $result->fetch();

            //Proceed!
            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return'], null, null);
            }

            $form = DeleteForm::createForm($_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/ttDates_edit_deleteProcess.php?gibbonSchoolYearID=$gibbonSchoolYearID&dateStamp=$dateStamp&gibbonTTDayID=$gibbonTTDayID");
            echo $form->getOutput();
        }
    }
}
