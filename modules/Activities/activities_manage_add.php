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

@session_start();

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Activities/activities_manage_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Activities/activities_manage.php'>".__($guid, 'Manage Activities')."</a> > </div><div class='trailEnd'>".__($guid, 'Add Activity').'</div>';
    echo '</div>';

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Activities/activities_manage_edit.php&gibbonActivityID='.$_GET['editID'].'&search='.$_GET['search'].'&gibbonSchoolYearTermID='.$_GET['gibbonSchoolYearTermID'];
    }
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], $editLink, null);
    }

    if ($_GET['search'] != '' || $_GET['gibbonSchoolYearTermID'] != '') {
        echo "<div class='linkTop'>";
        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Activities/activities_manage.php&search='.$_GET['search']."&gibbonSchoolYearTermID=".$_GET['gibbonSchoolYearTermID']."'>".__($guid, 'Back to Search Results').'</a>';
        echo '</div>';
	}

	$search = isset($_GET['search'])? $_GET['search'] : null;
	
	$form = Form::create('activity', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/activities_manage_addProcess.php?search='.$search.'&gibbonSchoolYearTermID='.$_GET['gibbonSchoolYearTermID']);
	$form->setFactory(DatabaseFormFactory::create($pdo));
	
	$form->addHiddenValue('address', $_SESSION[$guid]['address']);
	
	$form->addRow()->addHeading(__('Basic Information'));

	$row = $form->addRow();
        $row->addLabel('name', __('Name'));
		$row->addTextField('name')->isRequired()->maxLength(40);
		
	$row = $form->addRow();
        $row->addLabel('provider', __('Provider'));
        $row->addSelect('provider')->isRequired()->fromArray(array('School' => $_SESSION[$guid]['organisationNameShort'], 'External' => __('External')));
	
	$activityTypes = getSettingByScope($connection2, 'Activities', 'activityTypes');
	if (!empty($activityTypes)) {
		$row = $form->addRow();
        	$row->addLabel('type', __('Type'));
        	$row->addSelect('type')->fromString($activityTypes);
	}

	$row = $form->addRow();
        $row->addLabel('active', __('Active'));
		$row->addYesNo('active')->isRequired();
		
	$row = $form->addRow();
        $row->addLabel('registration', __('Registration'))->description(__('Assuming system-wide registration is open, should this activity be open for registration?'));
		$row->addYesNo('registration')->isRequired();
		
	$dateType = getSettingByScope($connection2, 'Activities', 'dateType');
	$form->addHiddenValue('dateType', $dateType);
	if ($dateType != 'Date') {
		$row = $form->addRow();
            $row->addLabel('gibbonSchoolYearTermIDList', __('Terms'))->description(__('Terms in which the activity will run.'));
            $row->addCheckboxSchoolYearTerm('gibbonSchoolYearTermIDList', $_SESSION[$guid]['gibbonSchoolYearID'])->checkAll();
	} else {
		$listingStart = $listingEnd = $programStart = $programEnd = new DateTime();

		$data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'today' => date('Y-m-d'));
		$sql = "SELECT * FROM gibbonSchoolYearTerm WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND lastDay>=:today ORDER BY sequenceNumber";
		$result = $pdo->executeQuery($data, $sql);
		if ($result->rowCount() > 0) {
			if ($currentTerm = $result->fetch()) {
				$listingStart = (new DateTime($currentTerm['lastDay']))->modify('-2 weeks');
			}

			if ($nextTerm = $result->fetch()) {
				$listingEnd = (new DateTime($nextTerm['firstDay']))->modify('+2 weeks');
				$programStart = new DateTime($nextTerm['firstDay']);
				$programEnd = new DateTime($nextTerm['lastDay']);
			}
		}

		$row = $form->addRow();
        	$row->addLabel('listingStart', __('Listing Start Date'))->description(__('Default: 2 weeks before the end of the current term.'));
			$row->addDate('listingStart')->isRequired()->setValue($listingStart->format($_SESSION[$guid]['i18n']['dateFormatPHP']));
			
		$row = $form->addRow();
        	$row->addLabel('listingEnd', __('Listing End Date'))->description(__('Default: 2 weeks after the start of next term.'));
			$row->addDate('listingEnd')->isRequired()->setValue($listingEnd->format($_SESSION[$guid]['i18n']['dateFormatPHP']));
			
		$row = $form->addRow();
        	$row->addLabel('programStart', __('Program Start Date'))->description(__('Default: first day of next term.'));
			$row->addDate('programStart')->isRequired()->setValue($programStart->format($_SESSION[$guid]['i18n']['dateFormatPHP']));
			
		$row = $form->addRow();
        	$row->addLabel('programEnd', __('Program End Date'))->description(__('Default: last day of the next term.'));
			$row->addDate('programEnd')->isRequired()->setValue($programEnd->format($_SESSION[$guid]['i18n']['dateFormatPHP']));
	}

	$row = $form->addRow();
        $row->addLabel('gibbonYearGroupIDList', __('Year Groups'));
        $row->addCheckboxYearGroup('gibbonYearGroupIDList')->checkAll()->addCheckAllNone();
	
	$row = $form->addRow();
        $row->addLabel('maxParticipants', __('Max Participants'));
		$row->addNumber('maxParticipants')->isRequired()->maxLength(4)->setValue('0');
		
	$column = $form->addRow()->addColumn();
        $column->addLabel('description', __('Description'));
        $column->addEditor('description', $guid)->setRows(10);
	
	$payment = getSettingByScope($connection2, 'Activities', 'payment');
	if ($payment != 'None' && $payment != 'Single') {
		$form->addRow()->addHeading(__('Cost'));

		$row = $form->addRow();
        	$row->addLabel('payment', __('Cost'));
			$row->addCurrency('payment')->isRequired()->maxLength(9);
			
		$costTypes = array(
			'Entire Programme' => __('Entire Programme'),
			'Per Session'      => __('Per Session'),
			'Per Week'         => __('Per Week'),
			'Per Term'         => __('Per Term'),
		);

		$row = $form->addRow();
        	$row->addLabel('paymentType', __('Cost Type'));
			$row->addSelect('paymentType')->isRequired()->fromArray($costTypes);
			
		$costStatuses = array(
            'Finalised' => __('Finalised'),
            'Estimated' => __('Estimated'),
		);
		
		$row = $form->addRow();
        	$row->addLabel('paymentFirmness', __('Cost Status'));
        	$row->addSelect('paymentFirmness')->isRequired()->fromArray($costStatuses);
	}

	$form->addRow()->addHeading(__('Time Slots'));

	$sqlWeekdays = "SELECT gibbonDaysOfWeekID as value, name FROM gibbonDaysOfWeek ORDER BY sequenceNumber";
	$sqlSpaces = "SELECT gibbonSpaceID as value, name FROM gibbonSpace ORDER BY name";
	$locations = array(
		'Internal' => __('Internal'),
		'External' => __('External'),
	);

    for ($i = 1; $i <= 2; ++$i) {
		$form->addRow()->addSubheading(__('Slot').' '.$i)->addClass("slotRow{$i}");
		
		$row = $form->addRow()->addClass("slotRow{$i}");
        	$row->addLabel("gibbonDaysOfWeekID{$i}", sprintf(__($guid, 'Slot %1$s Day'), $i));
			$row->addSelect("gibbonDaysOfWeekID{$i}")->fromQuery($pdo, $sqlWeekdays)->placeholder();
			
		$row = $form->addRow()->addClass("slotRow{$i}");
            $row->addLabel('timeStart'.$i, sprintf(__($guid, 'Slot %1$s Start Time'), $i));
            $row->addTime('timeStart'.$i);

		$row = $form->addRow()->addClass("slotRow{$i}");
			$row->addLabel("timeEnd{$i}", sprintf(__($guid, 'Slot %1$s End Time'), $i));
			$row->addTime("timeEnd{$i}")->chainedTo('timeStart'.$i);

		$row = $form->addRow()->addClass("slotRow{$i}");
            $row->addLabel("slot{$i}Location", sprintf(__($guid, 'Slot %1$s Location'), $i));
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
	

	$form->addRow()->addHeading(__('Staff'));

	$row = $form->addRow();
		$row->addLabel('staff', 'Staff');
		$row->addSelectStaff('staff')->selectMultiple();

	$staffRoles = array(
		'Organiser' => __('Organiser'),
		'Coach'     => __('Coach'),
		'Assistant' => __('Assistant'),
		'Other'     => __('Other'),
	);

	$row = $form->addRow();
		$row->addLabel('role', 'Role');
		$row->addSelect('role')->fromArray($staffRoles);

	$row = $form->addRow();
		$row->addFooter();
		$row->addSubmit();
	
	echo $form->getOutput();
	?>

	<script type="text/javascript">
	$(document).ready(function(){
		$('.slotRow2').hide();
	});
	</script>
	
	<?php
}
