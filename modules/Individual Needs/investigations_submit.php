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

use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\IndividualNeeds\INInvestigationContributionGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Individual Needs/investigations_submit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $page->breadcrumbs->add(__('Submit Contributions'));

    $contributionsGateway = $container->get(INInvestigationContributionGateway::class);

    // CRITERIA
    $criteria = $contributionsGateway->newQueryCriteria()
        ->sortBy('status')
        ->sortBy('date', 'DESC')
        ->fromPOST();

    $records = $contributionsGateway->queryContributionsByPerson($criteria, $session->get('gibbonPersonID'));

    // DATA TABLE
    $table = DataTable::createPaginated('investigationsManage', $criteria);
    $table->setTitle(__('Investigations'));

    $table->modifyRows(function ($investigations, $row) {
        if ($investigations['status'] == 'Complete') $row->addClass('success');
        if ($investigations['status'] == 'Pending') $row->addClass('warning');
        return $row;
    });

    $table->addColumn('status', __('Status'))
        ->format(function ($investigations) {
            return __($investigations['status']);
        });

    $table->addColumn('student', __('Student'))
        ->description(__('Form Group'))
        ->sortable(['student.surname', 'student.preferredName'])
        ->width('25%')
        ->format(function ($person) {
            $url = './index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$person['gibbonPersonIDStudent'].'&subpage=Individual Needs&search=&allStudents=&sort=surname,preferredName';
            return '<b>'.Format::link($url, Format::name('', $person['preferredName'], $person['surname'], 'Student', true)).'</b>'
                  .'<br/><small><i>'.$person['formGroup'].'</i></small>';
        });

    $table->addColumn('date', __('Date'))
        ->format(function ($investigations) {
            return Format::date($investigations['date']);
        });

    $table->addColumn('type', __('Type'))->translatable();

    $table->addColumn('class', __('Class'))
        ->format(function ($investigations) {
            if ($investigations['type'] == 'Teacher') {
                return ($investigations['course'].'.'.$investigations['class']);
            }
        });

    $table->addColumn('teacher', __('Teacher'))
        ->sortable(['preferredNameCreator', 'surnameCreator'])
        ->width('25%')
        ->format(function ($person) {
            return Format::name($person['titleCreator'], $person['preferredNameCreator'], $person['surnameCreator'], 'Staff');
        });

    $table->addActionColumn()
        ->addParam('gibbonINInvestigationContributionID')
            ->addParam('gibbonINInvestigationID')
        ->format(function ($person, $actions) {
            if ($person['status'] == 'Pending') {
                $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Individual Needs/investigations_submit_detail.php');
            }
        });

    echo $table->render($records);
}
