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

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/ttColumn_edit_row_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $gibbonTTColumnID = $_GET['gibbonTTColumnID'] ?? '';

    if ($gibbonTTColumnID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {

            $data = array('gibbonTTColumnID' => $gibbonTTColumnID);
            $sql = 'SELECT name AS columnName FROM gibbonTTColumn WHERE gibbonTTColumnID=:gibbonTTColumnID';
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            $page->addError(__('The specified record does not exist.'));
        } else {
            $values = $result->fetch();

            $page->breadcrumbs
                ->add(__('Manage Columns'), 'ttColumn.php')
                ->add(__('Edit Column'), 'ttColumn_edit.php', ['gibbonTTColumnID' => $gibbonTTColumnID])
                ->add(__('Add Column Row'));

            $editLink = '';
            if (isset($_GET['editID'])) {
                $editLink = $session->get('absoluteURL').'/index.php?q=/modules/Timetable Admin/ttColumn_edit_row_edit.php&gibbonTTColumnRowID='.$_GET['editID'].'&gibbonTTColumnID='.$_GET['gibbonTTColumnID'];
            }
            $page->return->setEditLink($editLink);


            $form = Form::create('action', $session->get('absoluteURL').'/modules/'.$session->get('module').'/ttColumn_edit_row_addProcess.php');

            $form->addHiddenValue('address', $session->get('address'));
            $form->addHiddenValue('gibbonTTColumnID', $gibbonTTColumnID);

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

            echo $form->getOutput();
        }
    }
}
