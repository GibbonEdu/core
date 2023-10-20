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
use Gibbon\Contracts\Comms\SMS;
use Gibbon\Services\Format;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\User\RoleGateway;
use Gibbon\Domain\Messenger\MessengerGateway;
use Gibbon\Module\Messenger\Forms\MessageForm;

require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php")==FALSE) {
    //Acess denied
    $page->addError(__('You do not have access to this action.'));
}
else {
    $highestAction = getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
        return;
    }

    if (!$session->has('email')) {
        $page->addError(__('You do not have a personal email address set in Gibbon, and so cannot send out emails.'));
        return;
    }

    $page->breadcrumbs->add(__('New Message'));

    $page->return->addReturns([
        'error5' => __('Your request failed due to an attachment error.'),
    ]);

    //Proceed!
    $settingGateway = $container->get(SettingGateway::class);

    $deliverByEmail = isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_post.php', 'New Message_byEmail');
    $deliverByWall = isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_post.php', 'New Message_byMessageWall');
    $deliverBySMS = isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_post.php', 'New Message_bySMS');

    if (!$deliverByEmail && !$deliverByWall && !$deliverBySMS) {
        $page->addError(__('You do not have access to this action.'));
        return;
    }

    $page->addWarning(sprintf(__('Each family in Gibbon must have one parent who is contact priority 1, and who must be enabled to receive email and SMS messages from %1$s. As a result, when targetting parents, you can be fairly certain that messages should get through to each family.'), $session->get('organisationNameShort')));
    
    $form = $container->get(MessageForm::class)->createForm('messenger_postProcess.php');
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
