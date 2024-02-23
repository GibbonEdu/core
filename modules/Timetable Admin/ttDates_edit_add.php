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

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/ttDates_edit_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
    $dateStamp = $_GET['dateStamp'] ?? '';

    if ($gibbonSchoolYearID == '' or $dateStamp == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        if (isSchoolOpen($guid, date('Y-m-d', $dateStamp), $connection2, true) != true) {
            echo "<div class='error'>";
            echo __('School is not open on the specified day.');
            echo '</div>';
        } else {
            
                $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
                $sql = 'SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID';
                $result = $connection2->prepare($sql);
                $result->execute($data);

            if ($result->rowCount() != 1) {
                $page->addError(__('The specified record does not exist.'));
            } else {
                $values = $result->fetch();

                //Proceed!
                $page->breadcrumbs
                    ->add(__('Tie Days to Dates'), 'ttDates.php', ['gibbonSchoolYearID' => $gibbonSchoolYearID])
                    ->add(__('Edit Days in Date'), 'ttDates_edit.php', ['gibbonSchoolYearID' => $gibbonSchoolYearID, 'dateStamp' => $dateStamp])
                    ->add(__('Add Day to Date'));

				$form = Form::create('addTTDate', $session->get('absoluteURL').'/modules/'.$session->get('module').'/ttDates_edit_addProcess.php');

				$form->addHiddenValue('address', $session->get('address'));
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
                    $row->addSelect('gibbonTTDayID')->fromQuery($pdo, $sql, $data)->required()->placeholder();

				$row = $form->addRow();
					$row->addFooter();
					$row->addSubmit();

				echo $form->getOutput();
            }
        }
    }
}
