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
use Gibbon\Domain\User\UserGateway;
use Gibbon\Domain\Messenger\MessengerGateway;

require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_manage_report.php")==FALSE) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
}
else {
    $highestAction = getHighestGroupedAction($guid, '/modules/Messenger/messenger_manage_report.php', $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
        return;
    }

    if (!$session->has('email')) {
        $page->addError(__('You do not have a personal email address set in Gibbon, and so cannot send out emails.'));
        return;
    }

    $gibbonMessengerID = $_GET['gibbonMessengerID'] ?? null;
    
    $page->breadcrumbs
        ->add(__('Manage Messages'), 'messenger_manage.php')
        ->add(__('View Send Report'), 'messenger_manage_report.php&gibbonMessengerID='.$gibbonMessengerID.'&sidebar=true')
        ->add(__('Add Recipients'));

    $page->return->addReturns([
        'error5' => __('Your request failed due to an error.'),
    ]);

    // Proceed!
    $settingGateway = $container->get(SettingGateway::class);
    $messengerGateway = $container->get(MessengerGateway::class);
    
    $page->addWarning(sprintf(__('While adding new recipients, ensure each family in Gibbon must have one parent who is contact priority 1, and who must be enabled to receive email and SMS messages from %1$s.'), $session->get('organisationNameShort')));

    // Get the existing message data, if any
    $message = !empty($gibbonMessengerID) ? $messengerGateway->getByID($gibbonMessengerID) : [];
    $sent = !empty($message) && $message['status'] == 'Sent';

    // FORM
    $form = Form::create('addRecipients', $session->get('absoluteURL').'/modules/Messenger/messenger_manage_report_addRecipientsProcess.php');
    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonMessengerID', $gibbonMessengerID ?? '');

    $form->addRow()->addHeading('Add New Recipients', __('Add New Recipients'))
         ->append(__("Select new recipients to send this message."));

    // Individuals
    $row = $form->addRow();
        $row->addLabel('individuals', __('Individuals'))->description(__('Select specific individuals from the whole school.'));
        $row->addYesNoRadio('individuals')->checked('N')->required();

    $form->toggleVisibilityByClass('individuals')->onRadio('individuals')->when('Y');

    $userGateway = $container->get(UserGateway::class);
    $individuals = $userGateway->getIndividualsBySchoolYearWithFullStatus($session->get('gibbonSchoolYearID'))->fetchAll();

    $individuals = array_reduce($individuals, function ($group, $item) {
        $name = Format::name("", $item['preferredName'], $item['surname'], 'Student', true).' (';
        if (!empty($item['formGroupName'])) $name .= $item['formGroupName'].', ';
        $group[$item['gibbonPersonID']] = $name.$item['username'].', '.__($item['category']).')';
        return $group;
    }, []);

    $selected = [];
    $selectedIndividuals = array_intersect_key($individuals, array_flip($selected));

    $row = $form->addRow()->addClass('individuals bg-blue-50');
        $col = $row->addColumn();
        $col->addLabel('individualList', __('Select Individuals'));
        $select = $col->addMultiSelect('individualList')->required();
        $select->source()->fromArray($individuals);
        $select->destination()->fromArray($selectedIndividuals);
    
    $row = $form->addRow()->addClass('individuals bg-blue-50');
        $row->addLabel('individualsParents', __('Include Parents?'));
        $row->addYesNo('individualsParents')->selected('N');

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();
    
    echo $form->getOutput();
}
?>