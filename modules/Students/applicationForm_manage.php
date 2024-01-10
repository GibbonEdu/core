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
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\Students\ApplicationFormGateway;
use Gibbon\Domain\User\FamilyGateway;

if (isActionAccessible($guid, $connection2, '/modules/Students/applicationForm_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Manage Applications'));

    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');
    $search = $_GET['search']  ?? '';
    $gibbonYearGroupID = $_GET['gibbonYearGroupID']  ?? '';

    $page->navigator->addSchoolYearNavigation($gibbonSchoolYearID);

    $familyGateway = $container->get(FamilyGateway::class);
    $applicationGateway = $container->get(ApplicationFormGateway::class);

    $criteria = $applicationGateway->newQueryCriteria(true)
        ->searchBy($applicationGateway->getSearchableColumns(), $search)
        ->sortBy('gibbonApplicationForm.status')
        ->sortBy('gibbonApplicationForm.priority', 'DESC')
        ->sortBy('gibbonApplicationForm.timestamp', 'DESC')
        ->filterBy('yearGroup', $gibbonYearGroupID)
        ->fromPOST();

    echo '<h4>';
    echo __('Search');
    echo '</h2>';

    $form = Form::create('searchForm', $session->get('absoluteURL').'/index.php', 'get');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setClass('noIntBorder fullWidth');
    $form->addHiddenValue('q', '/modules/'.$session->get('module').'/applicationForm_manage.php');
    $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);

    $row = $form->addRow();
        $row->addLabel('search', __('Search For'))->description(__('Application ID, preferred, surname, payment transaction ID'));
        $row->addTextField('search')->setValue($search);

    $row = $form->addRow();
        $row->addLabel('gibbonYearGroupID', __('Year Group'));
        $row->addSelectYearGroup('gibbonYearGroupID')->selected($gibbonYearGroupID)->placeholder();

    $row = $form->addRow();
        $row->addSearchSubmit($session, __('Clear Search'), array('gibbonSchoolYearID'));

    echo $form->getOutput();

    echo '<h4>';
    echo __('View');
    echo '</h2>';

    $applications = $applicationGateway->queryApplicationFormsBySchoolYear($criteria, $gibbonSchoolYearID);

    $familyIDs = $applications->getColumn('gibbonFamilyID');
    $adults = $familyGateway->selectAdultsByFamily($familyIDs)->fetchGrouped();
    $applications->joinColumn('gibbonFamilyID', 'adults', $adults);

    // DATA TABLE
    $table = DataTable::createPaginated('applications', $criteria);

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/Students/applicationForm_manage_add.php')
        ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
        ->addParam('search', $criteria->getSearchText(true))
        ->displayLabel();

    $table->modifyRows(function ($application, $row) {
        if ($application['status'] == 'Accepted') $row->addClass('current');
        if ($application['status'] == 'Rejected') $row->addClass('error');
        if ($application['status'] == 'Withdrawn') $row->addClass('error');
        return $row;
    });

    $table->addMetaData('filterOptions', [
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

    $table->addColumn('gibbonApplicationFormID', __('ID'))
            ->format(Format::using('number', ['gibbonApplicationFormID']));

    $table->addColumn('student', __('Student'))
        ->description(__('Application Date'))
        ->sortable(['surname', 'preferredName'])
        ->format(function ($application) use ($applicationGateway, $session) {
            $output = '';

            // Add a list of linked sibling appplications as an icon with hover-over text
            $linkedApplications = $applicationGateway->selectLinkedApplicationsByID($application['gibbonApplicationFormID']);
            if ($linkedApplications->rowCount() > 0) {
                $siblings = array_map(function($sibling) {
                    return '- ' . Format::name('', $sibling['preferredName'], $sibling['surname'], 'Student', true).' ('.$sibling['status'].')';
                }, $linkedApplications->fetchAll());
                $output .= "<img title='" . __('Sibling Applications') .'<br/>' . implode('<br/>', $siblings). "' src='./themes/" . $session->get("gibbonThemeName") . "/img/attendance.png'/ style='float: right;   width:20px; height:20px;margin-left:4px;'>";
            }
            
            $output .= '<strong>'.Format::name('', $application['preferredName'], $application['surname'], 'Student', true, true) . '</strong><br/>';
            $output .= '<small><i>'.Format::date($application['timestamp']).'</i></small>';

            return $output;
        });
        
    $table->addColumn('dob', __('Birth Year'))
        ->description(__('Entry Year'))
        ->format(function($application) {
            return substr($application['dob'], 0, 4).'<br/><span style="font-style: italic; font-size: 85%">'.$application['yearGroup'].'</span>';
        });

    $table->addColumn('parents', __('Parents'))
        ->sortable(false)
        ->format(function($application) {
            $parentsText = '';
            if (empty($application['gibbonFamilyID'])) {
                $application['adults'] = array();
                if (!empty($application['parent1surname']) && !empty($application['parent1preferredName'])) {
                    $application['adults'][] = array('title' => $application['parent1title'], 'preferredName' => $application['parent1preferredName'], 'surname' => $application['parent1surname'], 'email' => $application['parent1email']);
                }
                if (!empty($application['parent2surname']) && !empty($application['parent2preferredName'])) {
                    $application['adults'][] = array('title' => $application['parent2title'],'preferredName' => $application['parent2preferredName'],'surname' => $application['parent2surname'],'email' => $application['parent2email']);
                }
            }

            foreach ($application['adults'] as $parent) {
                $name = Format::name($parent['title'], $parent['preferredName'], $parent['surname'], 'Parent');
                $link = !empty($parent['email'])? 'mailto:'.$parent['email'] : '';
                $parentsText .= Format::link($link, $name).'<br/>';
            }

            return $parentsText;
        });

    $table->addColumn('schoolName1', __('Last School'))
        ->format(function($application) {
            $school = $application['schoolName1'];
            if ($application['schoolDate2'] > $application['schoolDate1'] && !empty($application['schoolName2'])) {
                $school = $application['schoolName2'];
            }
            return Format::truncate($school, 20);
        });

    $table->addColumn('status', __('Status'))
        ->description(__('Milestones'))
        ->format(function($application) {
            $statusText = '<strong>'.__($application['status']).'</strong>';
            if ($application['status'] == 'Pending') {
                $statusText .= '<br/><span style="font-style: italic; font-size: 85%">'.str_replace(',', '<br/>', $application['milestones']).'</span>';
            }
            return $statusText;
        });

    $table->addColumn('priority', __('Priority'));

    if ($criteria->hasFilter('paid')) {
        $table->addColumn('paymentMade', __('Payment Made'))->format(function($application) {
            return $application['paymentMade'] == 'Exemption'
                ? __('Exemption')
                : Format::yesNo($application['paymentMade']);
        });
    }

    $table->addActionColumn()
        ->width('72px')
        ->addParam('gibbonApplicationFormID')
        ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
        ->addParam('search', $criteria->getSearchText(true))
        ->format(function ($application, $actions) use ($guid, $connection2) {
            if ($application['status'] == 'Pending' or $application['status'] == 'Waiting List') {
                $actions->addAction('accept', __('Accept'))
                    ->setIcon('iconTick')
                    ->setURL('/modules/Students/applicationForm_manage_accept.php');

                $actions->addAction('reject', __('Reject'))
                    ->setIcon('iconCross')
                    ->setURL('/modules/Students/applicationForm_manage_reject.php')
                    ->append('<br/><div style="height:8px;"></div>');
            }

            $actions->addAction('edit', __('Edit'))
                ->setURL('/modules/Students/applicationForm_manage_edit.php');

            if (isActionAccessible($guid, $connection2, '/modules/Students/applicationForm_manage_delete.php')) {
                $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/Students/applicationForm_manage_delete.php');
            }
        });

    echo $table->render($applications);

}
