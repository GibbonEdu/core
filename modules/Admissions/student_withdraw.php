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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;

if (isActionAccessible($guid, $connection2, '/modules/Admissions/student_withdraw.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs->add(__('Withdraw Student'));

    $form = Form::create('studentWithdraw', $session->get('absoluteURL').'/modules/Admissions/student_withdrawProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $session->get('address'));

    $form->addRow()->addHeading('Basic Information', __('Basic Information'));

    $row = $form->addRow();
        $row->addLabel('gibbonPersonID', __('Student'));
        $row->addSelectStudent('gibbonPersonID', $session->get('gibbonSchoolYearID'), ['showForm' => true])->required()->placeholder();

    $row = $form->addRow();
        $row->addLabel('status', __('Status'))->description(__("Set this to Left unless the student's withdraw date is in the future."));
        $row->addSelect('status')->fromArray(['Left' => __('Left'), 'Full' => __('Full')])->required()->selected('Left');

    $row = $form->addRow();
        $row->addLabel('dateEnd', __('End Date'))->description(__("Users's last day at school."));
        $row->addDate('dateEnd')->required();

    $departureReasonsList = $container->get(SettingGateway::class)->getSettingByScope('User Admin', 'departureReasons');
    $row = $form->addRow();
        $row->addLabel('departureReason', __('Departure Reason'));
        if (!empty($departureReasonsList)) {
            $row->addSelect('departureReason')->fromString($departureReasonsList)->required()->placeholder();
        } else {
            $row->addTextField('departureReason')->maxLength(100)->required();
        }

    $schools = $pdo->select("SELECT DISTINCT nextSchool FROM gibbonPerson ORDER BY lastSchool")->fetchAll(\PDO::FETCH_COLUMN);
    $row = $form->addRow();
        $row->addLabel('nextSchool', __('Next School'));
        $row->addTextField('nextSchool')->maxLength(100)->autocomplete($schools);

    // NOTES
    $form->addRow()->addHeading('Notes', __('Notes'));

    $col = $form->addRow()->addColumn();
        $col->addLabel('withdrawNote', __('Withdraw Note'))->description(__('If provided, these will be saved in student notes, as well as shared with notification recipients.'));
        $col->addTextArea('withdrawNote');

    // NOTIFICATIONS
    $form->addRow()->addHeading('Notifications', __('Notifications'));

    $row = $form->addRow();
        $row->addLabel('notify', __('Automatically Notify'));
        $row->addCheckbox('notify')->fromArray([
            'admin'    => __('Admissions Administrator'),
            'HOY'      => __('Head of Year'),
            'tutors'   => __('Form Tutors'),
            'teachers' => __('Class Teachers'),
            'EAs'      => __('Educational Assistants'),
        ])->checkAll();

    $row = $form->addRow();
        $row->addLabel('notificationList', __('Notify Additional People'));
        $row->addFinder('notificationList')
            ->fromAjax($session->get('absoluteURL').'/modules/Staff/staff_searchAjax.php')
            ->setParameter('resultsLimit', 10)
            ->resultsFormatter('function(item){ return "<li class=\'\'><div class=\'inline-block bg-cover w-12 h-12 ml-2 rounded-full bg-gray-200 border border-gray-400 bg-no-repeat\' style=\'background-image: url(" + item.image + ");\'></div><div class=\'inline-block px-4 truncate\'>" + item.name + "<br/><span class=\'inline-block opacity-75 truncate text-xxs\'>" + item.jobTitle + "</span></div></li>"; }');

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
