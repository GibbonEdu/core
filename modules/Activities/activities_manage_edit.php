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
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Services\Format;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Activities/activities_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs
        ->add(__('Manage Activities'), 'activities_manage.php')
        ->add(__('Edit Activity'));
    
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, array('error3' => __('Your request failed due to an attachment error.')));
    }

    //Check if school year specified
    $gibbonActivityID = $_GET['gibbonActivityID'];
    if ($gibbonActivityID == 'Y') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        
            $data = array('gibbonActivityID' => $gibbonActivityID);
            $sql = 'SELECT * FROM gibbonActivity WHERE gibbonActivityID=:gibbonActivityID';
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __('The selected record does not exist, or you do not have access to it.');
            echo '</div>';
        } else {
            //Let's go!
			$values = $result->fetch();

			$search = isset($_GET['search'])? $_GET['search'] : '';
			$gibbonSchoolYearTermID = isset($_GET['gibbonSchoolYearTermID'])? $_GET['gibbonSchoolYearTermID'] : '';

            if ($search != '' || $gibbonSchoolYearTermID != '') {
                echo "<div class='linkTop'>";
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Activities/activities_manage.php&search='.$search."&gibbonSchoolYearTermID=".$gibbonSchoolYearTermID."'>".__('Back to Search Results').'</a>';
                echo '</div>';
			}

			$form = Form::create('activity', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/activities_manage_editProcess.php?gibbonActivityID='.$gibbonActivityID.'&search='.$search.'&gibbonSchoolYearTermID='.$gibbonSchoolYearTermID);
			$form->setFactory(DatabaseFormFactory::create($pdo));

			$form->addHiddenValue('address', $_SESSION[$guid]['address']);

			$form->addRow()->addHeading(__('Basic Information'));

			$row = $form->addRow();
				$row->addLabel('name', __('Name'));
				$row->addTextField('name')->required()->maxLength(40);

			$row = $form->addRow();
				$row->addLabel('provider', __('Provider'));
				$row->addSelect('provider')->required()->fromArray(array('School' => $_SESSION[$guid]['organisationNameShort'], 'External' => __('External')));

			$activityTypes = getSettingByScope($connection2, 'Activities', 'activityTypes');
			if (!empty($activityTypes)) {
				$row = $form->addRow();
					$row->addLabel('type', __('Type'));
					$row->addSelect('type')->fromString($activityTypes)->placeholder();
			}

			$row = $form->addRow();
				$row->addLabel('active', __('Active'));
				$row->addYesNo('active')->required();

			$row = $form->addRow();
				$row->addLabel('registration', __('Registration'))->description(__('Assuming system-wide registration is open, should this activity be open for registration?'));
				$row->addYesNo('registration')->required();

			$dateType = getSettingByScope($connection2, 'Activities', 'dateType');
			$form->addHiddenValue('dateType', $dateType);
			if ($dateType != 'Date') {
				$row = $form->addRow();
					$row->addLabel('gibbonSchoolYearTermIDList', __('Terms'))->description(__('Terms in which the activity will run.'));
					$row->addCheckboxSchoolYearTerm('gibbonSchoolYearTermIDList', $_SESSION[$guid]['gibbonSchoolYearID'])->loadFromCSV($values);
			} else {
				$row = $form->addRow();
					$row->addLabel('listingStart', __('Listing Start Date'))->description(__('Default: 2 weeks before the end of the current term.'));
					$row->addDate('listingStart')->required()->setValue(dateConvertBack($guid, $values['listingStart']));

				$row = $form->addRow();
					$row->addLabel('listingEnd', __('Listing End Date'))->description(__('Default: 2 weeks after the start of next term.'));
					$row->addDate('listingEnd')->required()->setValue(dateConvertBack($guid, $values['listingEnd']));

				$row = $form->addRow();
					$row->addLabel('programStart', __('Program Start Date'))->description(__('Default: first day of next term.'));
					$row->addDate('programStart')->required()->setValue(dateConvertBack($guid, $values['programStart']));

				$row = $form->addRow();
					$row->addLabel('programEnd', __('Program End Date'))->description(__('Default: last day of the next term.'));
					$row->addDate('programEnd')->required()->setValue(dateConvertBack($guid, $values['programEnd']));
			}

			$row = $form->addRow();
				$row->addLabel('gibbonYearGroupIDList', __('Year Groups'));
				$row->addCheckboxYearGroup('gibbonYearGroupIDList')->addCheckAllNone()->loadFromCSV($values);

			$row = $form->addRow();
				$row->addLabel('maxParticipants', __('Max Participants'));
				$row->addNumber('maxParticipants')->required()->maxLength(4);

			$column = $form->addRow()->addColumn();
				$column->addLabel('description', __('Description'));
				$column->addEditor('description', $guid)->setRows(10)->showMedia();

			$payment = getSettingByScope($connection2, 'Activities', 'payment');
			if ($payment != 'None' && $payment != 'Single') {
				$form->addRow()->addHeading(__('Cost'));

				$row = $form->addRow();
					$row->addLabel('payment', __('Cost'));
					$row->addCurrency('payment')->required()->maxLength(9);

				$costTypes = array(
					'Entire Programme' => __('Entire Programme'),
					'Per Session'      => __('Per Session'),
					'Per Week'         => __('Per Week'),
					'Per Term'         => __('Per Term'),
				);

				$row = $form->addRow();
					$row->addLabel('paymentType', __('Cost Type'));
					$row->addSelect('paymentType')->required()->fromArray($costTypes);

				$costStatuses = array(
					'Finalised' => __('Finalised'),
					'Estimated' => __('Estimated'),
				);

				$row = $form->addRow();
					$row->addLabel('paymentFirmness', __('Cost Status'));
					$row->addSelect('paymentFirmness')->required()->fromArray($costStatuses);
			}

			$form->addRow()->addHeading(__('Current Time Slots'));

            $data = array('gibbonActivityID' => $gibbonActivityID);
            $sql = "SELECT gibbonActivitySlot.*, gibbonDaysOfWeek.name, gibbonSpace.name as locationInternal FROM gibbonActivitySlot
					JOIN gibbonDaysOfWeek ON (gibbonActivitySlot.gibbonDaysOfWeekID=gibbonDaysOfWeek.gibbonDaysOfWeekID)
					LEFT JOIN gibbonSpace ON (gibbonSpace.gibbonSpaceID=gibbonActivitySlot.gibbonSpaceID)
					WHERE gibbonActivityID=:gibbonActivityID ORDER BY gibbonDaysOfWeek.gibbonDaysOfWeekID";

            $results = $pdo->executeQuery($data, $sql);

            if ($results->rowCount() == 0) {
                $form->addRow()->addAlert(__('There are no records to display.'), 'error');
            } else {
                $form->addRow()->addContent('<b>'.__('Warning').'</b>: '.__('If you delete a time slot, any unsaved changes to this record will be lost!'))->wrap('<i>', '</i>');

                $table = $form->addRow()->addTable()->addClass('colorOddEven');

                $header = $table->addHeaderRow();
                $header->addContent(__('Day'));
                $header->addContent(__('Time'));
                $header->addContent(__('Location'));
                $header->addContent(__('Action'));

                while ($slot = $results->fetch()) {
                    $row = $table->addRow();

                    $row->addContent(__($slot['name']));
                    $row->addContent(substr($slot['timeStart'], 0, 5).' - '.substr($slot['timeEnd'], 0, 5));
                    $row->addContent(!empty($slot['locationInternal'])? $slot['locationInternal'] : $slot['locationExternal']);
                    $row->addWebLink('<img title="'.__('Delete').'" src="./themes/'.$_SESSION[$guid]['gibbonThemeName'].'/img/garbage.png"/></a>')
                            ->setURL($_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/activities_manage_edit_slot_deleteProcess.php')
                            ->addParam('address', $_GET['q'])
                            ->addParam('gibbonActivitySlotID', $slot['gibbonActivitySlotID'])
                            ->addParam('gibbonActivityID', $gibbonActivityID)
                            ->addParam('search', $search)
                            ->addParam('gibbonSchoolYearTermID', $gibbonSchoolYearTermID)
                            ->addConfirmation(__('Are you sure you wish to delete this record?'));
                }
            }

            $form->addRow()->addHeading(__('New Time Slots'));

            $sqlWeekdays = "SELECT gibbonDaysOfWeekID as value, name FROM gibbonDaysOfWeek ORDER BY sequenceNumber";
            $sqlSpaces = "SELECT gibbonSpaceID as value, name FROM gibbonSpace ORDER BY name";
            $locations = array(
                    'Internal' => __('Internal'),
                    'External' => __('External'),
            );

            for ($i = 1; $i <= 2; ++$i) {
				$form->addRow()->addSubheading(__('Slot').' '.$i)->addClass("slotRow{$i}");
           
                $row = $form->addRow()->addClass("slotRow{$i}");
					$row->addLabel("gibbonDaysOfWeekID{$i}", sprintf(__('Slot %1$d Day'), $i));
                        $row->addSelect("gibbonDaysOfWeekID{$i}")->fromQuery($pdo, $sqlWeekdays)->placeholder();

                $row = $form->addRow()->addClass("slotRow{$i}");
					$row->addLabel('timeStart'.$i, sprintf(__('Slot %1$d Start Time'), $i));
					$row->addTime('timeStart'.$i);

                $row = $form->addRow()->addClass("slotRow{$i}");
                        $row->addLabel("timeEnd{$i}", sprintf(__('Slot %1$d End Time'), $i));
					$row->addTime("timeEnd{$i}")->chainedTo('timeStart'.$i);

                $row = $form->addRow()->addClass("slotRow{$i}");
					$row->addLabel("slot{$i}Location", sprintf(__('Slot %1$d Location'), $i));
                        $row->addRadio("slot{$i}Location")->fromArray($locations)->inline();

                $form->toggleVisibilityByClass("slotRow{$i}Internal")->onRadio("slot{$i}Location")->when('Internal');
                $row = $form->addRow()->addClass("slotRow{$i}Internal");
                        $row->addSelect("gibbonSpaceID{$i}")->fromQuery($pdo, $sqlSpaces)->placeholder();

                $form->toggleVisibilityByClass("slotRow{$i}External")->onRadio("slot{$i}Location")->when('External');
                $row = $form->addRow()->addClass("slotRow{$i}External");
                        $row->addTextField("location{$i}External")->maxLength(50);

                if ($i == 1) {
                        $form->toggleVisibilityByClass("slot{$i}ButtonRow")->onRadio("slot{$i}Location")->when(array('Internal', 'External'));
                        $row = $form->addRow()->addClass("slotRow{$i} slot{$i}ButtonRow");
                        $row->addButton(__('Add Another Slot'))
                                ->onClick("$('.slotRow2').show();$('.slot1ButtonRow').hide();")
                                ->addClass('right buttonAsLink');
                }
            }

            $form->addRow()->addHeading(__('Current Staff'));

            $data = array('gibbonActivityID' => $gibbonActivityID);
            $sql = "SELECT preferredName, surname, gibbonActivityStaff.* FROM gibbonActivityStaff JOIN gibbonPerson ON (gibbonActivityStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonActivityID=:gibbonActivityID AND gibbonPerson.status='Full' ORDER BY surname, preferredName";

            $results = $pdo->executeQuery($data, $sql);

            if ($results->rowCount() == 0) {
                $form->addRow()->addAlert(__('There are no records to display.'), 'error');
            } else {
                $form->addRow()->addContent('<b>'.__('Warning').'</b>: '.__('If you delete a member of staff, any unsaved changes to this record will be lost!'))->wrap('<i>', '</i>');

                $table = $form->addRow()->addTable()->addClass('colorOddEven');

                $header = $table->addHeaderRow();
                $header->addContent(__('Name'));
                $header->addContent(__('Role'));
                $header->addContent(__('Action'));

                while ($staff = $results->fetch()) {
                    $row = $table->addRow();
                        $row->addContent(Format::name('', $staff['preferredName'], $staff['surname'], 'Staff', true, true));
			$row->addContent(__($staff['role']));
			$row->addWebLink('<img title="'.__('Delete').'" src="./themes/'.$_SESSION[$guid]['gibbonThemeName'].'/img/garbage.png"/></a>')
			    ->setURL($_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/activities_manage_edit_staff_deleteProcess.php')
			    ->addParam('address', $_GET['q'])
			    ->addParam('gibbonActivityStaffID', $staff['gibbonActivityStaffID'])
			    ->addParam('gibbonActivityID', $gibbonActivityID)
			    ->addParam('search', $search)
			    ->addParam('gibbonSchoolYearTermID', $gibbonSchoolYearTermID)
			    ->addConfirmation(__('Are you sure you wish to delete this record?'));
                }
            }

            $form->addRow()->addHeading(__('New Staff'));

			$row = $form->addRow();
				$row->addLabel('staff', __('Staff'));
				$row->addSelectUsers('staff', $_SESSION[$guid]['gibbonSchoolYearID'], array('includeStaff' => true))->selectMultiple();
			
			$staffRoles = array(
				'Organiser' => __('Organiser'),
				'Coach'     => __('Coach'),
				'Assistant' => __('Assistant'),
				'Other'     => __('Other'),
			);

			$row = $form->addRow();
				$row->addLabel('role', __('Role'));
				$row->addSelect('role')->fromArray($staffRoles);

			$row = $form->addRow();
				$row->addFooter();
				$row->addSubmit();

			$form->loadAllValuesFrom($values);

			echo $form->getOutput();
			?>

			<script type="text/javascript">
			$(document).ready(function(){
				$('.slotRow2').hide();
			});
			</script>

			<?php
        }
    }
}
