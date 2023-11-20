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
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\Messenger\MessengerGateway;
use Gibbon\Domain\Messenger\MessengerReceiptGateway;
use Gibbon\Http\Url;
use Gibbon\Contracts\Comms\SMS;

require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_send.php') == FALSE) {
    //Acess denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $sendTestEmail = $_GET['sendTestEmail'] ?? 'N';
    $gibbonMessengerID = $_GET['gibbonMessengerID'] ?? '';
    $search = $_GET['search'] ?? '';

    $page->breadcrumbs
        ->add(__('Manage Messages'), 'messenger_manage.php', ['search' => $search])
        ->add(__('Edit Message'), 'messenger_manage_edit.php', ['gibbonMessengerID' => $gibbonMessengerID, 'sidebar' => true])
        ->add(__('Preview & Send'));

    $highestAction = getHighestGroupedAction($guid, '/modules/Messenger/messenger_send.php', $connection2) ;
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
        'error6' => __('Your message is not ready to send because no targets have been selected or no valid recipients were found. Be sure to select at least one target for your message.'),
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

    $sent = $values['status'] == 'Sent' || (!empty($_GET['return']) && $_GET['return'] == 'success1');

    // QUERY
    $criteria = $messengerReceiptGateway->newQueryCriteria()
        ->sortBy(['targetType', 'role',  'surname', 'preferredName'])
        ->fromPOST();
    $recipients = $messengerReceiptGateway->queryMessageRecipients($criteria, $gibbonMessengerID, $session->get('gibbonSchoolYearID'));

    // FORM
    $form = Form::create('messengerPreview', $session->get('absoluteURL').'/modules/Messenger/messenger_sendProcess.php');
    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonMessengerID', $gibbonMessengerID);
    $form->addClass('bulkActionForm');

    if ($values['sms'] == 'Y') {
        $smsAlert = __('SMS messages are sent to local and overseas numbers, but not all countries are supported. Please see the SMS Gateway provider\'s documentation or error log to see which countries are not supported. The subject does not get sent, and all HTML tags are removed. Each message, to each recipient, will incur a charge (dependent on your SMS gateway provider). Messages over 140 characters will get broken into smaller messages, and will cost more.');

        $sms = $container->get(SMS::class);
        if ($smsCredits = $sms->getCreditBalance()) {
            $smsAlert .= "<br/><br/><b>" . sprintf(__('Current balance: %1$s credit(s).'), $smsCredits) . "</u></b>" ;
            $form->addHiddenValue('smsCreditBalance', $smsCredits);
        }
        $form->addRow()->addAlert($smsAlert, 'error');

        $smsBody = stripslashes(strip_tags($values['body']));
        $form->addRow()->addContent(Format::bold(__('SMS Preview')).' ('.strlen($smsBody).' '.__('chars').'):<br/><br/>'.Format::alert($smsBody, 'exception'));
    }

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

    if (!$sent) {
        // If the message is not sent, let users manually select the recipients and click send
        $table->addCheckboxColumn('gibbonMessengerReceiptID')->checked(true);

        $form->addRow()->addHeading('Send', __('Send'));

        if ($values['email'] == 'Y') {
            $details = Format::listDetails([
                __('Email From') => $values['emailFrom'] ?? '',
                __('Reply To') => !empty($values['emailReplyTo']) ? $values['emailReplyTo'] : ($values['emailFrom'] ?? ''),
                __('Subject') => $values['subject'],
            ], 'ul', 'w-full text-left m-0');

            $row = $form->addRow();
            $row->addLabel('detailsLabel', __('Message Details'));
            $row->addContent($details);
        }

        $editURL = Url::fromModuleRoute('Messenger', 'messenger_manage_edit')->withQueryParams(['gibbonMessengerID' => $gibbonMessengerID, 'sidebar' => true]);

        $row = $form->addRow('stickySubmit');
            $col = $row->addColumn()->addClass('items-center');
            $col->addButton(__('Edit Draft'))->onClick('window.location="'.$editURL.'"')->addClass('email rounded-sm w-24 mr-2');
            $row->addSubmit(__('Send'));
    } else {
        // If the message is sent, display the message status
        $table->addColumn('status', __('Status'))
            ->format(function($values) {
                return $values['sent'] == 'Y' ? Format::tag(__('Sent'), 'success') : '';
            });
    }

    echo $form->getOutput();
}
