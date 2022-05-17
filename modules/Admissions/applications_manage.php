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
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\Admissions\AdmissionsApplicationGateway;

if (isActionAccessible($guid, $connection2, '/modules/Admissions/applications_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs->add(__('Manage Applications'));

    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');
    $gibbonAdmissionsAccountID = $_GET['gibbonAdmissionsAccountID'] ?? '';
    $search = $_GET['search'] ?? '';

    $page->navigator->addSchoolYearNavigation($gibbonSchoolYearID);

    // SEARCH
    $form = Form::create('searchForm', $session->get('absoluteURL').'/index.php','get');
    $form->setTitle(__('Search'));
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q', '/modules/'.$session->get('module').'/applications_manage.php');
    $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);

    $row = $form->addRow();
        $row->addLabel('search', __('Search For'))->description();
        $row->addTextField('search')->setValue($search);

    $row = $form->addRow();
        $row->addSearchSubmit($gibbon->session, __('Clear Search'), ['gibbonSchoolYearID']);

    echo $form->getOutput();

    // QUERY
    $admissionsApplicationGateway = $container->get(AdmissionsApplicationGateway::class);
    $criteria = $admissionsApplicationGateway->newQueryCriteria(true)
        ->sortBy('timestampCreated', 'DESC')
        ->filterBy('admissionsAccount', $gibbonAdmissionsAccountID)
        ->fromPOST();

    $submissions = $admissionsApplicationGateway->queryApplicationsBySchoolYear($criteria, $gibbonSchoolYearID, 'Application');

    // DATA TABLE
    $table = DataTable::createPaginated('applications', $criteria);
    $table->setTitle(__('Applications'));

    if (isActionAccessible($guid, $connection2, '/modules/System Admin/formBuilder.php')) {
        $table->addHeaderAction('forms', __('Form Builder'))
            ->setURL('/modules/System Admin/formBuilder.php')
            ->setIcon('markbook')
            ->displayLabel();
    }

    $table->modifyRows(function ($values, $row) {
        if ($values['status'] == 'Incomplete') $row->addClass('warning');
        if ($values['status'] == 'Rejected') $row->addClass('error');
        if ($values['status'] == 'Accepted') $row->addClass('success');
        return $row;
    });

    $table->addColumn('gibbonAdmissionsApplicationID', __('ID'))->format(function ($values) {
        return intval($values['gibbonAdmissionsApplicationID']);
    });

    $table->addColumn('student', __('Student'))->format(function ($values) {
        return Format::name('', $values['studentSurname'], $values['studentPreferredName'], 'Student', true);
    });
    $table->addColumn('yearGroup', __('Year Group'));
    $table->addColumn('formGroup', __('Form Group'));
    $table->addColumn('formName', __('Application Form'));
    $table->addColumn('status', __('Status'));
    $table->addColumn('timestampCreated', __('Created'))->format(Format::using('relativeTime', 'timestampCreated'));

    $table->addActionColumn()
        ->addParam('gibbonAdmissionsApplicationID')
        ->format(function ($application, $actions) {

            if ($application['status'] == 'Pending' or $application['status'] == 'Waiting List') {
                $actions->addAction('accept', __('Accept'))
                    ->setIcon('iconTick')
                    ->setURL('/modules/Admissions/applications_manage_accept.php');

                $actions->addAction('reject', __('Reject'))
                    ->setIcon('iconCross')
                    ->setURL('/modules/Admissions/applications_manage_reject.php');
            }

            if ($application['status'] == 'Accepted' || $application['status'] == 'Incomplete') {
                $actions->addAction('view', __('View & Print Application'))
                    ->setURL('/modules/Admissions/applications_manage_view.php');
            }

            if ($application['status'] != 'Incomplete') {
                $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Admissions/applications_manage_edit.php');
            }

            $actions->addAction('delete', __('Delete'))
                ->setURL('/modules/Admissions/applications_manage_delete.php');
        });

    echo $table->render($submissions);
}
