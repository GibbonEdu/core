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
        'gibbonSchoolYearTermID' => $_GET['gibbonSchoolYearTermID'] ?? null
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

    // FORM
    $form = BulkActionForm::create('bulkAction', $session->get('absoluteURL').'/modules/'.$session->get('module').'/activities_manageProcessBulk.php');
    $form->addHiddenValue('search', $search);

    $table = $form->addRow()->addDataTable('activities', $criteria)->withData($enrolment);

    // DATA TABLE
    $table = DataTable::create('enrolment');
    $table->setTitle(__('Participants'));

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/Activities/activities_manage_enrolment_add.php')
        ->addParam('gibbonActivityID', $gibbonActivityID)
        ->addParam('search', $params['search'])
        ->addParam('gibbonSchoolYearTermID', $params['gibbonSchoolYearTermID'])
        ->displayLabel();

    $table->modifyRows(function ($values, $row) {
        if ($values['status'] == 'Left') $row->addClass('error');
        return $row;
    });

    $table->addColumn('student', __('Student'))
        ->sortable(['surname', 'preferredName'])
        ->format(function ($person)  {
            return Format::name('', $person['preferredName'], $person['surname'], 'Student', true, true);
        });

    $table->addColumn('formGroup', __('Form Group'));

    $table->addColumn('status', __('Status'));

    $table->addColumn('timestamp', __('Timestamp'))->format(Format::using('timestamp', 'timestamp'));


    // ACTIONS
    $table->addActionColumn()
        ->addParam('gibbonActivityID')
        ->addParam('gibbonPersonID')
        ->format(function ($activity, $actions) {
            $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Activities/activities_manage_enrolment_edit.php');

            $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/Activities/activities_manage_enrolment_delete.php');
        });

    $table->addCheckboxColumn('gibbonActivityStudentID');

    echo $form->getOutput();


    return;

        $data = array('gibbonActivityID' => $gibbonActivityID, 'today' => date('Y-m-d'), 'statusCheck' => ($enrolment == 'Competitive'? 'Pending' : 'Waiting List'));
        $sql = "SELECT gibbonActivityStudent.*, surname, preferredName, gibbonFormGroup.nameShort as formGroupNameShort
                FROM gibbonActivityStudent
                JOIN gibbonPerson ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID)
                LEFT JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=(SELECT gibbonSchoolYearID FROM gibbonSchoolYear WHERE status='Current'))
                LEFT JOIN gibbonFormGroup ON (gibbonFormGroup.gibbonFormGroupID=gibbonStudentEnrolment.gibbonFormGroupID)
                WHERE gibbonActivityID=:gibbonActivityID
                AND NOT gibbonActivityStudent.status=:statusCheck
                AND gibbonPerson.status='Full'
                AND (dateStart IS NULL OR dateStart<=:today) AND (dateEnd IS NULL OR dateEnd>=:today)
                ORDER BY gibbonActivityStudent.status, timestamp";
        $result = $connection2->prepare($sql);
        $result->execute($data);

    echo "<div class='linkTop'>";
    echo "<a href='".$session->get('absoluteURL').'/index.php?q=/modules/'.$session->get('module')."/activities_manage_enrolment_add.php&gibbonActivityID=$gibbonActivityID&search=".$_GET['search'].'&gibbonSchoolYearTermID='.$_GET['gibbonSchoolYearTermID']."'>".__('Add')."<img style='margin-left: 5px' title='".__('Add')."' src='./themes/".$session->get('gibbonThemeName')."/img/page_new.png'/></a>";
    echo '</div>';

    if ($result->rowCount() < 1) {
        echo "<div class='error'>";
        echo __('There are no records to display.');
        echo '</div>';
    } else {
        echo "<table cellspacing='0' style='width: 100%'>";
        echo "<tr class='head'>";
        echo '<th>';
        echo __('Student');
        echo '</th>';
        echo '<th>';
        echo __('Form Group');
        echo '</th>';
        echo '<th>';
        echo __('Status');
        echo '</th>';
        echo '<th>';
        echo __('Timestamp');
        echo '</th>';
        echo '<th>';
        echo __('Actions');
        echo '</th>';
        echo '</tr>';

        $canViewStudentDetails = isActionAccessible($guid, $connection2, '/modules/Students/student_view_details.php');

        $count = 0;
        $rowNum = 'odd';
        while ($activity = $result->fetch()) {
            if ($count % 2 == 0) {
                $rowNum = 'even';
            } else {
                $rowNum = 'odd';
            }
            ++$count;

            //COLOR ROW BY STATUS!
            echo "<tr class=$rowNum>";
            echo '<td>';
            $studentName = Format::name('', $activity['preferredName'], $activity['surname'], 'Student', true);
            if ($canViewStudentDetails) {
                echo sprintf('<a href="%2$s">%1$s</a>', $studentName, $session->get('absoluteURL').'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$activity['gibbonPersonID'].'&subpage=Activities');
            } else {
                echo $studentName;
            }
            echo '</td>';
            echo '<td>';
            echo $activity['formGroupNameShort'];
            echo '</td>';
            echo '<td>';
            echo __($activity['status']);
            echo '</td>';
            echo '<td>';
            echo __('{date} at {time}',
                    ['date' => Format::date(substr($activity['timestamp'], 0, 10)),
                    'time' => substr($activity['timestamp'], 11, 5)]);
            echo '</td>';
            echo '<td>';
            echo "<a href='".$session->get('absoluteURL').'/index.php?q=/modules/'.$session->get('module').'/activities_manage_enrolment_edit.php&gibbonActivityID='.$activity['gibbonActivityID'].'&gibbonPersonID='.$activity['gibbonPersonID'].'&search='.$_GET['search'].'&gibbonSchoolYearTermID='.$_GET['gibbonSchoolYearTermID']."'><img title='".__('Edit')."' src='./themes/".$session->get('gibbonThemeName')."/img/config.png'/></a> ";
            echo "<a class='thickbox' href='".$session->get('absoluteURL').'/fullscreen.php?q=/modules/'.$session->get('module').'/activities_manage_enrolment_delete.php&gibbonActivityID='.$activity['gibbonActivityID'].'&gibbonPersonID='.$activity['gibbonPersonID'].'&search='.$_GET['search'].'&gibbonSchoolYearTermID='.$_GET['gibbonSchoolYearTermID']."&width=650&height=135'><img title='".__('Delete')."' src='./themes/".$session->get('gibbonThemeName')."/img/garbage.png'/></a> ";
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }

}
