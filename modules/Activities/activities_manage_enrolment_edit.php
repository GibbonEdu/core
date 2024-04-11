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

use Gibbon\Domain\School\SchoolYearTermGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Activities/activities_manage_enrolment_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $gibbonActivityID = (isset($_GET['gibbonActivityID']))? $_GET['gibbonActivityID'] : null;

    $highestAction = getHighestGroupedAction($guid, '/modules/Activities/activities_manage_enrolment.php', $connection2);
    if ($highestAction == 'My Activities_viewEditEnrolment') {


            $data = array('gibbonPersonID' => $session->get('gibbonPersonID'), 'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonActivityID' => $gibbonActivityID);
            $sql = "SELECT gibbonActivity.*, NULL as status, gibbonActivityStaff.role FROM gibbonActivity JOIN gibbonActivityStaff ON (gibbonActivity.gibbonActivityID=gibbonActivityStaff.gibbonActivityID) WHERE gibbonActivity.gibbonActivityID=:gibbonActivityID AND gibbonActivityStaff.gibbonPersonID=:gibbonPersonID AND gibbonActivityStaff.role='Organiser' AND gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' ORDER BY name";
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if (!$result || $result->rowCount() == 0) {
            //Acess denied
            $page->addError(__('You do not have access to this action.'));
            return;
        }
    }

    $urlParams = ['gibbonActivityID' => $_GET['gibbonActivityID'], 'search' => $_GET['search'] ?? '', 'gibbonSchoolYearTermID' => $_GET['gibbonSchoolYearTermID'] ?? ''];

    $page->breadcrumbs
        ->add(__('Manage Activities'), 'activities_manage.php')
        ->add(__('Activity Enrolment'), 'activities_manage_enrolment.php',  $urlParams)
        ->add(__('Edit Enrolment'));

    //Check if gibbonActivityID and gibbonPersonID specified
    $gibbonActivityID = $_GET['gibbonActivityID'] ?? '';
    $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
    if ($gibbonPersonID == '' or $gibbonActivityID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {

            $data = array('gibbonActivityID' => $gibbonActivityID, 'gibbonPersonID' => $gibbonPersonID);
            $sql = 'SELECT gibbonActivity.*, gibbonActivityStudent.*, surname, preferredName, gibbonActivityType.access, gibbonActivityType.maxPerStudent, gibbonActivityType.enrolmentType, gibbonActivityType.backupChoice, gibbonActivityType.waitingList FROM gibbonActivity JOIN gibbonActivityStudent ON (gibbonActivity.gibbonActivityID=gibbonActivityStudent.gibbonActivityID) JOIN gibbonPerson ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) LEFT JOIN gibbonActivityType ON (gibbonActivity.type=gibbonActivityType.name) WHERE gibbonActivityStudent.gibbonActivityID=:gibbonActivityID AND gibbonActivityStudent.gibbonPersonID=:gibbonPersonID';
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            $page->addError(__('The specified record cannot be found.'));
        } else {
            //Let's go!
            $values = $result->fetch();

            $settingGateway = $container->get(SettingGateway::class);
            $dateType = $settingGateway->getSettingByScope('Activities', 'dateType');

            $form = Form::create('activityEnrolment', $session->get('absoluteURL').'/modules/'.$session->get('module')."/activities_manage_enrolment_editProcess.php?gibbonActivityID=$gibbonActivityID&gibbonPersonID=$gibbonPersonID&search=".$_GET['search']."&gibbonSchoolYearTermID=".$_GET['gibbonSchoolYearTermID']);

            if ($_GET['search'] != '' || $_GET['gibbonSchoolYearTermID'] != '') {
                $params = [
                    "search" => $_GET['search'] ?? '',
                    "gibbonSchoolYearTermID" => $_GET['gibbonSchoolYearTermID'] ?? null,
                    "gibbonActivityID" => $gibbonActivityID
                ];
                $form->addHeaderAction('back', __('Back'))
                    ->setURL('/modules/Activities/activities_manage_enrolment.php')
                    ->addParams($params);
			}

            $form->addHiddenValue('address', $session->get('address'));
            $form->addHiddenValue('gibbonPersonID', $gibbonPersonID);

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

            $row = $form->addRow();
            $row->addLabel('student', __('Student'));
            $row->addTextField('student')->readOnly()->setValue(Format::name('', htmlPrep($values['preferredName']), htmlPrep($values['surname']), 'Student'));

            // Load the enrolmentType system setting, optionally override with the Activity Type setting
            $enrolment = $settingGateway->getSettingByScope('Activities', 'enrolmentType');
            $enrolment = !empty($values['enrolmentType'])? $values['enrolmentType'] : $enrolment;

            $statuses = ['Accepted' => __('Accepted')];
            if ($enrolment == 'Competitive') {
                if (!empty($values['waitingList']) && $values['waitingList'] == 'Y') {
                    $statuses['Waiting List'] = __('Waiting List');
                }
            } else {
                $statuses['Pending'] = __('Pending');
            }
            $statuses['Not Accepted'] = __('Not Accepted');
            $statuses['Left'] = __('Left');

            $row = $form->addRow();
            $row->addLabel('status', __('Status'));
            $row->addSelect('status')->fromArray($statuses)->required();

            $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit();

            $form->loadAllValuesFrom($values);

            echo $form->getOutput();
        }
    }
}
