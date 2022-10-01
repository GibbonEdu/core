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
use Gibbon\Services\Format;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\Messenger\MessengerGateway;
use Gibbon\Domain\Messenger\MessengerReceiptGateway;

require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_post.php') == FALSE) {
    //Acess denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $sendTestEmail = $_GET['sendTestEmail'] ?? 'N';
    $gibbonMessengerID = $_GET['gibbonMessengerID'] ?? '';

    $page->breadcrumbs
        ->add(__('Manage Messages'), 'messenger_manage.php', ['search' => $search])
        ->add(__('Edit Message'), 'messenger_manage_edit.php', ['gibbonMessengerID' => $gibbonMessengerID, 'sidebar' => true])
        ->add(__('Preview & Send'));

    $highestAction = getHighestGroupedAction($guid, '/modules/Messenger/messenger_post.php', $connection2) ;
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
        return;
    }

    if (!$session->has('email')) {
        $page->addError(__('You do not have a personal email address set in Gibbon, and so cannot send out emails.'));
        return;
    }
    //Proceed!
    $settingGateway = $container->get(SettingGateway::class);
    $messengerGateway = $container->get(MessengerGateway::class);
    $messengerReceiptGateway = $container->get(MessengerReceiptGateway::class);

    $page->return->addReturns([
        'error4' => __('Your request was completed successfully, but some or all messages could not be delivered.'),
        'error5' => __('Your request failed due to an attachment error.'),
        'error6' => __('Your message is not ready to preview and send because no targets have been selected.'),
        'success1' => !empty($_GET['notification']) && $_GET['notification'] == 'Y'
            ? __("Your message has been dispatched to a team of highly trained gibbons for delivery: not all messages may arrive at their destination, but an attempt has been made to get them all out. You'll receive a notification once all messages have been sent.")
            : __('Your message has been posted successfully.'),
    ]);

    // Check if gibbonMessengerID specified
    if (empty($gibbonMessengerID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $values = $highestAction == 'Manage Messages_all'
        ? $messengerGateway->getMessageDetailsByID($gibbonMessengerID)
        : $messengerGateway->getMessageDetailsByIDAndOwner($gibbonMessengerID, $session->get('gibbonPersonID'));

    if (empty($values)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $sent = !empty($_GET['return']) || $values['status'] == 'Sent';

    // QUERY
    $criteria = $messengerReceiptGateway->newQueryCriteria()->fromPOST();
    $recipients = $messengerReceiptGateway->queryMessageRecipients($criteria, $gibbonMessengerID, $session->get('gibbonSchoolYearID'));

    // FORM
    $form = Form::create('messengerPreview', $session->get('absoluteURL').'/modules/Messenger/messenger_postPreviewProcess.php');
    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonMessengerID', $gibbonMessengerID);
    $form->addClass('bulkActionForm');

    $form->addRow()->addHeading('Recipients', __('Recipients'));

    // DATA TABLE
    $table = $form->addRow()->addDataTable('recipients', $criteria)->withData($recipients);
    $table->addMetaData('hidePagination', true);

    $table->addColumn('targetType', __('Target'));
    $table->addColumn('fullName', __('Recipient'))
        ->context('primary')
        ->width('30%')
        ->sortable(['surname', 'preferredName'])
        ->format(function($values) {
            $name = Format::name($values['title'], $values['preferredName'], $values['surname'], 'Student', true);
            if (!empty($values['formGroup'])) {
                $name .= ' ('.$values['formGroup'].')';
            }
            return $name;
        });

    $table->addColumn('role', __('Role'));
    $table->addColumn('contactType', __('Contact Type'));
    $table->addColumn('contactDetail', __('Contact Detail'));

    if (empty($_GET['return']) && $values['status'] != 'Sent') {
        $table->addCheckboxColumn('gibbonMessengerReceiptID')->checked(true);

        $form->addRow()->addHeading('Send', __('Send'));

        if ($values['email'] == 'Y') {
            $details = Format::listDetails([
                __('Email From') => $values['emailFrom'] ?? '',
                __('Reply To') => $values['emailReplyTo'] ?? '',
                __('Subject') => $values['subject'],
            ], 'ul', 'w-full text-left m-0');

            $row = $form->addRow();
            $row->addLabel('detailsLabel', __('Message Details'));
            $row->addContent($details);
        }

        $row = $form->addRow('submit');
            $col = $row->addColumn()->addClass('items-center');
            $col->addButton(__('Edit Draft'))->onClick('window.location="'.$session->get('absoluteURL').'index.php?q=/modules/Messenger/messenger_manage_edit.php&sidebar=true&gibbonMessengerID='.$gibbonMessengerID.'"')->addClass('email rounded-sm w-24 mr-2');
            $row->addSubmit(__('Send'));
    } else {

        $table->addColumn('status', __('Status'))
            ->format(function($values) {
                return $values['sent'] == 'Y' ? Format::tag(__('Sent'), 'success') : '';
            });
    }

    echo $form->getOutput();
    
}
