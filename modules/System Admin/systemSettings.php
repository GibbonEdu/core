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
use Gibbon\Forms\DatabaseFormFactory;

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/systemSettings.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Prepare and submit stats if that is what the system calls for
    if ($_SESSION[$guid]['statsCollection'] == 'Y') {
        $absolutePathProtocol = '';
        $absolutePath = '';
        if (substr($_SESSION[$guid]['absoluteURL'], 0, 7) == 'http://') {
            $absolutePathProtocol = 'http';
            $absolutePath = substr($_SESSION[$guid]['absoluteURL'], 7);
        } elseif (substr($_SESSION[$guid]['absoluteURL'], 0, 8) == 'https://') {
            $absolutePathProtocol = 'https';
            $absolutePath = substr($_SESSION[$guid]['absoluteURL'], 8);
        }
        try {
            $data = array();
            $sql = 'SELECT * FROM gibbonPerson';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
        }
        $usersTotal = $result->rowCount();
        try {
            $data = array();
            $sql = "SELECT * FROM gibbonPerson WHERE status='Full'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
        }
        $usersFull = $result->rowCount();
        echo "<iframe style='display: none; height: 10px; width: 10px' src='https://gibbonedu.org/services/tracker/tracker.php?absolutePathProtocol=".urlencode($absolutePathProtocol).'&absolutePath='.urlencode($absolutePath).'&organisationName='.urlencode($_SESSION[$guid]['organisationName']).'&type='.urlencode($_SESSION[$guid]['installType']).'&version='.urlencode($version).'&country='.$_SESSION[$guid]['country']."&usersTotal=$usersTotal&usersFull=$usersFull'></iframe>";
    }

    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'System Settings').'</div>';
    echo '</div>';

    //Check for new version of Gibbon
    echo getCurrentVersion($guid, $connection2, $version);

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $form = Form::create('systemSettings', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/systemSettingsProcess.php');

    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    // SYSTEM SETTINGS
    $form->addRow()->addHeading(__('System Settings'));

    $setting = getSettingByScope($connection2, 'System', 'absoluteURL', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addURL($setting['name'])->setValue($setting['value'])->maxLength(100)->isRequired();

    $setting = getSettingByScope($connection2, 'System', 'absolutePath', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'])->setValue($setting['value'])->maxLength(100)->isRequired();

    $setting = getSettingByScope($connection2, 'System', 'systemName', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'])->setValue($setting['value'])->maxLength(50)->isRequired();

    $setting = getSettingByScope($connection2, 'System', 'indexText', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value'])->setRows(8)->isRequired();

    $setting = getSettingByScope($connection2, 'System', 'installType', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelect($setting['name'])->fromString('Production, Testing, Development')->selected($setting['value'])->isRequired();

    $setting = getSettingByScope($connection2, 'System', 'cuttingEdgeCode', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'])->setValue(ynExpander($guid, $setting['value']))->readonly();

    $setting = getSettingByScope($connection2, 'System', 'statsCollection', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->isRequired();

    // ORGANISATION
    $form->addRow()->addHeading(__('Organisation Settings'));

    $setting = getSettingByScope($connection2, 'System', 'organisationName', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'])->setValue($setting['value'])->maxLength(50)->isRequired();

    $setting = getSettingByScope($connection2, 'System', 'organisationNameShort', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'])->setValue($setting['value'])->maxLength(50)->isRequired();

    $setting = getSettingByScope($connection2, 'System', 'organisationEmail', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addEmail($setting['name'])->setValue($setting['value'])->isRequired();

    $setting = getSettingByScope($connection2, 'System', 'organisationLogo', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'])->setValue($setting['value'])->isRequired();

    $setting = getSettingByScope($connection2, 'System', 'organisationAdministrator', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelectStaff($setting['name'])->selected($setting['value'])->placeholder()->isRequired();

    $setting = getSettingByScope($connection2, 'System', 'organisationDBA', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelectStaff($setting['name'])->selected($setting['value'])->placeholder()->isRequired();

    $setting = getSettingByScope($connection2, 'System', 'organisationAdmissions', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelectStaff($setting['name'])->selected($setting['value'])->placeholder()->isRequired();

    $setting = getSettingByScope($connection2, 'System', 'organisationHR', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelectStaff($setting['name'])->selected($setting['value'])->placeholder()->isRequired();

    // SECURITY SETTINGS
    $form->addRow()->addHeading(__('Security Settings'));
    $form->addRow()->addSubheading(__('Password Policy'));

    $setting = getSettingByScope($connection2, 'System', 'passwordPolicyMinLength', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelect($setting['name'])->fromArray(range(4, 12))->selected($setting['value'])->isRequired();

    $setting = getSettingByScope($connection2, 'System', 'passwordPolicyAlpha', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->isRequired();

    $setting = getSettingByScope($connection2, 'System', 'passwordPolicyNumeric', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->isRequired();

    $setting = getSettingByScope($connection2, 'System', 'passwordPolicyNonAlphaNumeric', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->isRequired();

    $form->addRow()->addSubheading(__('Miscellaneous'));

    $setting = getSettingByScope($connection2, 'System', 'sessionDuration', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addNumber($setting['name'])->setValue($setting['value'])->minimum(1200)->maxLength(50)->isRequired();

    // VALUE ADDED
    $form->addRow()->addHeading(__('gibbonedu.com Value Added Services'));

    $setting = getSettingByScope($connection2, 'System', 'gibboneduComOrganisationName', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'])->setValue($setting['value']);

    $setting = getSettingByScope($connection2, 'System', 'gibboneduComOrganisationKey', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'])->setValue($setting['value']);

    // LOCALISATION
    $form->addRow()->addHeading(__('Localisation'));

    $setting = getSettingByScope($connection2, 'System', 'country', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelectCountry($setting['name'])->selected($setting['value']);

    $setting = getSettingByScope($connection2, 'System', 'firstDayOfTheWeek', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelect($setting['name'])->fromString('Monday, Sunday')->selected($setting['value'])->isRequired();

    $setting = getSettingByScope($connection2, 'System', 'timezone', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'])->setValue($setting['value'])->isRequired();

    $setting = getSettingByScope($connection2, 'System', 'currency', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelectCurrency($setting['name'])->selected($setting['value'])->isRequired();

    // MISCELLANEOUS
    $form->addRow()->addHeading(__('Miscellaneous'));

    $setting = getSettingByScope($connection2, 'System', 'emailLink', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addURL($setting['name'])->setValue($setting['value']);

    $setting = getSettingByScope($connection2, 'System', 'webLink', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addURL($setting['name'])->setValue($setting['value']);

    $setting = getSettingByScope($connection2, 'System', 'pagination', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addNumber($setting['name'])->setValue($setting['value'])->minimum(5)->maxLength(50)->isRequired();

    $setting = getSettingByScope($connection2, 'System', 'analytics', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value'])->setRows(8);

    $sql = "SELECT gibbonScaleID as value, name FROM gibbonScale WHERE active='Y' ORDER BY name";

    $setting = getSettingByScope($connection2, 'System', 'defaultAssessmentScale', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelect($setting['name'])->fromQuery($pdo, $sql)->selected($setting['value']);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
