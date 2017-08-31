<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;

if (isActionAccessible($guid, $connection2, '/modules/School Admin/house_manage_assign.php') == false) {
	//Acess denied
	echo "<div class='error'>" ;
		echo __('You do not have access to this action.');
	echo "</div>" ;
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/house_manage.php'>".__($guid, 'Manage Houses')."</a> > </div><div class='trailEnd'>".__($guid, 'Assign Houses').'</div>';
    echo '</div>';

    $form = Form::create('houseAssignProcess', $_SESSION[$guid]['absoluteURL'].'/modules/School Admin/house_manage_assignProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    $row = $form->addRow();
        $row->addLabel('gibbonYearGroupIDList', __('Year Groups'));
        $row->addSelectYearGroup('gibbonYearGroupIDList')->selectMultiple()->isRequired();

    $sql = "SELECT gibbonHouseID as value, name FROM gibbonHouse ORDER BY name";
    $row = $form->addRow();
        $row->addLabel('gibbonHouseIDList', __('Houses'));
        $row->addSelect('gibbonHouseIDList')->fromQuery($pdo, $sql)->isRequired()->selectMultiple()->selectAll();

    $row = $form->addRow();
        $row->addLabel('balanceGender', __('Balance by Gender?'));
        $row->addYesNo('balanceGender');

    $row = $form->addRow();
        $row->addLabel('balanceYearGroup', __('Balance by Year Group?'))->description(__('Attempts to keep houses be as balanced as possible per year group, otherwise balances the house numbers school-wide.'));
        $row->addYesNo('balanceYearGroup');

    $row = $form->addRow();
        $row->addLabel('overwrite', __('Overwrite'))->description(__("Replace a student's existing house assignment? If no, it will only assign houses to students who do not already have one."));
        $row->addYesNo('overwrite')->selected('N');

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
