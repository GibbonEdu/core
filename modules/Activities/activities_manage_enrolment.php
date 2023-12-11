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

use Gibbon\Http\Url;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\School\SchoolYearTermGateway;
use Gibbon\Domain\Activities\ActivityStudentGateway;
use Gibbon\Domain\Activities\ActivityStaffGateway;
use Gibbon\Domain\Activities\ActivityGateway;
use Gibbon\Tables\DataTable;
use Gibbon\Forms\Prefab\BulkActionForm;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Activities/activities_manage_enrolment.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $gibbonActivityID = $_GET['gibbonActivityID'] ?? '';
    $params = [
        'search' => $_GET['search'] ?? '',
        'gibbonSchoolYearTermID' => $_GET['gibbonSchoolYearTermID'] ?? ''
    ];

    if (empty($gibbonActivityID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $settingGateway = $container->get(SettingGateway::class);
    $activityStudentGateway = $container->get(ActivityStudentGateway::class);
    $activityStaffGateway = $container->get(ActivityStaffGateway::class);
    
    $highestAction = getHighestGroupedAction($guid, '/modules/Activities/activities_manage_enrolment.php', $connection2);
    if ($highestAction == 'My Activities_viewEditEnrolment') {
        $organiser = $activityStaffGateway->selectActivityOrganiserByPerson($gibbonActivityID, $session->get('gibbonPersonID'));
        if (empty($organiser)) {
            $page->addError(__('You do not have access to this action.'));
            return;
        }
    }

    $page->breadcrumbs
        ->add(__('Manage Activities'), 'activities_manage.php')
        ->add(__('Activity Enrolment'));

    $activity = $container->get(ActivityGateway::class)->getActivityDetailsByID($gibbonActivityID);

    if (empty($activity)) {
        $page->addError(__('The selected record does not exist, or you do not have access to it.'));
        return;
    }

    //Let's go!
    $dateType = $settingGateway->getSettingByScope('Activities', 'dateType');
    if (!empty($params['search']) || !empty($params['gibbonSchoolYearTermID'])) {
        $page->navigator->addSearchResultsAction(Url::fromModuleRoute('Activities', 'activities_manage.php')->withQueryParams($params));
    }

    // FORM
    $form = Form::create('activityEnrolment', $session->get('absoluteURL').'/index.php');

    $row = $form->addRow();
        $row->addLabel('nameLabel', __('Name'));
        $row->addTextField('name')->readOnly()->setValue($activity['name']);

    if ($dateType == 'Date') {
        $row = $form->addRow();
        $row->addLabel('listingDatesLabel', __('Listing Dates'));
        $row->addTextField('listingDates')->readOnly()->setValue(Format::date($activity['listingStart']).'-'.Format::date($activity['listingEnd']));

        $row = $form->addRow();
        $row->addLabel('programDatesLabel', __('Program Dates'));
        $row->addTextField('programDates')->readOnly()->setValue(Format::date($activity['programStart']).'-'.Format::date($activity['programEnd']));
    } else {
        $schoolYearTermGateway = $container->get(SchoolYearTermGateway::class);
        $termList = $schoolYearTermGateway->getTermNamesByID($activity['gibbonSchoolYearTermIDList']);

        $row = $form->addRow();
        $row->addLabel('termsLabel', __('Terms'));
        $row->addTextField('terms')->readOnly()->setValue(!empty($termList)? implode(', ', $termList) : '-');
    }
    echo $form->getOutput();


    $enrolmentType = $settingGateway->getSettingByScope('Activities', 'enrolmentType');
    $enrolmentType = !empty($activity['enrolmentType'])? $activity['enrolmentType'] : $enrolmentType;


    // QUERY
    $criteria = $activityStudentGateway->newQueryCriteria()
        ->sortBy(['sortOrder', 'surname', 'preferredName'])
        ->fromPOST();

    $enrolment = $activityStudentGateway->queryActivityEnrolment($criteria, $gibbonActivityID);

    // FORM
    $form = BulkActionForm::create('bulkAction', $session->get('absoluteURL').'/modules/Activities/activities_manage_enrolmentProcessBulk.php');
    $form->addHiddenValue('gibbonSchoolYearTermID', $params['gibbonSchoolYearTermID']);
    $form->addHiddenValue('search', $criteria->getSearchText());
    $form->addHiddenValue('gibbonActivityID', $gibbonActivityID);

    $col = $form->createBulkActionColumn([
        'Mark as Accepted' => __('Mark as Accepted'),
        'Mark as Left' => __('Mark as Left'),
        'Delete' => __('Delete'),
    ]);
    $col->addSubmit(__('Go'));

    // DATA TABLE
    $table = $form->addRow()->addDataTable('enrolment', $criteria)->withData($enrolment);
    $table->setTitle(__('Participants'));

    $table->addMetaData('bulkActions', $col);

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/Activities/activities_manage_enrolment_add.php')
        ->addParam('gibbonActivityID', $gibbonActivityID)
        ->addParam('search', $criteria->getSearchText())
        ->addParam('gibbonSchoolYearTermID', $params['gibbonSchoolYearTermID'])
        ->displayLabel();

    $table->modifyRows(function ($values, $row) {
        if ($values['status'] == 'Pending') $row->addClass('warning');
        if ($values['status'] == 'Waiting List') $row->addClass('warning');
        if ($values['status'] == 'Not Accepted') $row->addClass('error');
        if ($values['status'] == 'Left') $row->addClass('error');
        return $row;
    });

    $table->addColumn('student', __('Student'))
        ->sortable(['surname', 'preferredName'])
        ->format(Format::using('nameLinked', ['gibbonPersonID', '', 'preferredName', 'surname', 'Student', true, false, ['subpage' => 'Activities']]));

    $table->addColumn('formGroup', __('Form Group'));

    $table->addColumn('status', __('Status'))->sortable('sortOrder');

    $table->addColumn('timestamp', __('Timestamp'))->format(Format::using('dateTime', 'timestamp'));

    // ACTIONS
    $table->addActionColumn()
        ->addParam('search',$criteria->getSearchText())
        ->addParam('gibbonSchoolYearTermID', $params['gibbonSchoolYearTermID'])
        ->addParam('gibbonActivityStudentID')
        ->addParam('gibbonActivityID', $gibbonActivityID)
        ->addParam('gibbonPersonID')
        ->format(function ($activity, $actions) {
            $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Activities/activities_manage_enrolment_edit.php');

            $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/Activities/activities_manage_enrolment_delete.php');
        });

    $table->addCheckboxColumn('gibbonActivityStudentID');

    echo $form->getOutput();
}
