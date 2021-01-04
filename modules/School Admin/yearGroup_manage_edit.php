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
use Gibbon\Forms\DatabaseFormFactory;

if (isActionAccessible($guid, $connection2, '/modules/School Admin/yearGroup_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs
        ->add(__('Manage Year Groups'), 'yearGroup_manage.php')
        ->add(__('Edit Year Group'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Check if school year specified
    $gibbonYearGroupID = $_GET['gibbonYearGroupID'];
    if ($gibbonYearGroupID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        
            $data = array('gibbonYearGroupID' => $gibbonYearGroupID);
            $sql = 'SELECT * FROM gibbonYearGroup WHERE gibbonYearGroupID=:gibbonYearGroupID';
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            $page->addError(__('The specified record cannot be found.'));
        } else {
            //Let's go!
            $values = $result->fetch();

            $form = Form::create('yearGroup', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/yearGroup_manage_editProcess.php?gibbonYearGroupID='.$gibbonYearGroupID);
            $form->setFactory(DatabaseFormFactory::create($pdo));

            $form->addHiddenValue('address', $_SESSION[$guid]['address']);

            $row = $form->addRow();
                $row->addLabel('name', __('Name'))->description(__('Must be unique.'));
                $row->addTextField('name')->required()->maxLength(15)->setValue($values['name']);

            $row = $form->addRow();
                $row->addLabel('nameShort', __('Short Name'))->description(__('Must be unique.'));
                $row->addTextField('nameShort')->required()->maxLength(4)->setValue($values['nameShort']);

            $row = $form->addRow();
                $row->addLabel('sequenceNumber', __('Sequence Number'))->description(__('Must be unique. Controls chronological ordering.'));
                $row->addSequenceNumber('sequenceNumber', 'gibbonYearGroup', $values['sequenceNumber'])
                    ->required()
                    ->maxLength(3)
                    ->setValue($values['sequenceNumber']);
            
            $row = $form->addRow();
                $row->addLabel('gibbonPersonIDHOY', __('Head of Year'));
                $row->addSelectStaff('gibbonPersonIDHOY')->placeholder()->selected($values['gibbonPersonIDHOY']);

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            echo $form->getOutput();
        }
    }
}
