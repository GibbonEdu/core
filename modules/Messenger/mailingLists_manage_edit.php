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
use Gibbon\Domain\Messenger\MailingListGateway;

if (isActionAccessible($guid, $connection2, '/modules/Messenger/mailingLists_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $gibbonMessengerMailingListID = $_GET['gibbonMessengerMailingListID'] ?? '';

    $page->breadcrumbs
        ->add(__m('Manage Mailing Lists'), 'mailingLists_manage.php')
        ->add(__m('Edit '));

    if (empty($gibbonMessengerMailingListID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $values = $container->get(MailingListGateway::class)->getByID($gibbonMessengerMailingListID);

    if (empty($values)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $form = Form::create('category', $session->get('absoluteURL').'/modules/'.$session->get('module').'/mailingLists_manage_editProcess.php');

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonMessengerMailingListID', $gibbonMessengerMailingListID);

    $row = $form->addRow();
		$row->addLabel('name', __('Name'))->description(__('Must be unique.'));
		$row->addTextField('name')->required()->maxLength(60);
	
	$row = $form->addRow();
        $row->addLabel('active', __('Active'));
        $row->addYesNo('active')->required();

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    $form->loadAllValuesFrom($values);

    echo $form->getOutput();
}
