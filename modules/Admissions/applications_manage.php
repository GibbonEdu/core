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
use Gibbon\Forms\DatabaseFormFactory;

if (isActionAccessible($guid, $connection2, '/modules/Admissions/applications_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs->add(__('Manage Applications'));

    $page->addMessage('This <b>BETA</b> feature is part of the new flexible application form and admissions system. While we have worked to ensure that this functionality is ready to use, this is part of a very large set of changes that are likely to continue evolving over the next version, so we\'ve marked it as beta for v24. You are welcome to use these features and please do let us know in the support forums if you encounter any issues.');

    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');
    $gibbonYearGroupID = $_REQUEST['gibbonYearGroupID'] ?? '';
    $gibbonAdmissionsAccountID = $_GET['gibbonAdmissionsAccountID'] ?? '';
    $search = $_GET['search'] ?? '';

    $page->navigator->addSchoolYearNavigation($gibbonSchoolYearID);

    // SEARCH
    $form = Form::create('searchForm', $session->get('absoluteURL').'/index.php','get');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setClass('noIntBorder fullWidth');
    $form->setTitle(__('Search'));

    $form->addHiddenValue('q', '/modules/'.$session->get('module').'/applications_manage.php');
    $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);

    $row = $form->addRow();
        $row->addLabel('search', __('Search For'))->description(__('Application ID, preferred, surname, payment transaction ID'));
        $row->addTextField('search')->setValue($search);

    $row = $form->addRow();
        $row->addLabel('gibbonYearGroupID', __('Year Group'));
        $row->addSelectYearGroup('gibbonYearGroupID')->selected($gibbonYearGroupID)->placeholder();

    $row = $form->addRow();
        $row->addSearchSubmit($gibbon->session, __('Clear Search'), ['gibbonSchoolYearID']);

    echo $form->getOutput();

    // QUERY
    $admissionsApplicationGateway = $container->get(AdmissionsApplicationGateway::class);
    $criteria = $admissionsApplicationGateway->newQueryCriteria(true)
        ->searchBy($admissionsApplicationGateway->getSearchableColumns(), $search)
        ->sortBy('gibbonAdmissionsApplication.status')
        ->sortBy('gibbonAdmissionsApplication.priority', 'DESC')
        ->sortBy('gibbonAdmissionsApplication.timestampCreated', 'DESC')
        ->filterBy('admissionsAccount', $gibbonAdmissionsAccountID)
        ->filterBy('yearGroup', $gibbonYearGroupID)
        ->filterBy('incomplete', 'N')
        ->fromPOST();

    $submissions = $admissionsApplicationGateway->queryApplicationsBySchoolYear($criteria, $gibbonSchoolYearID, 'Application');
    $submissions->transform(function (&$values) {
        $values['data'] = json_decode($values['data'] ?? '', true);
    });

    // DATA TABLE
    $table = DataTable::createPaginated('applications', $criteria);
    $table->setTitle(__('Applications'));

    if (isActionAccessible($guid, $connection2, '/modules/System Admin/formBuilder.php')) {
        $table->addHeaderAction('forms', __('Form Builder'))
            ->setURL('/modules/System Admin/formBuilder.php')
            ->setIcon('markbook')
            ->displayLabel()
            ->append(' | ');
    }

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/Admissions/applications_manage_addSelect.php')
        ->displayLabel();

    $table->modifyRows(function ($values, $row) {
        if ($values['status'] == 'Incomplete') $row->addClass('warning');
        if ($values['status'] == 'Rejected') $row->addClass('error');
        if ($values['status'] == 'Accepted') $row->addClass('success');
        if ($values['status'] == 'Withdrawn') $row->addClass('error');
        return $row;
    });

    $table->addMetaData('filterOptions', [
        'incomplete:N'        => __('Incomplete Forms Hidden'),
        'status:pending'      => __('Status').': '.__('Pending'),
        'status:accepted'     => __('Status').': '.__('Accepted'),
        'status:rejected'     => __('Status').': '.__('Rejected'),
        'status:waiting list' => __('Status').': '.__('Waiting List'),
        'paid:y'         => __('Paid').': '.__('Yes'),
        'paid:n'         => __('Paid').': '.__('No'),
        'paid:exemption' => __('Paid').': '.__('Exemption'),
        'formGroup:y'         => __('Form Group').': '.__('Yes'),
        'formGroup:n'         => __('Form Group').': '.__('No'),
    ]);

    $table->addColumn('gibbonAdmissionsApplicationID', __('ID'))->format(function ($values) {
        return intval($values['gibbonAdmissionsApplicationID']);
    });

    $table->addColumn('student', __('Student'))
        ->description(__('Application Date'))
        ->sortable(['studentSurname', 'studentPreferredName'])
        ->format(function ($values) {
            return Format::bold(Format::name('', $values['studentSurname'], $values['studentPreferredName'], 'Student', true)).'<br/>'.
                   Format::small(Format::date($values['timestampCreated']));
        });

    $table->addColumn('dob', __('Birth Year'))
        ->description(__('Entry Year'))
        ->format(function($application) {
            return substr($application['dob'] ?? '', 0, 4).'<br/>'.Format::small($application['yearGroup'] ?? '');
        });

    $table->addColumn('parents', __('Parents'))->notSortable();

    $table->addColumn('schoolName1', __('Last School'))
        ->format(function($application) {
            $school = $application['data']['schoolName1'] ?? '';
            if (!empty($application['data']['schoolName2'])) {
                $schoolDate1 = $application['data']['schoolDate1'] ?? '';
                $schoolDate2 = $application['data']['schoolDate2'] ?? '';
                $school = $schoolDate2 > $schoolDate1 ? $application['data']['schoolName2'] : $school;
            }
            return Format::truncate($school, 20);
        });
    
    $table->addColumn('status', __('Status'))
        ->description(__('Milestones'))
        ->format(function($application) {
            $statusText = Format::bold(__($application['status']));
            $milestones = array_keys(json_decode($application['milestones'] ?? '', true)?? []);
            if ($application['status'] == 'Pending' || $application['status'] == 'Waiting List') {
                $statusText .= '<br/>'.Format::small(implode('<br/>', $milestones)).'</span>';
            }
            return $statusText;
        });

    $table->addColumn('priority', __('Priority'));

    $table->addActionColumn()
        ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
        ->addParam('search', $criteria->getSearchText(true))
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

            if ($application['status'] == 'Incomplete' && empty($application['owner'])) {
                $actions->addAction('continue', __('Continue'))
                    ->setURL('/modules/Admissions/applications_manage_add.php')
                    ->addParam('gibbonFormID', $application['gibbonFormID'])
                    ->addParam('identifier', $application['identifier'])
                    ->addParam('accessID', $application['accessID'])
                    ->setIcon('page_right');
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
