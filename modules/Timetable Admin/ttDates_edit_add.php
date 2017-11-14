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

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/ttDates_edit_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
    $dateStamp = $_GET['dateStamp'];

    if ($gibbonSchoolYearID == '' or $dateStamp == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        if (isSchoolOpen($guid, date('Y-m-d', $dateStamp), $connection2, true) != true) {
            echo "<div class='error'>";
            echo __($guid, 'School is not open on the specified day.');
            echo '</div>';
        } else {
            try {
                $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
                $sql = 'SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($result->rowCount() != 1) {
                echo "<div class='error'>";
                echo __($guid, 'The specified record does not exist.');
                echo '</div>';
            } else {
                $values = $result->fetch();

                //Proceed!
                echo "<div class='trail'>";
                echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/ttDates.php&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID']."'>".__($guid, 'Tie Days to Dates')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/ttDates_edit.php&gibbonSchoolYearID=$gibbonSchoolYearID&dateStamp=$dateStamp'>".__($guid, 'Edit Days in Date')."</a> > </div><div class='trailEnd'>".__($guid, 'Add Day to Date').'</div>';
                echo '</div>';

                if (isset($_GET['return'])) {
                    returnProcess($guid, $_GET['return'], null, null);
				}
				
				$form = Form::create('addTTDate', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/ttDates_edit_addProcess.php');
				
				$form->addHiddenValue('address', $_SESSION[$guid]['address']);
				$form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);
				$form->addHiddenValue('dateStamp', $dateStamp);
				
				$row = $form->addRow();
					$row->addLabel('schoolYearName', __('School Year'));
					$row->addTextField('schoolYearName')->readonly()->setValue($values['name']);

				$row = $form->addRow();
                    $row->addLabel('dateName', __('Date'));
					$row->addTextField('dateName')->readonly()->setValue(date('d/m/Y l', $dateStamp));
					
				$data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'date' => date('Y-m-d', $dateStamp));
				$sql = "SELECT gibbonTTDay.gibbonTTDayID as value, CONCAT(gibbonTT.name, ': ', gibbonTTDay.nameShort) as name
						FROM gibbonTT 
						JOIN gibbonTTDay ON (gibbonTTDay.gibbonTTID=gibbonTT.gibbonTTID)
						LEFT JOIN (SELECT gibbonTTDay.gibbonTTID, gibbonTTDayDate.date
                        	FROM gibbonTTDay
                        	JOIN gibbonTTDayDate ON (gibbonTTDay.gibbonTTDayID=gibbonTTDayDate.gibbonTTDayID)
                        ) AS dateCheck ON (dateCheck.gibbonTTID=gibbonTT.gibbonTTID AND dateCheck.date=:date)
						WHERE gibbonTT.gibbonSchoolYearID=:gibbonSchoolYearID 
						AND dateCheck.gibbonTTID IS NULL
						ORDER BY name";

				$row = $form->addRow();
                    $row->addLabel('gibbonTTDayID', __('Day'));
                    $row->addSelect('gibbonTTDayID')->fromQuery($pdo, $sql, $data)->isRequired();
				
				$row = $form->addRow();
					$row->addFooter();
					$row->addSubmit();
				
				echo $form->getOutput();
            }
        }
    }
}
?>