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

use Gibbon\Domain\School\SchoolYearTermGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Http\Url;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Activities/activities_manage_enrolment.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $gibbonActivityID = (isset($_GET['gibbonActivityID']))? $_GET['gibbonActivityID'] : null;

    $highestAction = getHighestGroupedAction($guid, '/modules/Activities/activities_manage_enrolment.php', $connection2);
    if ($highestAction == 'My Activities_viewEditEnrolment') {

            $data = array('gibbonPersonID' => $gibbon->session->get('gibbonPersonID'), 'gibbonSchoolYearID' => $gibbon->session->get('gibbonSchoolYearID'), 'gibbonActivityID' => $gibbonActivityID);
            $sql = "SELECT gibbonActivity.*, NULL as status, gibbonActivityStaff.role FROM gibbonActivity JOIN gibbonActivityStaff ON (gibbonActivity.gibbonActivityID=gibbonActivityStaff.gibbonActivityID) WHERE gibbonActivity.gibbonActivityID=:gibbonActivityID AND gibbonActivityStaff.gibbonPersonID=:gibbonPersonID AND gibbonActivityStaff.role='Organiser' AND gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' ORDER BY name";
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if (!$result || $result->rowCount() == 0) {
            //Acess denied
            echo "<div class='error'>";
            echo __('You do not have access to this action.');
            echo '</div>';
            return;
        }
    }

    $page->breadcrumbs
        ->add(__('Manage Activities'), 'activities_manage.php')
        ->add(__('Activity Enrolment'));

    //Check if gibbonActivityID specified
    if ($gibbonActivityID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {

            $data = array('gibbonActivityID' => $gibbonActivityID);
            $sql = 'SELECT gibbonActivity.*, gibbonActivityType.access, gibbonActivityType.maxPerStudent, gibbonActivityType.enrolmentType, gibbonActivityType.backupChoice FROM gibbonActivity LEFT JOIN gibbonActivityType ON (gibbonActivity.type=gibbonActivityType.name) WHERE gibbonActivityID=:gibbonActivityID';
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __('The selected record does not exist, or you do not have access to it.');
            echo '</div>';
        } else {
            //Let's go!
            $values = $result->fetch();
            $settingGateway = $container->get(SettingGateway::class);
            $dateType = $settingGateway->getSettingByScope('Activities', 'dateType');
            if ($_GET['search'] != '' || $_GET['gibbonSchoolYearTermID'] != '') {
                $params = [
                    "search" => $_GET['search'] ?? '',
                    "gibbonSchoolYearTermID" => $_GET['gibbonSchoolYearTermID'] ?? null
                ];
                $page->navigator->addSearchResultsAction(Url::fromModuleRoute('Activities', 'activities_manage.php')->withQueryParams($params));
            }

            $form = Form::create('activityEnrolment', $gibbon->session->get('absoluteURL').'/index.php');

            $row = $form->addRow();
                $row->addLabel('nameLabel', __('Name'));
                $row->addTextField('name')->readOnly()->setValue($values['name']);

            if ($dateType == 'Date') {
                $row = $form->addRow();
                $row->addLabel('listingDatesLabel', __('Listing Dates'));
                $row->addTextField('listingDates')->readOnly()->setValue(Format::date($values['listingStart']).'-'.Format::date($values['listingEnd']));

                $row = $form->addRow();
                $row->addLabel('programDatesLabel', __('Program Dates'));
                $row->addTextField('programDates')->readOnly()->setValue(Format::date($values['programStart']).'-'.Format::date($values['programEnd']));
            } else {
                /**
                 * @var SchoolYearTermGateway
                 */
                $schoolYearTermGateway = $container->get(SchoolYearTermGateway::class);
                $termList = $schoolYearTermGateway->getTermNamesByID($values['gibbonSchoolYearTermIDList']);

                $row = $form->addRow();
                $row->addLabel('termsLabel', __('Terms'));
                $row->addTextField('terms')->readOnly()->setValue(!empty($termList)? implode(', ', $termList) : '-');
            }
            echo $form->getOutput();


            $enrolment = $settingGateway->getSettingByScope('Activities', 'enrolmentType');
            $enrolment = !empty($values['enrolmentType'])? $values['enrolmentType'] : $enrolment;

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
            echo "<a href='".$gibbon->session->get('absoluteURL').'/index.php?q=/modules/'.$gibbon->session->get('module')."/activities_manage_enrolment_add.php&gibbonActivityID=$gibbonActivityID&search=".$_GET['search'].'&gibbonSchoolYearTermID='.$_GET['gibbonSchoolYearTermID']."'>".__('Add')."<img style='margin-left: 5px' title='".__('Add')."' src='./themes/".$gibbon->session->get('gibbonThemeName')."/img/page_new.png'/></a>";
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
                while ($values = $result->fetch()) {
                    if ($count % 2 == 0) {
                        $rowNum = 'even';
                    } else {
                        $rowNum = 'odd';
                    }
                    ++$count;

                    //COLOR ROW BY STATUS!
                    echo "<tr class=$rowNum>";
                    echo '<td>';
                    $studentName = Format::name('', $values['preferredName'], $values['surname'], 'Student', true);
                    if ($canViewStudentDetails) {
                        echo sprintf('<a href="%2$s">%1$s</a>', $studentName, $gibbon->session->get('absoluteURL').'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$values['gibbonPersonID'].'&subpage=Activities');
                    } else {
                        echo $studentName;
                    }
                    echo '</td>';
                    echo '<td>';
                    echo $values['formGroupNameShort'];
                    echo '</td>';
                    echo '<td>';
                    echo __($values['status']);
                    echo '</td>';
                    echo '<td>';
                    echo __('{date} at {time}',
                            ['date' => Format::date(substr($values['timestamp'], 0, 10)),
                            'time' => substr($values['timestamp'], 11, 5)]);
                    echo '</td>';
                    echo '<td>';
                    echo "<a href='".$gibbon->session->get('absoluteURL').'/index.php?q=/modules/'.$gibbon->session->get('module').'/activities_manage_enrolment_edit.php&gibbonActivityID='.$values['gibbonActivityID'].'&gibbonPersonID='.$values['gibbonPersonID'].'&search='.$_GET['search'].'&gibbonSchoolYearTermID='.$_GET['gibbonSchoolYearTermID']."'><img title='".__('Edit')."' src='./themes/".$gibbon->session->get('gibbonThemeName')."/img/config.png'/></a> ";
                    echo "<a class='thickbox' href='".$gibbon->session->get('absoluteURL').'/fullscreen.php?q=/modules/'.$gibbon->session->get('module').'/activities_manage_enrolment_delete.php&gibbonActivityID='.$values['gibbonActivityID'].'&gibbonPersonID='.$values['gibbonPersonID'].'&search='.$_GET['search'].'&gibbonSchoolYearTermID='.$_GET['gibbonSchoolYearTermID']."&width=650&height=135'><img title='".__('Delete')."' src='./themes/".$gibbon->session->get('gibbonThemeName')."/img/garbage.png'/></a> ";
                    echo '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            }
        }
    }
}
?>
