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
use Gibbon\Tables\DataTable;
use Gibbon\Tables\Prefab\ReportTable;
use Gibbon\Domain\Activities\ActivityGateway;
use Gibbon\Domain\Activities\ActivityCategoryGateway;
use Gibbon\Domain\Activities\ActivityStudentGateway;
use Gibbon\Domain\Activities\ActivityStaffGateway;

if (isActionAccessible($guid, $connection2, '/modules/Activities/report_overview.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if (empty($highestAction)) {
        $page->addError(__('You do not have access to this action.'));
        return;
    }

    $page->breadcrumbs->add(__('Activities Overview'));

    $params = [
        'gibbonActivityCategoryID' => $_REQUEST['gibbonActivityCategoryID'] ?? '',
        'gibbonActivityID' => $_REQUEST['gibbonActivityID'] ?? '',
    ];

    // Setup data
    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');
    $viewMode = $_REQUEST['format'] ?? '';

    // Setup gateways
    $activityGateway = $container->get(ActivityGateway::class);
    $activityActivityCategoryGateway = $container->get(ActivityCategoryGateway::class);
    $activityStudentGateway = $container->get(ActivityStudentGateway::class);

    $canManageEnrolment = isActionAccessible($guid, $connection2, '/modules/Activities/enrolment_manage.php');

    $categories = $activityActivityCategoryGateway->selectCategoriesBySchoolYear($session->get('gibbonSchoolYearID'))->fetchKeyPair();

    if (empty($categories)) {
        $page->addMessage(__('There are no records to display.'));
        return;
    }
    
    if (empty($viewMode)) {
        // FILTER
        $form = Form::create('filter', $session->get('absoluteURL').'/index.php', 'get');

        $form->setTitle(__('Filter'));
        $form->setClass('noIntBorder fullWidth');

        $form->addHiddenValue('q', '/modules/'.$session->get('module').'/report_overview.php');
        $form->addHiddenValue('address', $session->get('address'));

        $row = $form->addRow();
        $row->addLabel('gibbonActivityCategoryID', __('Category'));
        $row->addSelect('gibbonActivityCategoryID')->fromArray($categories)->required()->placeholder()->selected($params['gibbonActivityCategoryID']);

        if (!empty($params['gibbonActivityCategoryID'])) {
            $activityList = $activityGateway->selectActivitiesByCategory($params['gibbonActivityCategoryID'])->fetchKeyPair();
            $row = $form->addRow();
            $row->addLabel('gibbonActivityID', __('Activity'));
            $row->addSelect('gibbonActivityID')
                ->fromArray($activityList)
                ->placeholder()
                ->selected($params['gibbonActivityID'] ?? '');
        }

        $row = $form->addRow();
            $row->addFooter();
            $row->addSearchSubmit($session);

        echo $form->getOutput();
    }

    // Nothing to display
    if (empty($params['gibbonActivityCategoryID'])) return;

    $activities = $activityGateway->selectActivityDetailsByCategory($params['gibbonActivityCategoryID'])->fetchGroupedUnique();

    if (!empty($params['gibbonActivityID'])) {
        $activities = [$params['gibbonActivityID'] => $activities[$params['gibbonActivityID']] ?? ''];
    }

    if (empty($activities)) {
        $activities = [-1 => ''];
    } else if (empty($viewMode)) {
        $form = Form::create('print', '');
        $form->addHeaderAction('print', __('Print All'))
            ->setUrl('/report.php')
            ->addParam('q', '/modules/Activities/report_overview.php')
            ->addParam('gibbonActivityCategoryID', $params['gibbonActivityCategoryID'])
            ->addParam('format', 'print')
            ->setTarget('_blank')
            ->directLink()
            ->displayLabel();

        echo $form->getOutput();
    }

    // TABLES
    foreach ($activities as $gibbonActivityID => $activity) {

        $_GET['gibbonActivityID'] = $gibbonActivityID;

        // QUERY
        $criteria = $activityStudentGateway->newQueryCriteria()
            ->sortBy(['sortOrder', 'role', 'status', 'surname', 'preferredName'])
            ->fromPOST('report_overview'.$gibbonActivityID);

        $enrolment = $activityStudentGateway->queryAllActivityParticipants($criteria, $gibbonActivityID);

        $table = ReportTable::createPaginated('report_overview'.$gibbonActivityID, $criteria)->setViewMode($viewMode, $session);
        $table->setTitle($activity['name']);
        $table->setDescription(__('Location').': '.($activity['space'] ?? $activity['locationExternal']).(!empty($activity['provider']) ? ' ('.__('Provider').': '.$activity['provider'].')' : ''));

        $table->modifyRows(function($values, $row) {
            if ($values['roleCategory'] != 'Student') $row->addClass('message');
            if ($values['status'] == 'Pending') $row->addClass('warning');
            return $row;
        });

        $table->addMetaData('hidePagination', true);
        $table->addMetaData('hideHeaderActions', true);

        $table->addColumn('image_240', __('Photo'))
            ->context('primary')
            ->width('8%')
            ->notSortable()
            ->format(Format::using('userPhoto', ['image_240', 'xs']));
            
        $table->addColumn('student', __('Person'))
            ->description(__('Status'))
            ->sortable(['surname', 'preferredName'])
            ->width('30%')
            ->format(function ($values) {
                return Format::nameLinked($values['gibbonPersonID'], '', $values['preferredName'], $values['surname'], $values['roleCategory'], true, true);
            })
            ->formatDetails(function ($values) {
                return $values['roleCategory'] == 'Student'
                    ? Format::small($values['status'])
                    : Format::small($values['role']);
            });

        $table->addColumn('formGroup', __('Form Group'))
            ->width('12%')
            ->context('secondary');

        $choices = ['1' => __('1st'), '2' => __('2nd'), '3' => __('3rd'), '4' => __('4th'), '5' => __('5th')];
        $table->addColumn('choice', __('Choice'))
            // ->width('5%')
            ->format(function ($values) use ($choices) {
                switch ($values['choice']) {
                    case 1: $class = 'success'; break;
                    case 2: $class = 'message'; break;
                    case 3: $class = 'warning'; break;
                    case 4: $class = 'warning'; break;
                    case 5: $class = 'warning'; break;
                    default: $class = 'error'; break;
                }
                return Format::tag($choices[$values['choice']] ?? $values['choice'], $class);
            });

        // $table->addColumn('notes', __('Notes'))
        //     ->format(Format::using('truncate', 'notes'));

        // ACTIONS
        if ($canManageEnrolment) {
            
            $table->addActionColumn()
                ->addParam('search', $criteria->getSearchText(true))
                ->addParam('gibbonActivityCategoryID', $params['gibbonActivityCategoryID'])
                ->addParam('gibbonActivityID', $gibbonActivityID)
                ->addParam('gibbonActivityStudentID')
                ->addParam('gibbonPersonID')
                ->format(function ($values, $actions) {
                    if ($values['roleCategory'] == 'Student') {
                        $actions->addAction('edit', __('Edit'))
                                ->setURL('/modules/Activities/activities_manage_enrolment_edit.php')
                                ->addParam('mode', 'edit');
                    }
                });
        }

        if ($viewMode == 'print') {
            $table->setHeader([]);
        }

        echo $table->render($enrolment ?? []);
    }
}
