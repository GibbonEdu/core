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

$page->breadcrumbs
    ->add(__('Manage Canned Responses'), 'cannedResponse_manage.php')
    ->add(__('Edit Canned Response'));

if (isActionAccessible($guid, $connection2, '/modules/Messenger/cannedResponse_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    //Check if gibbonMessengerCannedResponseID specified
    $gibbonMessengerCannedResponseID = $_GET['gibbonMessengerCannedResponseID'] ?? '';
    if ($gibbonMessengerCannedResponseID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        
            $data = array('gibbonMessengerCannedResponseID' => $gibbonMessengerCannedResponseID);
            $sql = 'SELECT * FROM gibbonMessengerCannedResponse WHERE gibbonMessengerCannedResponseID=:gibbonMessengerCannedResponseID';
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            $page->addError(__('The specified record cannot be found.'));
        } else {
            //Let's go!
            $values = $result->fetch(); 
            
            $form = Form::create('canneResponse', $session->get('absoluteURL').'/modules/'.$session->get('module').'/cannedResponse_manage_editProcess.php?gibbonMessengerCannedResponseID='.$gibbonMessengerCannedResponseID);
                
            $form->addHiddenValue('address', $session->get('address'));

            $row = $form->addRow();
                $row->addLabel('subject', __('Subject'))->description(__('Must be unique.'));
                $row->addTextField('subject')->required()->maxLength(20);

            $row = $form->addRow();
                $col = $row->addColumn('body');
                $col->addLabel('body', __('Body'));
                $col->addEditor('body', $guid)->required()->setRows(20)->showMedia(true);

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            $form->loadAllValuesFrom($values);

            echo $form->getOutput();
        }
    }
}
