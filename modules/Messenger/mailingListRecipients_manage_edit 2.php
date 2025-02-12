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
use Gibbon\Domain\Messenger\MailingListRecipientGateway;

if (isActionAccessible($guid, $connection2, '/modules/Messenger/mailingListRecipients_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $gibbonMessengerMailingListRecipientID = $_GET['gibbonMessengerMailingListRecipientID'] ?? '';

    $page->breadcrumbs
        ->add(__m('Manage Mailing List Recipients'), 'mailingListRecipients_manage.php')
        ->add(__m('Edit Recipient'));

    if (empty($gibbonMessengerMailingListRecipientID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $values = $container->get(MailingListRecipientGateway::class)->getByID($gibbonMessengerMailingListRecipientID);
    
    if (empty($values)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $form = Form::create('category', $session->get('absoluteURL').'/modules/'.$session->get('module').'/mailingListRecipients_manage_editProcess.php');

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonMessengerMailingListRecipientID', $gibbonMessengerMailingListRecipientID);

    $row = $form->addRow();
		$row->addLabel('surname', __('Surname'));
		$row->addTextField('surname')->required()->maxLength(60);

	$row = $form->addRow();
		$row->addLabel('preferredName', __('Preferred Name'));
		$row->addTextField('preferredName')->required()->maxLength(60);

	$row = $form->addRow();
		$row->addLabel('email', __('Email'))->description(__('Must be unique.'));
		$row->addEmail('email')->required()->maxLength(75);
    
    $row = $form->addRow();
		$row->addLabel('organisation', __('Organisation'));
		$row->addTextField('organisation')->maxLength(60);
    
    $sql = "SELECT gibbonMessengerMailingListID as value, name FROM gibbonMessengerMailingList WHERE active='Y' ORDER BY name";
    $lists = $pdo->select($sql)->fetchKeyPair();
    if (count($lists) > 0) {
        $row = $form->addRow();
            $row->addLabel('gibbonMessengerMailingListIDList', __('Mailing Lists'));
            $row->addCheckbox('gibbonMessengerMailingListIDList')->fromArray($lists);
    }
    
    $values['gibbonMessengerMailingListIDList'] = explode(',', $values['gibbonMessengerMailingListIDList'] ?? ''); 

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    $form->loadAllValuesFrom($values);

    echo $form->getOutput();
}
