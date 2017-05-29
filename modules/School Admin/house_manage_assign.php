<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

use Gibbon\Forms\Form;

if (isActionAccessible($guid, $connection2, '/modules/School Admin/house_manage.php') == false) {
	//Acess denied
	echo "<div class='error'>" ;
		echo __('You do not have access to this action.');
	echo "</div>" ;
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/house_manage.php'>".__($guid, 'Manage Houses')."</a> > </div><div class='trailEnd'>".__($guid, 'Assign Houses').'</div>';
    echo '</div>';

    $gibbonYearGroupIDList = (isset($_POST['gibbonYearGroupIDList']))? $_POST['gibbonYearGroupIDList'] : '';
    $gibbonHouseIDList = (isset($_POST['gibbonHouseIDList']))? $_POST['gibbonHouseIDList'] : '';
    $balanceYearGroup = (isset($_POST['balanceYearGroup']))? $_POST['balanceYearGroup'] : '';
    $balanceGender = (isset($_POST['balanceGender']))? $_POST['balanceGender'] : '';
    $overwrite = (isset($_POST['overwrite']))? $_POST['overwrite'] : '';

    if (empty($gibbonYearGroupIDList) || empty($gibbonHouseIDList) || empty($balanceYearGroup) || empty($balanceGender) || empty($overwrite)) {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        $form = Form::create('houseAssignProcess', $_SESSION[$guid]['absoluteURL'].'/modules/School Admin/house_manage_assignProcess.php');

        $form->addHiddenValue('gibbonYearGroupIDList', implode(',', $gibbonYearGroupIDList));
        $form->addHiddenValue('gibbonHouseIDList', implode(',', $gibbonHouseIDList));
        $form->addHiddenValue('balanceYearGroup', $balanceYearGroup);
        $form->addHiddenValue('balanceGender', $balanceGender);
        $form->addHiddenValue('overwrite', $overwrite);
        $form->addHiddenValue('address', $_SESSION[$guid]['address']);

        $row = $form->addRow()->addHeading(__('Are you sure you want to continue?'));

        $row = $form->addRow();
            $row->addContent(__('This operation cannot be undone.').' '.__('This action with assign students to houses.'));

        $row = $form->addRow();
            $row->addLabel('confirm', sprintf(__('Type %1$s to confirm'), __('CONFIRM')) )->addClass('mediumWidth');
            $row->addTextField('confirm')
                ->addValidation('Validate.Presence')
                ->addValidation('Validate.Inclusion',
                    'within: [\''.__('CONFIRM').'\'], failureMessage: "'.__(' Please enter the text exactly as it is displayed to confirm this action.').'", partialMatch: false, caseSensitive: false')
                ->addValidationOption('onlyOnSubmit: true');

        $form->addRow()->addSubmit();

        echo $form->getOutput();
    }
}
