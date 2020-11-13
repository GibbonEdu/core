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

if (isActionAccessible($guid, $connection2, '/modules/User Admin/studentsSettings_noteCategory_edit.php') == false) {
    //Access denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    $page->breadcrumbs
        ->add(__('Students Settings'), 'studentsSettings.php')
        ->add(__('Edit Note Category'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }
    //Check if school year specified
    $gibbonStudentNoteCategoryID = $_GET['gibbonStudentNoteCategoryID'];
    if ($gibbonStudentNoteCategoryID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        
            $data = array('gibbonStudentNoteCategoryID' => $gibbonStudentNoteCategoryID);
            $sql = 'SELECT * FROM gibbonStudentNoteCategory WHERE gibbonStudentNoteCategoryID=:gibbonStudentNoteCategoryID';
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            $page->addError(__('The specified record cannot be found.'));
        } else {
            //Let's go!
            $values = $result->fetch();

            $form = Form::create('noteCategory', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/studentsSettings_noteCategory_editProcess.php?gibbonStudentNoteCategoryID='.$gibbonStudentNoteCategoryID);

            $form->addHiddenValue('address', $_SESSION[$guid]['address']);

            $row = $form->addRow();
                $row->addLabel('name', __('Name'))->description(__('Must be unique.'));
                $row->addTextField('name')->required()->maxLength(30);

            $row = $form->addRow();
                $row->addLabel('active', __('Active'));
                $row->addYesNo('active')->required();

            $row = $form->addRow();
                $row->addLabel('template', __('Template'))->description(__('HTML code to be inserted into blank note.'));
                $row->addTextArea('template')->setRows(8);

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            $form->loadAllValuesFrom($values);

            echo $form->getOutput();
        }
    }
}
