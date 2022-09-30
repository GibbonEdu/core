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
use Gibbon\Contracts\Comms\SMS;
use Gibbon\Services\Format;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\User\RoleGateway;
use Gibbon\Domain\Messenger\MessengerGateway;
use Gibbon\Module\Messenger\Forms\MessageForm;

require_once __DIR__ . '/moduleFunctions.php';

$page->breadcrumbs->add(__('New Message'));

if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php")==FALSE) {
    //Acess denied
    $page->addError(__("You do not have access to this action."));
}
else {
    if (!$session->has('email')) {
        $page->addError(__("You do not have a personal email address set in Gibbon, and so cannot send out emails."));
    } else {
        //Proceed!
        $settingGateway = $container->get(SettingGateway::class);

        $page->return->addReturns([
            'error4' => __('Your request was completed successfully, but some or all messages could not be delivered.'),
            'error5' => __('Your request failed due to an attachment error.'),
            'success1' => !empty($_GET['notification']) && $_GET['notification'] == 'Y'
                ? __("Your message has been dispatched to a team of highly trained gibbons for delivery: not all messages may arrive at their destination, but an attempt has been made to get them all out. You'll receive a notification once all messages have been sent.")
                : __('Your message has been posted successfully.'),
            'success2' => __('Your message has been saved as a draft. You can continue to edit and preview your message before sending.')
        ]);

        $deliverByEmail = isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_post.php', 'New Message_byEmail');
        $deliverByWall = isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_post.php', 'New Message_byMessageWall');
        $deliverBySMS = isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_post.php', 'New Message_bySMS');

        if (!$deliverByEmail && !$deliverByWall && !$deliverBySMS) {
            $page->addError(__('You do not have access to this action.'));
            return;
        }

        $page->addWarning(sprintf(__('Each family in Gibbon must have one parent who is contact priority 1, and who must be enabled to receive email and SMS messages from %1$s. As a result, when targetting parents, you can be fairly certain that messages should get through to each family.'), $session->get('organisationNameShort')));
        
        $form = $container->get(MessageForm::class)->createForm('messenger_postPreProcess.php');
        echo $form->getOutput();
    }
}

?>
<script>
function saveDraft() {
    $('option', '#individualList').each(function() {
        $(this).prop('selected', true);
    });

    var form = LiveValidationForm.getInstance(document.getElementById('messengerMessage'));
    if (LiveValidation.massValidate(form.fields)) {
        $('input[name="status"]').val('Draft');
        document.getElementById('messengerMessage').submit();
    }
}
</script>
