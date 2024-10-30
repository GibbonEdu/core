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
use Gibbon\Domain\IndividualNeeds\INInvestigationGateway;
use Gibbon\Domain\IndividualNeeds\INInvestigationContributionGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Individual Needs/investigations_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Get action with highest precedence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if (empty($highestAction)) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        $page->breadcrumbs->add(__('Manage Investigations'));

        $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
        $gibbonFormGroupID = $_GET['gibbonFormGroupID'] ?? '';
        $gibbonYearGroupID = $_GET['gibbonYearGroupID'] ?? '';

        $form = Form::create('filter', $session->get('absoluteURL').'/index.php', 'get');
        $form->setTitle(__('Filter'));
        $form->setClass('noIntBorder fullWidth');
        $form->setFactory(DatabaseFormFactory::create($pdo));

        $form->addHiddenValue('q', "/modules/Individual Needs/investigations_manage.php");

        $row = $form->addRow();
            $row->addLabel('gibbonPersonID', __('Student'));
            $row->addSelectStudent('gibbonPersonID', $session->get('gibbonSchoolYearID'))->selected($gibbonPersonID)->placeholder();

        $row = $form->addRow();
            $row->addLabel('gibbonFormGroupID', __('Form Group'));
            $row->addSelectFormGroup('gibbonFormGroupID', $session->get('gibbonSchoolYearID'))->selected($gibbonFormGroupID)->placeholder();

        $row = $form->addRow();
            $row->addLabel('gibbonYearGroupID', __('Year Group'));
            $row->addSelectYearGroup('gibbonYearGroupID')->placeholder()->selected($gibbonYearGroupID);

        $row = $form->addRow();
            $row->addSearchSubmit($session, __('Clear Filters'));

        echo $form->getOutput();

        $investigationGateway = $container->get(INInvestigationGateway::class);
        $criteria = $investigationGateway->newQueryCriteria()
            ->sortBy('date', 'DESC')
            ->filterBy('student', $gibbonPersonID)
            ->filterBy('formGroup', $gibbonFormGroupID)
            ->filterBy('yearGroup', $gibbonYearGroupID)
            ->fromPOST();

        $contributionsGateway = $container->get(INInvestigationContributionGateway::class);
        $criteria2 = $contributionsGateway->newQueryCriteria();

        if ($highestAction == 'Manage Investigations_all') {
            $records = $investigationGateway->queryInvestigations($criteria, $session->get('gibbonSchoolYearID'));
        } else if ($highestAction == 'Manage Investigations_my') {
            $records = $investigationGateway->queryInvestigations($criteria, $session->get('gibbonSchoolYearID'), $session->get('gibbonPersonID'));
        } else {
            return;
        }

        // DATA TABLE
        $table = DataTable::createPaginated('investigationsManage', $criteria);
        $table->setTitle(__('Investigations'));

        $table->addHeaderAction('add', __('Add'))
            ->setURL('/modules/Individual Needs/investigations_manage_add.php')
            ->addParam('gibbonPersonID', $gibbonPersonID)
            ->addParam('gibbonFormGroupID', $gibbonFormGroupID)
            ->addParam('gibbonYearGroupID', $gibbonYearGroupID)
            ->displayLabel();

        $table->modifyRows(function ($investigations, $row) {
            if ($investigations['status'] == 'Resolved' || $investigations['status'] == 'Investigation Complete') $row->addClass('success');
            if ($investigations['status'] == 'Investigation') $row->addClass('warning');
            return $row;
        });

        $table->addExpandableColumn('comment')
            ->format(function ($investigations) {
                $output = '';
                $output .= '<strong>'.__('Reason').'</strong><br/>';
                $output .= nl2br($investigations['reason']).'<br/>';
                if (!empty($investigations['strategiesTried'])) {
                    $output .= '<br/><strong>'.__('Strategies Tried').'</strong><br/>';
                    $output .= nl2br($investigations['strategiesTried']).'<br/>';
                }
                if ($investigations['parentsInformed'] == 'Y') {
                    $output .= '<br/><strong>'.__('Parents Informed?').'</strong><br/>';
                    $output .= Format::yesNo($investigations['parentsInformed']).'<br/>';
                    if (!empty($investigations['parentsResponse'])) {
                        $output .= '<br/><strong>'.__('Parent Response').'</strong><br/>';
                        $output .= nl2br($investigations['parentsResponse']).'<br/>';
                    }
                }
                if (!empty($investigations['resolutionDetails'])) {
                    $output .= '<br/><strong>'.__('Resolution Details').'</strong><br/>';
                    $output .= nl2br($investigations['resolutionDetails']).'<br/>';
                }

                return $output;
            });

        $table->addColumn('status', __('Status'))
            ->description(__('Progress'))
            ->format(function ($investigations) use ($contributionsGateway, &$page) {
                $output = __($investigations['status']);
                if ($investigations['status'] == 'Investigation') {
                    $completion = $contributionsGateway->getInvestigationCompletion($investigations['gibbonINInvestigationID']);
                    $output .= $page->fetchFromTemplate('ui/progress.twig.html', [
                        'progressCount' => $completion['complete'],
                        'totalCount'    => $completion['total'],
                        'width'         => 'w-32 mt-1',
                    ]);
                }
                return $output;
            });

        $table->addColumn('student', __('Student'))
            ->description(__('Form Group'))
            ->sortable(['student.surname', 'student.preferredName'])
            ->width('25%')
            ->format(function ($person) {
                $url = './index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$person['gibbonPersonID'].'&subpage=Individual Needs&search=&allStudents=&sort=surname,preferredName';
                return '<b>'.Format::link($url, Format::name('', $person['preferredName'], $person['surname'], 'Student', true)).'</b>'
                      .'<br/><small><i>'.$person['formGroup'].'</i></small>';
            });

        $table->addColumn('date', __('Date'))
            ->format(function ($investigations) {
                return Format::date($investigations['date']);
            });

        $table->addColumn('teacher', __('Teacher'))
            ->sortable(['preferredNameCreator', 'surnameCreator'])
            ->width('25%')
            ->format(function ($person) {
                return Format::name($person['titleCreator'], $person['preferredNameCreator'], $person['surnameCreator'], 'Staff');
            });

        $table->addActionColumn()
            ->addParam('gibbonPersonID', $gibbonPersonID)
            ->addParam('gibbonFormGroupID', $gibbonFormGroupID)
            ->addParam('gibbonYearGroupID', $gibbonYearGroupID)
            ->addParam('gibbonINInvestigationID')
            ->format(function ($person, $actions) use ($highestAction) {
                $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Individual Needs/investigations_manage_edit.php');
                if ($highestAction == 'Manage Investigations_all') {
                    $actions->addAction('delete', __('Delete'))
                        ->setURL('/modules/Individual Needs/investigations_manage_delete.php');
                }
            });

        echo $table->render($records);
    }
}
