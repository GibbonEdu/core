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
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\DataUpdater\StaffUpdateGateway;

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Data Updater/data_staff.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        Format::alert(__('The highest grouped action cannot be determined.'));
        return;
    } 
    // Proceed!
    $page->breadcrumbs->add(__('Update Staff Data'));

    if ($highestAction == 'Update Staff Data_any') {
        echo '<p>';
        echo __('This page allows a user to request selected data updates for any staff record.');
        echo '</p>';
    } else {
        echo '<p>';
        echo __('This page allows a user to request data updates to their staff record.');
        echo '</p>';
    }

    $organisationInfo = '';
    if ($session->get('organisationDBAEmail') != '' and $session->get('organisationDBAName') != '') {
        $organisationInfo = ' '.sprintf(__('Please contact %1$s if you have any questions.'), "<a href='mailto:".$session->get('organisationDBAEmail')."'>".$session->get('organisationDBAName').'</a>');
    }

    $page->return->addReturns([
        'error3' => __('Your request was successful, but some data was not properly saved. An administrator will process your request as soon as possible. You will not see the updated data in the system until it has been processed.').$organisationInfo,
        'success0' => __('Your request was completed successfully. An administrator will process your request as soon as possible. You will not see the updated data in the system until it has been processed.').$organisationInfo,
    ]);

    $staffGateway = $container->get(StaffGateway::class);
    $staffUpdateGateway = $container->get(StaffUpdateGateway::class);

    if ($highestAction == 'Update Staff Data_any') {
        $gibbonPersonID = $_GET['gibbonPersonID'] ?? null;

        $criteria = $staffGateway->newQueryCriteria()
            ->sortBy(['surname', 'preferredName'])
            ->filterBy('status', 'Full');
        $allStaff = $staffGateway->queryAllStaff($criteria)->toArray();
        $allStaff = Format::nameListArray($allStaff, 'Staff', true, true);

        $form = Form::create('selectStaff', $session->get('absoluteURL').'/index.php', 'get');
        $form->setTitle(__('Choose Staff'));
        $form->addHiddenValue('q', '/modules/'.$session->get('module').'/data_staff.php');

        $row = $form->addRow();
            $row->addLabel('gibbonPersonID', __('Staff'));
            $row->addSelectPerson('gibbonPersonID')
                ->fromArray($allStaff)
                ->required()
                ->selected($gibbonPersonID)
                ->placeholder();

        $row = $form->addRow();
            $row->addSubmit();

        echo $form->getOutput();

    } else {
        $gibbonPersonID = $session->get('gibbonPersonID');
    }
    
    if (!empty($gibbonPersonID)) {

        $gibbonStaffID = $staffGateway->selectBy(['gibbonPersonID' => $gibbonPersonID], ['gibbonStaffID'])->fetchColumn(0);
        $values = $staffGateway->getByID($gibbonStaffID);

        if (empty($gibbonStaffID) || empty($values)) {
            echo Format::alert(__('The selected record does not exist, or you do not have access to it.'), 'error');
            return;
        }

        // Check access to person
        if ($highestAction == 'Update Staff Data_my' && $values['gibbonPersonID'] != $session->get('gibbonPersonID')) {
            echo Format::alert(__('The selected record does not exist, or you do not have access to it.'), 'error');
            return;
        }

        // Check if there is already a pending form for this user
        $existing = false;
        $proceed = false;

        $staffUpdate = $staffUpdateGateway->selectBy(['gibbonStaffID' => $gibbonStaffID, 'gibbonPersonIDUpdater' => $session->get('gibbonPersonID'), 'status' => 'Pending']);
            
        if ($staffUpdate->rowCount() > 1) {
            echo Format::alert(__('Your request failed due to a database error.'), 'error');
            return;
        } else if ($staffUpdate->rowCount() == 1) {
            echo Format::alert(__('You have already submitted a form, which is awaiting processing by an administrator. If you wish to make changes, please edit the data below, but remember your data will not appear in the system until it has been processed.'), 'warning');
            $values = $staffUpdate->fetch();
        }
               
        // Let's go
        $required = ($highestAction != 'Update Staff Data_any');

        $form = Form::create('updateStaff', $session->get('absoluteURL').'/modules/'.$session->get('module').'/data_staffProcess.php?gibbonStaffID='.$gibbonStaffID);
        $form->setTitle(__('Update Data'));
        $form->setFactory(DatabaseFormFactory::create($pdo));

        $form->addHiddenValue('address', $session->get('address'));
        $form->addHiddenValue('existing', !empty($values['gibbonStaffUpdateID'])? $values['gibbonStaffUpdateID'] : 'N');
        $form->addHiddenValue('gibbonStaffID', $gibbonStaffID);
        $form->addHiddenValue('gibbonPersonID', $gibbonPersonID);

        $form->addRow()->addHeading('Basic Information', __('Basic Information'));

        $row = $form->addRow();
            $row->addLabel('initials', __('Initials'))->description(__('Must be unique if set.'));
            $row->addTextField('initials')->maxlength(4);

        $types = array('Teaching' => __('Teaching'), 'Support' => __('Support'));
        $row = $form->addRow();
            $row->addLabel('type', __('Type'));
            $row->addSelect('type')->fromArray($types)->placeholder()->required($required);

        $row = $form->addRow();
            $row->addLabel('jobTitle', __('Job Title'));
            $row->addTextField('jobTitle')->maxlength(100);

        $form->addRow()->addHeading('First Aid', __('First Aid'));

        $row = $form->addRow();
            $row->addLabel('firstAidQualified', __('First Aid Qualified?'));
            $row->addYesNo('firstAidQualified')->placeHolder();

        $form->toggleVisibilityByClass('firstAid')->onSelect('firstAidQualified')->when('Y');

        $row = $form->addRow()->addClass('firstAid');
            $row->addLabel('firstAidQualification', __('First Aid Qualification'));
            $row->addTextField('firstAidQualification')->maxlength(100);

        $row = $form->addRow()->addClass('firstAid');
            $row->addLabel('firstAidExpiry', __('First Aid Expiry'));
            $row->addDate('firstAidExpiry');

        $form->addRow()->addHeading('Biography', __('Biography'));

        $row = $form->addRow();
            $row->addLabel('countryOfOrigin', __('Country Of Origin'));
            $row->addSelectCountry('countryOfOrigin')->placeHolder();

        $row = $form->addRow();
            $row->addLabel('qualifications', __('Qualifications'));
            $row->addTextField('qualifications')->maxlength(80);

        $row = $form->addRow();
            $row->addLabel('biography', __('Biography'));
            $row->addTextArea('biography')->setRows(10);

        // Custom Fields
        $container->get(CustomFieldHandler::class)->addCustomFieldsToForm($form, 'Staff', ['dataUpdater' => 1], $values['fields']);

        $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit();

        $form->loadAllValuesFrom($values);

        echo $form->getOutput();
    }
}
