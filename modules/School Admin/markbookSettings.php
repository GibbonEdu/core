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

@session_start();

use Gibbon\Forms\Form;

if (isActionAccessible($guid, $connection2, '/modules/School Admin/markbookSettings.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Markbook Settings').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $form = Form::create('markbookSettings', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/markbookSettingsProcess.php' );

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    $row = $form->addRow()->addHeading(__('Features'));

    $setting = getSettingByScope($connection2, 'Markbook', 'enableEffort', true);
	$row = $form->addRow();
    	$row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
		$row->addYesNo($setting['name'])->selected($setting['value'])->isRequired();

    $setting = getSettingByScope($connection2, 'Markbook', 'enableRubrics', true);
    $row = $form->addRow();
    	$row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
    	$row->addYesNo($setting['name'])->selected($setting['value'])->isRequired();

    $setting = getSettingByScope($connection2, 'Markbook', 'enableColumnWeighting', true);
	$row = $form->addRow();
    	$row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
		$row->addYesNo($setting['name'])->selected($setting['value'])->isRequired();

    $form->toggleVisibilityByClass('columnWeighting')->onSelect('enableColumnWeighting')->when('Y');

    $setting = getSettingByScope($connection2, 'Markbook', 'enableMarksOnStudentProfile', true);
    $row = $form->addRow()->addClass('columnWeighting');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value']);

    $setting = getSettingByScope($connection2, 'Markbook', 'enableRawAttainment', true);
	$row = $form->addRow();
    	$row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
		$row->addYesNo($setting['name'])->selected($setting['value'])->isRequired();

    $row = $form->addRow()->addHeading(__('Interface'));

    $setting = getSettingByScope($connection2, 'Markbook', 'markbookType', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value'])->isRequired();

    $setting = getSettingByScope($connection2, 'Markbook', 'enableGroupByTerm', true);
	$row = $form->addRow();
    	$row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
		$row->addYesNo($setting['name'])->selected($setting['value'])->isRequired();

    $setting = getSettingByScope($connection2, 'Markbook', 'attainmentAlternativeName', true);
	$row = $form->addRow();
    	$row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
		$row->addTextField($setting['name'])->setValue($setting['value']);

    $setting = getSettingByScope($connection2, 'Markbook', 'attainmentAlternativeNameAbrev', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'])->setValue($setting['value']);

    $setting = getSettingByScope($connection2, 'Markbook', 'effortAlternativeName', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'])->setValue($setting['value']);

    $setting = getSettingByScope($connection2, 'Markbook', 'effortAlternativeNameAbrev', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'])->setValue($setting['value']);

    $row = $form->addRow()->addHeading(__('Warnings'));

    $setting = getSettingByScope($connection2, 'Markbook', 'showStudentAttainmentWarning', true);
    $row = $form->addRow();
    	$row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
    	$row->addYesNo($setting['name'])->selected($setting['value'])->isRequired();

    $setting = getSettingByScope($connection2, 'Markbook', 'showStudentEffortWarning', true);
    $row = $form->addRow();
    	$row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
    	$row->addYesNo($setting['name'])->selected($setting['value'])->isRequired();

    $setting = getSettingByScope($connection2, 'Markbook', 'showParentAttainmentWarning', true);
    $row = $form->addRow();
    	$row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
    	$row->addYesNo($setting['name'])->selected($setting['value'])->isRequired();

    $setting = getSettingByScope($connection2, 'Markbook', 'showParentEffortWarning', true);
    $row = $form->addRow();
    	$row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
    	$row->addYesNo($setting['name'])->selected($setting['value'])->isRequired();

    $setting = getSettingByScope($connection2, 'Markbook', 'personalisedWarnings', true);
    $row = $form->addRow();
    	$row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
    	$row->addYesNo($setting['name'])->selected($setting['value'])->isRequired();

    $row = $form->addRow()->addHeading(__('Miscellaneous'));

    $setting = getSettingByScope($connection2, 'Markbook', 'wordpressCommentPush', true);
    $row = $form->addRow();
    	$row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelect($setting['name'])->fromString('On, Off')->selected($setting['value'])->isRequired();

	$row = $form->addRow();
		$row->addFooter();
		$row->addSubmit();

	echo $form->getOutput();
}
?>
