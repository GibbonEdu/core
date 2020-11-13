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

use Gibbon\Forms\Form;
use Gibbon\Services\Format;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Activities/activities_manage_enrolment_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $gibbonActivityID = (isset($_GET['gibbonActivityID']))? $_GET['gibbonActivityID'] : null;

    $highestAction = getHighestGroupedAction($guid, '/modules/Activities/activities_manage_enrolment.php', $connection2);
    if ($highestAction == 'My Activities_viewEditEnrolment') {

        
            $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonActivityID' => $gibbonActivityID);
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
    
    $urlParams = ['gibbonActivityID' => $_GET['gibbonActivityID'], 'search' => $_GET['search'], 'gibbonSchoolYearTermID' => $_GET['gibbonSchoolYearTermID']];
    
    $page->breadcrumbs
        ->add(__('Manage Activities'), 'activities_manage.php')
        ->add(__('Activity Enrolment'), 'activities_manage_enrolment.php',  $urlParams)
        ->add(__('Add Student'));   

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $gibbonActivityID = $_GET['gibbonActivityID'];

    if ($gibbonActivityID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        
            $data = array('gibbonActivityID' => $gibbonActivityID);
            $sql = 'SELECT * FROM gibbonActivity WHERE gibbonActivityID=:gibbonActivityID';
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __('The specified record does not exist.');
            echo '</div>';
        } else {
            $values = $result->fetch();
            $dateType = getSettingByScope($connection2, 'Activities', 'dateType');
            if ($_GET['search'] != '' || $_GET['gibbonSchoolYearTermID'] != '') {
                echo "<div class='linkTop'>";
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Activities/activities_manage_enrolment.php&search='.$_GET['search']."&gibbonSchoolYearTermID=".$_GET['gibbonSchoolYearTermID']."&gibbonActivityID=$gibbonActivityID'>".__('Back').'</a>';
                echo '</div>';
			}
			
			$form = Form::create('activityEnrolment', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/activities_manage_enrolment_addProcess.php?gibbonActivityID=$gibbonActivityID&search=".$_GET['search']."&gibbonSchoolYearTermID=".$_GET['gibbonSchoolYearTermID']);
			
			$form->addHiddenValue('address', $_SESSION[$guid]['address']);

            $row = $form->addRow();
                $row->addLabel('nameLabel', __('Name'));
                $row->addTextField('name')->readOnly()->setValue($values['name']);

            if ($dateType == 'Date') {
                $row = $form->addRow();
                $row->addLabel('listingDatesLabel', __('Listing Dates'));
                $row->addTextField('listingDates')->readOnly()->setValue(dateConvertBack($guid, $values['listingStart']).'-'.dateConvertBack($guid, $values['listingEnd']));

                $row = $form->addRow();
                $row->addLabel('programDatesLabel', __('Program Dates'));
                $row->addTextField('programDates')->readOnly()->setValue(dateConvertBack($guid, $values['programStart']).'-'.dateConvertBack($guid, $values['programEnd']));
            } else {
                $schoolTerms = getTerms($connection2, $_SESSION[$guid]['gibbonSchoolYearID']);
                $termList = array_filter(array_map(function ($item) use ($schoolTerms) {
                    $index = array_search($item, $schoolTerms);
                    return ($index !== false && isset($schoolTerms[$index+1]))? $schoolTerms[$index+1] : '';
                }, explode(',', $values['gibbonSchoolYearTermIDList'])));
                $termList = (!empty($termList))? implode(', ', $termList) : '-';
                                            
                $row = $form->addRow();
                $row->addLabel('termsLabel', __('Terms'));
                $row->addTextField('terms')->readOnly()->setValue($termList);
			}

			$students = array();
			$data = array('gibbonYearGroupIDList' => $values['gibbonYearGroupIDList'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'date' => date('Y-m-d'));
			$sql = "SELECT gibbonPerson.gibbonPersonID, preferredName, surname, gibbonRollGroup.name AS rollGroupName 
					FROM gibbonPerson
					JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) 
					JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID)
					JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID)
					WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
					AND FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, :gibbonYearGroupIDList)
					AND gibbonPerson.status='FULL' 
					AND (dateStart IS NULL OR dateStart<=:date) AND (dateEnd IS NULL  OR dateEnd>=:date) 
					ORDER BY rollGroupName, gibbonPerson.surname, gibbonPerson.preferredName";
			$result = $pdo->executeQuery($data, $sql);

			if ($result->rowCount() > 0) {
				$students['--'.__('Enrolable Students').'--'] = array_reduce($result->fetchAll(), function($group, $item) {
					$group[$item['gibbonPersonID']] = $item['rollGroupName'].' - '. Format::name('', $item['preferredName'], $item['surname'], 'Student', true);
					return $group;
				}, array());
			}

            $sql = "SELECT gibbonPersonID, surname, preferredName, status, username FROM gibbonPerson WHERE status='Full' OR status='Expected' ORDER BY surname, preferredName";
			$result = $pdo->executeQuery(array(), $sql);

            if ($result->rowCount() > 0) {
                $students['--'.__('All Users').'--'] = array_reduce($result->fetchAll(), function ($group, $item) {
                    $group[$item['gibbonPersonID']] = Format::name('', $item['preferredName'], $item['surname'], 'Student', true).' ('.$item['username'].')';
                    return $group;
                }, array());
            }

			$row = $form->addRow();
                $row->addLabel('Members[]', __('Students'));
				$row->addSelect('Members[]')->fromArray($students)->selectMultiple()->required();
				
			$statuses = array('Accepted' => __('Accepted'));
			$enrolment = getSettingByScope($connection2, 'Activities', 'enrolmentType');
			if ($enrolment == 'Competitive') {
				$statuses['Waiting List'] = __('Waiting List');
			} else {
				$statuses['Pending'] = __('Pending');
			}

			$row = $form->addRow();
                $row->addLabel('status', __('Status'));
                $row->addSelect('status')->fromArray($statuses)->required();
			
			$row = $form->addRow();
                $row->addFooter();
				$row->addSubmit();
				
			echo $form->getOutput();
        }
    }
}
