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

if (isActionAccessible($guid, $connection2, '/modules/User Admin/staffApplicationFormSettings.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Staff Application Form Settings').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $form = Form::create('staffApplicationFormSettings', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/staffApplicationFormSettingsProcess.php');

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    $row = $form->addRow()->addHeading('General Options');

    $settingByScope = getSettingByScope($connection2, 'Staff', 'staffApplicationFormIntroduction', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addTextArea($settingByScope['name'])->setValue($settingByScope['value']);

    $settingByScope = getSettingByScope($connection2, 'Staff', 'staffApplicationFormQuestions', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addTextArea($settingByScope['name'])->setValue($settingByScope['value']);

    $settingByScope = getSettingByScope($connection2, 'Staff', 'staffApplicationFormPostscript', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addTextArea($settingByScope['name'])->setValue($settingByScope['value']);

    $settingByScope = getSettingByScope($connection2, 'Staff', 'staffApplicationFormAgreement', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addTextArea($settingByScope['name'])->setValue($settingByScope['value']);

    $settingByScope = getSettingByScope($connection2, 'Staff Application Form', 'staffApplicationFormPublicApplications', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addYesNo($settingByScope['name'])->selected($settingByScope['value'])->isRequired();

    $settingByScope = getSettingByScope($connection2, 'Staff', 'staffApplicationFormMilestones', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addTextArea($settingByScope['name'])->setValue($settingByScope['value']);

    $settingByScope = getSettingByScope($connection2, 'Staff', 'applicationFormRefereeLink', true);
    $row = $form->addRow()->addHeading(__($guid, $settingByScope['nameDisplay']))->append(__($guid, $settingByScope['description']));

    $applicationFormRefereeLink = unserialize($settingByScope['value']);

    $types=array() ;
    $types[0] = 'Teaching';
    $types[1] = 'Support';
    $typeCount = 2 ;
    try {
        $dataSelect = array();
        $sqlSelect = "SELECT * FROM gibbonRole WHERE category='Staff' ORDER BY name";
        $resultSelect = $connection2->prepare($sqlSelect);
        $resultSelect->execute($dataSelect);
    } catch (PDOException $e) {}
    while ($rowSelect = $resultSelect->fetch()) {
        $types[$typeCount] = $rowSelect['name'];
        $typeCount++;
    }
    $typeCount=0 ;
    foreach ($types AS $type) {
        $row = $form->addRow();
        if ($typeCount==0 || $typeCount==1)
            $row->addLabel($settingByScope['name'], __($guid, "Staff Type").": ".__($guid, $type));
        else
            $row->addLabel($settingByScope['name'], __($guid, "Staff Role").": ".__($guid, $type));
        $form->addHiddenValue("types[".$typeCount."]", $type);
        $row->addURL("refereeLinks[]")->setValue(isset($applicationFormRefereeLink[$type]) ? $applicationFormRefereeLink[$type] : '');
        $typeCount++;
    }

    $row = $form->addRow()->addHeading('Required Documents Options');

    $settingByScope = getSettingByScope($connection2, 'Staff', 'staffApplicationFormRequiredDocuments', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addTextArea($settingByScope['name'])->setValue($settingByScope['value']);

    $settingByScope = getSettingByScope($connection2, 'Staff', 'staffApplicationFormRequiredDocumentsText', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addTextArea($settingByScope['name'])->setValue($settingByScope['value']);

    $settingByScope = getSettingByScope($connection2, 'Staff', 'staffApplicationFormRequiredDocumentsCompulsory', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addYesNo($settingByScope['name'])->selected($settingByScope['value'])->isRequired();

    $row = $form->addRow()->addHeading('Acceptance Options');

    $settingByScope = getSettingByScope($connection2, 'Staff', 'staffApplicationFormUsernameFormat', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addTextField($settingByScope['name'])->setValue($settingByScope['value']);

    $settingByScope = getSettingByScope($connection2, 'Staff', 'staffApplicationFormNotificationMessage', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addTextArea($settingByScope['name'])->setValue($settingByScope['value']);

    $settingByScope = getSettingByScope($connection2, 'Staff', 'staffApplicationFormNotificationDefault', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addYesNo($settingByScope['name'])->selected($settingByScope['value'])->isRequired();

    $settingByScope = getSettingByScope($connection2, 'Staff', 'staffApplicationFormDefaultEmail', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addTextField($settingByScope['name'])->setValue($settingByScope['value']);

    $settingByScope = getSettingByScope($connection2, 'Staff', 'staffApplicationFormDefaultWebsite', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addTextField($settingByScope['name'])->setValue($settingByScope['value']);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
?>
