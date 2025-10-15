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

use Gibbon\Http\Url;
use Gibbon\Forms\Form;
use Gibbon\View\Component;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\UI\Components\Alert;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\StudentAlerts\AlertGateway;

if (!isActionAccessible($guid, $connection2, '/modules/Student Alerts/report_alertsByClass.php')) {
	// Access denied
	$page->addError(__('You do not have access to this action.'));
} else {
    $page->breadcrumbs->add(__('Student Alerts by Class'));

    $gibbonCourseClassID = $_REQUEST['gibbonCourseClassID'] ?? '';
    $gibbonSchoolYearID = $session->get('gibbonSchoolYearID');

    $alertGateway = $container->get(AlertGateway::class);
    $alertManager = $container->get(Alert::class);

    // SEARCH
    $form = Form::createSearch();
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $row = $form->addRow();
        $row->addLabel('gibbonCourseClassID',__('Class'));
        $row->addSelectClass('gibbonCourseClassID', $gibbonSchoolYearID, $session->get('gibbonPersonID'))->selected($gibbonCourseClassID)->placeholder();

    $row = $form->addRow();
        $row->addFooter();
        $row->addSearchSubmit($session, __('Clear Filters'));

    echo $form->getOutput();

    if (empty($gibbonCourseClassID)) return;

    // CRITERIA
    $criteria = $alertGateway->newQueryCriteria(true)
        ->sortBy(['surname', 'preferredName'])
        ->filterBy('class', $gibbonCourseClassID)
        ->fromPOST();

    $students = $alertGateway->queryStudentsWithAlertsByClass($criteria, $gibbonCourseClassID);
    $students->transform(function (&$student) use ($alertGateway, $alertManager, $gibbonSchoolYearID, $gibbonCourseClassID) {
        $student['alerts'] = $alertManager->getAlertsByStudent($student['gibbonPersonID']);
        $student['classAlerts'] = $alertGateway->selectClassAlertsByStudent($gibbonSchoolYearID, $student['gibbonPersonID'])->fetchGrouped();
        $student['classAlerts'] = array_map(function ($alertGroup) use ($gibbonCourseClassID) {
            return array_filter($alertGroup, function ($alert) use ($gibbonCourseClassID) {
                return $alert['gibbonCourseClassID'] == $gibbonCourseClassID;
            });
        }, $student['classAlerts']);
    });

    // DATA TABLE
    $table = DataTable::createPaginated('manageAlerts', $criteria);
    $table->setTitle(__('Alerts'));

    $table->addHeaderAction('add', __('Add Class Alert'))
        ->setURL('/modules/Student Alerts/studentAlerts_add.php')
        ->addParam('gibbonCourseClassID', $gibbonCourseClassID)
        ->addParam('source', 'class')
        ->displayLabel();

    $table->modifyRows(function($alert, $row) {
        if ($alert['status'] == 'Pending') $row->addClass('warning');
        elseif ($alert['status'] == 'Declined') $row->addClass('dull');
        return $row;
    });

    $table->addColumn('image_240', __('Photo'))
        ->context('primary')
        ->width('7%')
        ->notSortable()
        ->format(Format::using('userPhoto', ['image_240', 'xs']));

    $table->addColumn('student', __('Student'))
        ->description(__('Form Group'))
        ->sortable(['surname', 'preferredName'])
        ->context('primary')
        ->format(function($student) {
            return Format::nameLinked($student['gibbonPersonID'], '', $student['preferredName'], $student['surname'], 'Student', true, true, ['subpage' => 'Personal']);
        })
        ->formatDetails(function ($student) {
            return Format::small($student['formGroup']);
        });

    $alertTypes = $alertManager->getActiveAlertTypes();

    foreach ($alertTypes as $alertType) {
        $table->addColumn($alertType['gibbonAlertTypeID'], __($alertType['name']))
            ->notSortable()
            ->width('10%')
            ->format(function($student) use ($alertType) {
                $alert = $student['alerts'][$alertType['name']] ?? [];
                $classAlerts = $student['classAlerts'][$alertType['name']] ?? [];
                $output = '';

                if (!empty($alert)) {
                    $output .= Component::render(Alert::class, [
                        'color'   => $alert['levelColor'] ?? $alert['color'],
                        'colorBG' => $alert['levelColorBG'] ?? $alert['colorBG'],
                        'title' => $alert['type'] ?? '',
                        'large' => true,
                    ] + $alert);
                }

                if (!empty($classAlerts)) {
                    $topAlert = current($classAlerts);
                    $output .= Component::render(Alert::class, [
                        'link'    => Url::fromModuleRoute('Student Alerts', 'studentAlerts_manage')->withQueryParams(['gibbonPersonID' => $student['gibbonPersonID']]),
                        'color'   => $topAlert['levelColor'] ?? $topAlert['color'],
                        'colorBG' => $topAlert['levelColorBG'] ?? $topAlert['colorBG'],
                        'title'   => __('Class').' '.$topAlert['name'],
                        'tag'     => count($classAlerts),
                        'medium'  => true,
                    ]);
                }

                return $output;
            });
    }

    $table->addActionColumn()
        ->addParam('gibbonCourseClassID', $gibbonCourseClassID)
        ->addParam('source', 'class')
        ->addParam('gibbonPersonID')
        ->format(function ($alert, $actions)  {
            $actions->addAction('add', __('Add'))
                ->setURL('/modules/Student Alerts/studentAlerts_add.php');

            $actions->addAction('view', __('View'))
                ->setURL('/modules/Student Alerts/studentAlerts_manage.php');
        });

    echo $table->render($students);

}
