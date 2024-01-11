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
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\Admissions\AdmissionsApplicationGateway;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\User\FamilyGateway;

if (isActionAccessible($guid, $connection2, '/modules/Admissions/applications_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs->add(__('Manage Applications'));

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
        $row->addSearchSubmit($session, __('Clear Search'), ['gibbonSchoolYearID']);

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

    $applications = $admissionsApplicationGateway->queryApplicationsBySchoolYear($criteria, $gibbonSchoolYearID, 'Application');
    $applications->transform(function (&$values) {
        $defaults = ['gibbonFamilyID' => null];
        $data = json_decode($values['data'] ?? '', true);
        $values = array_merge($defaults, $data, $values);
    });

    $familyIDs = $applications->getColumn('gibbonFamilyID');
    $adults = $container->get(FamilyGateway::class)->selectAdultsByFamily($familyIDs)->fetchGrouped();
    $applications->joinColumn('gibbonFamilyID', 'adults', $adults);

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
        'paid:y'              => __('Paid').': '.__('Yes'),
        'paid:n'              => __('Paid').': '.__('No'),
        'paid:exemption'      => __('Paid').': '.__('Exemption'),
        'formGroup:y'         => __('Form Group').': '.__('Yes'),
        'formGroup:n'         => __('Form Group').': '.__('No'),
    ]);

    $table->addColumn('gibbonAdmissionsApplicationID', __('ID'))->format(function ($application) {
        return intval($application['gibbonAdmissionsApplicationID']);
    });

    $table->addColumn('student', __('Student'))
        ->description(__('Application Date'))
        ->sortable(['studentSurname', 'studentPreferredName'])
        ->format(function ($application) {
            return Format::bold(Format::name('', $application['studentPreferredName'], $application['studentSurname'], 'Student', true));
        })
        ->formatDetails(function ($application) {
            return Format::small(Format::date($application['timestampCreated']));
        });

    $table->addColumn('dob', __('Birth Year'))
        ->description(__('Entry Year'))
        ->format(function($application) {
            return substr($application['dob'] ?? '', 0, 4);
        })
        ->formatDetails(function ($application) {
            return Format::small($application['yearGroup'] ?? '');
        });

    $table->addColumn('parents', __('Parents'))
        ->notSortable()
        ->format(function($application) {
            $parentsText = '';
            if (empty($application['gibbonFamilyID'])) {
                $application['adults'] = [];
                if (!empty($application['parent1surname']) && !empty($application['parent1preferredName'])) {
                    $application['adults'][] = ['title' => $application['parent1title'] ?? '', 'preferredName' => $application['parent1preferredName'], 'surname' => $application['parent1surname'], 'email' => $application['parent1email']];
                }
                if (!empty($application['parent2surname']) && !empty($application['parent2preferredName'])) {
                    $application['adults'][] = ['title' => $application['parent2title'] ?? '','preferredName' => $application['parent2preferredName'],'surname' => $application['parent2surname'],'email' => $application['parent2email']];
                }
            }

            foreach ($application['adults'] as $parent) {
                $name = Format::name($parent['title'], $parent['preferredName'], $parent['surname'], 'Parent');
                $parentsText .= !empty($parent['email'])
                    ? Format::link($parent['email'], $name).'<br/>'
                    : $name.'<br/>';
            }

            return $parentsText;
        });

    $table->addColumn('schoolName1', __('Last School'))
        ->format(function($application) {
            $school = $application['schoolName1'] ?? '';
            if (!empty($application['schoolName2'])) {
                $schoolDate1 = $application['schoolDate1'] ?? '';
                $schoolDate2 = $application['schoolDate2'] ?? '';
                $school = $schoolDate2 > $schoolDate1 ? $application['schoolName2'] : $school;
            }
            return Format::truncate($school, 20);
        });
    
    $table->addColumn('status', __('Status'))
        ->description(__('Milestones'))
        ->format(function($application) {
            return Format::bold(__($application['status']));
        })
        ->formatDetails(function ($application) {
            $milestones = array_keys(json_decode($application['milestones'] ?? '', true)?? []);
            if ($application['status'] == 'Pending' || $application['status'] == 'Waiting List') {
                return Format::small(implode('<br/>', $milestones));
            }
        });

    $table->addColumn('priority', __('Priority'));

    $table->addActionColumn()
        ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
        ->addParam('search', $search)
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

    echo $table->render($applications);
}
