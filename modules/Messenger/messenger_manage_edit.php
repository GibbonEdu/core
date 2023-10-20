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

use Gibbon\Module\Messenger\Forms\MessageForm;
use Gibbon\Domain\Messenger\MessengerGateway;

if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_manage_edit.php")==FALSE) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
        return;
    }

    $search = $_GET['search'] ?? null;
    $messengerGateway = $container->get(MessengerGateway::class);

    $page->breadcrumbs
        ->add(__('Manage Messages'), 'messenger_manage.php', ['search' => $search])
        ->add(__('Edit Message'));

    $page->return->addReturns([
        'error5' => __('Your request failed due to an attachment error.'),
        'error6' => __('Your message is not ready to send because no targets have been selected or no valid recipients were found. Be sure to select at least one target for your message.'),
    ]);

    // Check if gibbonMessengerID specified
    $gibbonMessengerID = $_GET['gibbonMessengerID'] ?? '';
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

    // Confidential Check
    if ($values['confidential'] == 'Y' && $session->get('gibbonPersonID') != $values['gibbonPersonID']) {
        $page->addError(__('You do not have access to this action.'));
        return;
    }

    if ($values['status'] == 'Draft') {
        $page->addMessage('<b><u>'.__('Note').'</u></b>: '.__('This is a draft message, it has not been sent yet. You may continue editing the message contents below.'));
    } else {
        $page->addWarning('<b><u>'.__('Note').'</u></b>: '.__('Changes made here do not apply to emails and SMS messages (which have already been sent), but only to message wall messages.'));
    }
    
    $form = $container->get(MessageForm::class)->createForm('messenger_manage_editProcess.php', $gibbonMessengerID);
    echo $form->getOutput();
}
?>
<script>
function saveDraft() {
    $('option', '#individualList').each(function() {
        $(this).prop('selected', true);
    });

    var form = LiveValidationForm.getInstance(document.getElementById('messengerMessage'));
    if (LiveValidation.massValidate(form.fields)) {
        $('button[id="Save Draft"]').prop('disabled', true);
        setTimeout(function() {
            $('button[id="Save Draft"]').wrap('<span class="submitted"></span>');
        }, 500);
        $('input[name="saveMode"]').val('Draft');
        document.getElementById('messengerMessage').submit();
    }
}
</script>
