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
use Gibbon\Services\Format;
use Gibbon\Support\Facades\Access;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\Timetable\CourseGateway;
use Gibbon\Domain\StudentAlerts\AlertGateway;
use Gibbon\Domain\StudentAlerts\AlertTypeGateway;

if (!isActionAccessible($guid, $connection2, '/modules/Student Alerts/studentAlerts_edit.php')) {
	// Access denied
	$page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $action = Access::get('Student Alerts', 'studentAlerts_edit');
    if (empty($action)) {
        $page->addError(__('The highest grouped action cannot be determined.'));
        return;
    }

    $page->breadcrumbs
        ->add(__('Manage Alerts'), 'studentAlerts_manage.php')
        ->add(__('Edit'));

    $alertGateway = $container->get(AlertGateway::class);
    $alertTypeGateway = $container->get(AlertTypeGateway::class);

    
    $gibbonAlertID = $_GET['gibbonAlertID'] ?? '';
    $params = [
        'gibbonPersonID'    => $_REQUEST['gibbonPersonID'] ?? '',
        'gibbonFormGroupID' => $_REQUEST['gibbonFormGroupID'] ?? '',
        'gibbonYearGroupID' => $_REQUEST['gibbonYearGroupID'] ?? '',
    ];

    if (empty($gibbonAlertID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $values = $alertGateway->getByID($gibbonAlertID);
    
    if (empty($values)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $canEditAlert = $action->allowsAny('Manage Student Alerts_all', 'Manage Student Alerts_headOfYear') || $alertGateway->getAlertEditAccess($gibbonAlertID, $session->get('gibbonPersonID'));
    
    if (!$canEditAlert) {
        $page->addError(__('You do not have edit access to this record.'));
        return;
    }

    if (!empty($params['gibbonPersonID']) || !empty($params['gibbonFormGroupID']) || !empty($params['gibbonYearGroupID'])) {
        $page->navigator->addSearchResultsAction(Url::fromModuleRoute('Student Alerts', 'studentAlerts_manage')->withQueryParams($params));
    }

    $form = Form::create('editAlert', $session->get('absoluteURL').'/modules/Student Alerts/studentAlerts_editProcess.php?gibbonAlertID='.$gibbonAlertID.'&gibbonPersonID='.$params['gibbonPersonID'].'&gibbonFormGroupID='.$params['gibbonFormGroupID'].'&gibbonYearGroupID='.$params['gibbonYearGroupID']);
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonAlertID', $gibbonAlertID);

    $form->addRow()->addHeading('Edit Alert', __('Edit Alert'));

    $row = $form->addRow();
        $row->addLabel('gibbonPersonID', __('Student'));
        $row->addSelectStudent('gibbonPersonID', $session->get('gibbonSchoolYearID'))->placeholder()->selected($values['gibbonPersonID'])->readonly();
        $form->addHiddenValue('gibbonPersonID', $values['gibbonPersonID']);

    if (!empty($values['gibbonCourseClassID'])) {
        $class = $container->get(CourseGateway::class)->getCourseClassByID($values['gibbonCourseClassID']);
        $row = $form->addRow();
            $row->addLabel('gibbonCourseClassID', __('Class'));
            $row->addTextField('gibbonCourseClassID')->readOnly()->setValue(Format::courseClassName($class['courseNameShort'] ?? '', $class['nameShort'] ?? ''));
    }

    $alertType = $alertTypeGateway->getByID($values['gibbonAlertTypeID']);
    $row = $form->addRow();
        $row->addLabel('typeLabel', __('Type'));
        $row->addTextField('typeLabel')->readonly()->setValue($alertType['name']);

    if ($action->allowsAny('Manage Student Alerts_all', 'Manage Student Alerts_headOfYear') && $values['status'] != 'Pending') {
        $row = $form->addRow();
        $row->addLabel('status', __('Status'));
        $row->addSelect('status')->fromArray(['Pending' => __('Pending'), 'Approved' => __('Approved'), 'Declined' => __('Declined')])->required();
    } else {
        $row = $form->addRow();
        $row->addLabel('statusLabel', __('Status'));
        $row->addTextField('statusLabel')->readOnly()->setValue($values['status']);
        $form->addHiddenValue('status', $values['status']);
    }

    if ($alertType['useLevels'] == 'Y') {
        $row = $form->addRow();
            $row->addLabel('level', __('Level'));
            $row->addSelect('level')
                ->fromArray(['High' => __('High'), 'Medium' => __('Medium'), 'Low' => __('Low')])->required();
    }

    // $row = $form->addRow();
    //     $row->addLabel('dateStart', __('Start Date'))->description(__('If the alert is for a specified period'));
    //     $row->addDate('dateStart');

    // $row = $form->addRow();
    //     $row->addLabel('dateEnd', __('End Date'))->description(__('If the alert is for a specified period')); 
    //     $row->addDate('dateEnd');

    $row = $form->addRow();
        $col = $row->addColumn();
        $col->addLabel('comment', __('Comment'));
        $col->addTextArea('comment')->setRows(5);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    $form->loadAllValuesFrom($values);

    echo $form->getOutput();
}
