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

use Gibbon\Domain\Activities\ActivityGateway;
use Gibbon\Domain\School\SchoolYearTermGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\User\UserGateway;
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
        $result = $container->get(ActivityGateway::class)->selectActivityByYearandStaff($session->get('gibbonPersonID'), $session->get('gibbonSchoolYearID'), $gibbonActivityID);

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
        ->add(__('Add Student'));

    $gibbonActivityID = $_GET['gibbonActivityID'] ?? '';

    if ($gibbonActivityID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {

        $values = $container->get(ActivityGateway::class)->getActivityDetailsByID($gibbonActivityID);

        if (empty($values)) {
            $page->addError(__('The specified record does not exist.'));
        } else {
            $settingGateway = $container->get(SettingGateway::class);

            $dateType = $settingGateway->getSettingByScope('Activities', 'dateType');

			$form = Form::create('activityEnrolment', $session->get('absoluteURL').'/modules/'.$session->get('module')."/activities_manage_enrolment_addProcess.php?gibbonActivityID=$gibbonActivityID&search=".$_GET['search']."&gibbonSchoolYearTermID=".$_GET['gibbonSchoolYearTermID']);

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

			$students = array();

		    $result = $container->get(UserGateway::class)->selectUserByFormGroup($values['gibbonYearGroupIDList'], $session->get('gibbonSchoolYearID'));

			if ($result->rowCount() > 0) {
				$students['--'.__('Enrolable Students').'--'] = array_reduce($result->fetchAll(), function($group, $item) {
					$group[$item['gibbonPersonID']] = $item['formGroupName'].' - '. Format::name('', $item['preferredName'], $item['surname'], 'Student', true);
					return $group;
				}, array());
			}

			$result = $container->get(UserGateway::class)->selectUsers();

            if ($result->rowCount() > 0) {
                $students['--'.__('All Users').'--'] = array_reduce($result->fetchAll(), function ($group, $item) {
                    $group[$item['gibbonPersonID']] = Format::name('', $item['preferredName'], $item['surname'], 'Student', true).' ('.$item['username'].')';
                    return $group;
                }, array());
            }

			$row = $form->addRow();
                $row->addLabel('Members[]', __('Students'));
				$row->addSelect('Members[]')->fromArray($students)->selectMultiple()->required();

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
            $statuses['Left'] = __('Left');

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