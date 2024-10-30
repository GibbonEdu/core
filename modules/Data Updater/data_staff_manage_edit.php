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
use Gibbon\Forms\CustomFieldHandler;
use Gibbon\Domain\Staff\StaffGateway;
use Gibbon\Domain\DataUpdater\StaffUpdateGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Data Updater/data_staff_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $gibbonSchoolYearID = isset($_REQUEST['gibbonSchoolYearID'])? $_REQUEST['gibbonSchoolYearID'] : $session->get('gibbonSchoolYearID');
    $urlParams = ['gibbonSchoolYearID' => $gibbonSchoolYearID];

    $page->breadcrumbs
        ->add(__('Staff Data Updates'), 'data_staff_manage.php', $urlParams)
        ->add(__('Edit Request'));

    $staffUpdateGateway = $container->get(StaffUpdateGateway::class);
    $staffGateway = $container->get(StaffGateway::class);

    // Check if required values are present
    $gibbonStaffUpdateID = $_GET['gibbonStaffUpdateID'] ?? '';
    if (empty($gibbonStaffUpdateID) || $gibbonStaffUpdateID == 'Y') {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $newValues = $staffUpdateGateway->getByID($gibbonStaffUpdateID);
    $oldValues = $staffGateway->getByID($newValues['gibbonStaffID'] ?? '');

    // Check database records exist
    if (empty($oldValues) || empty($newValues)) {
        $page->addError(__('The selected record does not exist, or you do not have access to it.'));
        return;
    }

    $compare = [
        'initials'              => __('Initials'),
        'type'                  => __('Type'),
        'jobTitle'              => __('Job Title'),
        'firstAidQualified'     => __('First Aid Qualified?'),
        'firstAidQualification' => __('First Aid Qualification'),
        'firstAidExpiry'        => __('First Aid Expiry'),
        'countryOfOrigin'       => __('Country Of Origin'),
        'qualifications'        => __('Qualifications'),
        'biography'             => __('Biography'),
    ];

    $form = Form::createTable('updateStaff', $session->get('absoluteURL').'/modules/'.$session->get('module').'/data_staff_manage_editProcess.php?gibbonStaffUpdateID='.$gibbonStaffUpdateID);

    $form->setClass('fullWidth colorOddEven');
    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonStaffID', $oldValues['gibbonStaffID']);

    // Provide links back to edit the associated records
    if (isActionAccessible($guid, $connection2, '/modules/User Admin/user_manage_edit.php') == true) {
        $page->navigator->addHeaderAction('edit', __('Edit User'))
            ->setURL('/modules/User Admin/user_manage_edit.php')
            ->addParam('gibbonPersonID', $oldValues['gibbonPersonID'])
            ->setIcon('config')
            ->displayLabel();
    }
    
    if (isActionAccessible($guid, $connection2, '/modules/Staff/staff_manage_edit.php') == true) {
        $page->navigator->addHeaderAction('editStaff', __('Edit Staff'))
            ->setURL('/modules/Staff/staff_manage_edit.php')
            ->addParam('gibbonStaffID', $oldValues['gibbonStaffID'])
            ->setIcon('config')
            ->displayLabel();
    }

    if (isActionAccessible($guid, $connection2, '/modules/Staff/staff_view_details.php') == true) {
        $page->navigator->addHeaderAction('view', __('View Staff'))
            ->setURL('/modules/Staff/staff_view_details.php')
            ->addParam('gibbonPersonID', $oldValues['gibbonPersonID'])
            ->addParam('gibbonStaffID', $oldValues['gibbonStaffID'])
            ->setIcon('plus')
            ->displayLabel();    
     }

    $row = $form->addRow()->setClass('head heading');
        $row->addContent(__('Field'));
        $row->addContent(__('Current Value'));
        $row->addContent(__('New Value'));
        $row->addContent(__('Accept'));

    $changeCount = 0;
    foreach ($compare as $fieldName => $label) {
        $oldValue = isset($oldValues[$fieldName])? $oldValues[$fieldName] : '';
        $newValue = isset($newValues[$fieldName])? $newValues[$fieldName] : '';
        $isNotMatching = ($oldValue != $newValue);

        if ($fieldName == 'firstAidExpiry') {
            $oldValue = Format::date($oldValue);
            $newValue = Format::date($newValue);
        }

        $row = $form->addRow();
            $row->addLabel('new'.$fieldName.'On', $label);
            $row->addContent($oldValue);
            $row->addContent($newValue)->addClass($isNotMatching ? 'matchHighlightText' : '');

        if ($isNotMatching) {
            $row->addCheckbox('new'.$fieldName.'On')->checked(true)->setClass('textCenter');
            $form->addHiddenValue('new'.$fieldName, $newValues[$fieldName] ?? '');
            $changeCount++;
        } else {
            $row->addContent();
        }
    }

    // CUSTOM FIELDS
    $changeCount += $container->get(CustomFieldHandler::class)->addCustomFieldsToDataUpdate($form, 'Staff', ['dataUpdater' => 1], $oldValues, $newValues);

    $row = $form->addRow();
        $row->addSubmit();

    echo $form->getOutput();
}
