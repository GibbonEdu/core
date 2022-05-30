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

use Gibbon\Forms\Prefab\DeleteForm;
use Gibbon\Domain\Admissions\AdmissionsApplicationGateway;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;

if (isActionAccessible($guid, $connection2, '/modules/Admissions/applications_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('Manage Applications'), 'applications_manage.php')
        ->add(__('Reject Application'));

    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');
    $gibbonAdmissionsApplicationID = $_GET['gibbonAdmissionsApplicationID'] ?? '';
    $search = $_GET['search'] ?? '';
    
    if (empty($gibbonAdmissionsApplicationID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $application = $container->get(AdmissionsApplicationGateway::class)->getByID($gibbonAdmissionsApplicationID);

    if (empty($application)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $values = json_decode($application['data'], true);

    $form = Form::create('application', $session->get('absoluteURL').'/modules/Admissions/applications_manage_rejectProcess.php');

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);
    $form->addHiddenValue('gibbonAdmissionsApplicationID', $gibbonAdmissionsApplicationID);
    $form->addHiddenValue('search', $search);

    $row = $form->addRow();
        $row->addContent(sprintf(__('Are you sure you want to reject the application for %1$s?'), Format::name('', $values['preferredName'], $values['surname'], 'Student')));

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit(__('Yes'));

    echo $form->getOutput();
}
