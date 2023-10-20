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

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/ttColumn_edit_row_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Check if gibbonTTColumnRowID and gibbonTTColumnID specified
    $gibbonTTColumnRowID = $_GET['gibbonTTColumnRowID'] ?? '';
    $gibbonTTColumnID = $_GET['gibbonTTColumnID'] ?? '';
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
    if ($gibbonTTColumnRowID == '' or $gibbonTTColumnID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {

            $data = array('gibbonTTColumnID' => $gibbonTTColumnID, 'gibbonTTColumnRowID' => $gibbonTTColumnRowID);
            $sql = 'SELECT gibbonTTColumnRow.*, gibbonTTColumn.name AS columnName FROM gibbonTTColumn JOIN gibbonTTColumnRow ON (gibbonTTColumn.gibbonTTColumnID=gibbonTTColumnRow.gibbonTTColumnID) WHERE gibbonTTColumnRow.gibbonTTColumnID=:gibbonTTColumnID AND gibbonTTColumnRow.gibbonTTColumnRowID=:gibbonTTColumnRowID';
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            $page->addError(__('The specified record cannot be found.'));
        } else {
            //Let's go!
            $values = $result->fetch();

            $page->breadcrumbs
                ->add(__('Manage Columns'), 'ttColumn.php')
                ->add(__('Edit Column'), 'ttColumn_edit.php', ['gibbonTTColumnID' => $gibbonTTColumnID])
                ->add(__('Edit Column Row'));

            $form = Form::create('action', $session->get('absoluteURL').'/modules/'.$session->get('module').'/ttColumn_edit_row_editProcess.php');

            $form->addHiddenValue('address', $session->get('address'));
            $form->addHiddenValue('gibbonTTColumnID', $gibbonTTColumnID);
            $form->addHiddenValue('gibbonTTColumnRowID', $gibbonTTColumnRowID);

            $row = $form->addRow();
                $row->addLabel('columnName', __('Column'));
                $row->addTextField('columnName')->maxLength(30)->required()->readonly()->setValue($values['columnName']);

            $row = $form->addRow();
                $row->addLabel('name', __('Name'))->description(__('Must be unique for this column.'));
                $row->addTextField('name')->maxLength(12)->required();

            $row = $form->addRow();
                $row->addLabel('nameShort', __('Short Name'))->description(__('Must be unique for this column.'));
                $row->addTextField('nameShort')->maxLength(4)->required();

            $row = $form->addRow();
                $row->addLabel('timeStart', __('Start Time'));
                $row->addTime('timeStart')->required();

            $row = $form->addRow();
                $row->addLabel('timeEnd', __('End Time'));
                $row->addTime('timeEnd')->required()->chainedTo('timeStart');

            $types = array(
                'Lesson' => __('Lesson'),
                'Pastoral' => __('Pastoral'),
                'Sport' => __('Sport'),
                'Break' => __('Break'),
                'Service' => __('Service'),
                'Other' => __('Other'));
            $row = $form->addRow();
                $row->addLabel('type', __('Type'));
                $row->addSelect('type')->fromArray($types)->required();

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            $form->loadAllValuesFrom($values);

            echo $form->getOutput();
        }
    }
}
