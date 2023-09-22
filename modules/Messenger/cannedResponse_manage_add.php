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
    ->add(__('Add Canned Response'));

if (isActionAccessible($guid, $connection2, '/modules/Messenger/cannedResponse_manage_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $session->get('absoluteURL').'/index.php?q=/modules/Messenger/cannedResponse_manage_edit.php&gibbonMessengerCannedResponseID='.$_GET['editID'];
    }
    $page->return->setEditLink($editLink);

	
	$form = Form::create('canneResponse', $session->get('absoluteURL').'/modules/'.$session->get('module').'/cannedResponse_manage_addProcess.php');
                
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

	echo $form->getOutput();
}
