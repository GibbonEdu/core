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
use Gibbon\Services\Format;
use Gibbon\Forms\DatabaseFormFactory;

if (isActionAccessible($guid, $connection2, '/modules/School Admin/schoolYearTerm_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $page->breadcrumbs
        ->add(__('Manage Terms'), 'schoolYearTerm_manage.php')
        ->add(__('Edit Term'));

    //Check if gibbonSchoolYearTermID specified
    $gibbonSchoolYearTermID = $_GET['gibbonSchoolYearTermID'] ?? '';
    if ($gibbonSchoolYearTermID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        
            $data = array('gibbonSchoolYearTermID' => $gibbonSchoolYearTermID);
            $sql = 'SELECT * FROM gibbonSchoolYearTerm WHERE gibbonSchoolYearTermID=:gibbonSchoolYearTermID';
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            $page->addError(__('The specified record cannot be found.'));
        } else {
            //Let's go!
            $values = $result->fetch();

            $form = Form::create('schoolYearTerm', $session->get('absoluteURL').'/modules/'.$session->get('module').'/schoolYearTerm_manage_editProcess.php?gibbonSchoolYearTermID='.$gibbonSchoolYearTermID);
		    $form->setFactory(DatabaseFormFactory::create($pdo));

		    $form->addHiddenValue('address', $session->get('address'));

		    $row = $form->addRow();
		        $row->addLabel('gibbonSchoolYearID', __('School Year'));
		        $row->addSelectSchoolYear('gibbonSchoolYearID')->required()->selected($values['gibbonSchoolYearID']);

		    $row = $form->addRow();
		        $row->addLabel('sequenceNumber', __('Sequence Number'))->description(__('Must be unique. Controls chronological ordering.'));
		        $row->addSequenceNumber('sequenceNumber', 'gibbonSchoolYearTerm', $values['sequenceNumber'])
		        	->required()
		        	->maxLength(3)
		        	->setValue($values['sequenceNumber']);

		    $row = $form->addRow();
		        $row->addLabel('name', __('Name'));
		        $row->addTextField('name')->required()->maxLength(20)->setValue($values['name']);

		    $row = $form->addRow();
		        $row->addLabel('nameShort', __('Short Name'));
		        $row->addTextField('nameShort')->required()->maxLength(4)->setValue($values['nameShort']);

		    $row = $form->addRow();
		        $row->addLabel('firstDay', __('First Day'));
		        $row->addDate('firstDay')->required()->setValue(Format::date($values['firstDay']));

		    $row = $form->addRow();
		        $row->addLabel('lastDay', __('Last Day'));
		        $row->addDate('lastDay')->required()->setValue(Format::date($values['lastDay']));

		    $row = $form->addRow();
		        $row->addFooter();
		        $row->addSubmit();

		    echo $form->getOutput();
        }
    }
}
