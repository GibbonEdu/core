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

if (isActionAccessible($guid, $connection2, '/modules/User Admin/applicationFormSettings.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Application Form Settings').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $form = Form::create('applicationFormSettings', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/applicationFormSettingsProcess.php');

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    $row = $form->addRow()->addHeading('General Options');

    $settingByScope = getSettingByScope($connection2, 'Application Form', 'introduction', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addTextArea($settingByScope['name'])->setValue($settingByScope['value']);

    $settingByScope = getSettingByScope($connection2, 'Students', 'applicationFormSENText', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addTextArea($settingByScope['name'])->setValue($settingByScope['value']);

    $settingByScope = getSettingByScope($connection2, 'Students', 'applicationFormRefereeLink', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addURL($settingByScope['name'])->setValue($settingByScope['value']);

    $settingByScope = getSettingByScope($connection2, 'Application Form', 'postscript', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addTextArea($settingByScope['name'])->setValue($settingByScope['value']);

    $settingByScope = getSettingByScope($connection2, 'Application Form', 'scholarships', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addTextArea($settingByScope['name'])->setValue($settingByScope['value']);

    $settingByScope = getSettingByScope($connection2, 'Application Form', 'agreement', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addTextArea($settingByScope['name'])->setValue($settingByScope['value']);

    $settingByScope = getSettingByScope($connection2, 'Application Form', 'applicationFee', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])
            ->description($settingByScope['description'])
            ->append('In %1$s.', $_SESSION[$guid]['currency']);
        $row->addNumber($settingByScope['name'])
            ->setValue($settingByScope['value'])
            ->decimalPlaces(2)
            ->isRequired();

    $settingByScope = getSettingByScope($connection2, 'Application Form', 'publicApplications', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addYesNo($settingByScope['name'])->selected($settingByScope['value'])->isRequired();

    $settingByScope = getSettingByScope($connection2, 'Application Form', 'milestones', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addTextArea($settingByScope['name'])->setValue($settingByScope['value']);

    $settingByScope = getSettingByScope($connection2, 'Application Form', 'howDidYouHear', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addTextArea($settingByScope['name'])->setValue($settingByScope['value']);

    $row = $form->addRow()->addHeading('Required Documents Options');

    $settingByScope = getSettingByScope($connection2, 'Application Form', 'requiredDocuments', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addTextArea($settingByScope['name'])->setValue($settingByScope['value']);

    $settingByScope = getSettingByScope($connection2, 'Application Form', 'internalDocuments', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addTextArea($settingByScope['name'])->setValue($settingByScope['value']);

    $settingByScope = getSettingByScope($connection2, 'Application Form', 'requiredDocumentsText', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addTextArea($settingByScope['name'])->setValue($settingByScope['value']);

    $settingByScope = getSettingByScope($connection2, 'Application Form', 'requiredDocumentsCompulsory', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addYesNo($settingByScope['name'])->selected($settingByScope['value'])->isRequired();

    $row = $form->addRow()->addHeading('Language Learning Options')->append(__($guid, 'Set values for applicants to specify which language they wish to learn.'));

    $settingByScope = getSettingByScope($connection2, 'Application Form', 'languageOptionsActive', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addYesNo($settingByScope['name'])->selected($settingByScope['value'])->isRequired();

    $form->toggleVisibilityByClass('languageOptions')->onSelect($settingByScope['name'])->when('Y');

    $settingByScope = getSettingByScope($connection2, 'Application Form', 'languageOptionsBlurb', true);
    $row = $form->addRow()->addClass('languageOptions');
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addTextArea($settingByScope['name'])->setValue($settingByScope['value']);

    $settingByScope = getSettingByScope($connection2, 'Application Form', 'languageOptionsLanguageList', true);
    $row = $form->addRow()->addClass('languageOptions');
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addTextArea($settingByScope['name'])->setValue($settingByScope['value']);

    $row = $form->addRow()->addHeading('Acceptance Options');

    $settingByScope = getSettingByScope($connection2, 'Application Form', 'usernameFormat', true);
	$row = $form->addRow();
    	$row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
		$row->addTextField($settingByScope['name'])->setValue($settingByScope['value']);

    $settingByScope = getSettingByScope($connection2, 'Application Form', 'notificationStudentMessage', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addTextArea($settingByScope['name'])->setValue($settingByScope['value']);

    $settingByScope = getSettingByScope($connection2, 'Application Form', 'notificationStudentDefault', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addYesNo($settingByScope['name'])->selected($settingByScope['value'])->isRequired();

    $settingByScope = getSettingByScope($connection2, 'Application Form', 'notificationParentsMessage', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addTextArea($settingByScope['name'])->setValue($settingByScope['value']);

    $settingByScope = getSettingByScope($connection2, 'Application Form', 'notificationParentsDefault', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addYesNo($settingByScope['name'])->selected($settingByScope['value'])->isRequired();

    $settingByScope = getSettingByScope($connection2, 'Application Form', 'studentDefaultEmail', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addEmail($settingByScope['name'])->setValue($settingByScope['value']);

    $settingByScope = getSettingByScope($connection2, 'Application Form', 'studentDefaultEmail', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addEmail($settingByScope['name'])->setValue($settingByScope['value']);

    $settingByScope = getSettingByScope($connection2, 'Application Form', 'studentDefaultWebsite', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addURL($settingByScope['name'])->setValue($settingByScope['value']);

    $settingByScope = getSettingByScope($connection2, 'Application Form', 'autoHouseAssign', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addYesNo($settingByScope['name'])->selected($settingByScope['value'])->isRequired();

    $row = $form->addRow();
        $row->addContent('<span class="emphasis small">* '.__('denotes a required field').'</span>');
        $row->addSubmit();

    echo $form->getOutput();
}
?>
