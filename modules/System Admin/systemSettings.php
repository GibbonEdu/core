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

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/systemSettings.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('System Settings'));

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
        $row->addURL($setting['name'])->setValue($setting['value'])->maxLength(100)->required();

    $setting = getSettingByScope($connection2, 'System', 'absolutePath', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'])->setValue($setting['value'])->maxLength(100)->required();

    $setting = getSettingByScope($connection2, 'System', 'systemName', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'])->setValue($setting['value'])->maxLength(50)->required();

    $setting = getSettingByScope($connection2, 'System', 'indexText', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value'])->setRows(8)->required();

    $installTypes = array(
        'Production' => __("Production"),
        'Testing' =>  __("Testing"),
        'Development' =>  __("Development")
    );
    $setting = getSettingByScope($connection2, 'System', 'installType', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelect($setting['name'])->fromArray($installTypes)->selected($setting['value'])->required();

    $setting = getSettingByScope($connection2, 'System', 'cuttingEdgeCode', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'])->setValue(ynExpander($guid, $setting['value']))->readonly();

    $setting = getSettingByScope($connection2, 'System', 'statsCollection', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $setting = getSettingByScope($connection2, 'System', 'backgroundProcessing', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    // ORGANISATION
    $form->addRow()->addHeading(__('Organisation Settings'));

    $setting = getSettingByScope($connection2, 'System', 'organisationName', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'])->setValue($setting['value'])->maxLength(50)->required();

    $setting = getSettingByScope($connection2, 'System', 'organisationNameShort', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'])->setValue($setting['value'])->maxLength(50)->required();

    $setting = getSettingByScope($connection2, 'System', 'organisationEmail', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addEmail($setting['name'])->setValue($setting['value'])->required();

    $setting = getSettingByScope($connection2, 'System', 'organisationLogo', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'])->setValue($setting['value'])->required();

    $setting = getSettingByScope($connection2, 'System', 'organisationBackground', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'])->setValue($setting['value']);

    $setting = getSettingByScope($connection2, 'System', 'organisationAdministrator', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelectStaff($setting['name'])->selected($setting['value'])->placeholder()->required();

    $setting = getSettingByScope($connection2, 'System', 'organisationDBA', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelectStaff($setting['name'])->selected($setting['value'])->placeholder()->required();

    $setting = getSettingByScope($connection2, 'System', 'organisationAdmissions', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelectStaff($setting['name'])->selected($setting['value'])->placeholder()->required();

    $setting = getSettingByScope($connection2, 'System', 'organisationHR', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelectStaff($setting['name'])->selected($setting['value'])->placeholder()->required();

    // LOCALISATION
    $form->addRow()->addHeading(__('Localisation'));

    $setting = getSettingByScope($connection2, 'System', 'country', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelectCountry($setting['name'])->selected($setting['value']);

    $firstDayOfTheWeekOptions = array(
        'Monday' => __("Monday"),
        'Sunday' => __("Sunday"),
        'Saturday' => __("Saturday")
    );

    $setting = getSettingByScope($connection2, 'System', 'firstDayOfTheWeek', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelect($setting['name'])->fromArray($firstDayOfTheWeekOptions)->selected($setting['value'])->required();

    $tzlist = array_reduce(DateTimeZone::listIdentifiers(DateTimeZone::ALL), function($group, $item) {
        $group[$item] = __($item);
        return $group;
    }, array());
    $setting = getSettingByScope($connection2, 'System', 'timezone', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelect($setting['name'])->fromArray($tzlist)->selected($setting['value'])->placeholder()->required();

    $setting = getSettingByScope($connection2, 'System', 'currency', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelectCurrency($setting['name'])->selected($setting['value'])->required();

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
        $row->addSelect($setting['name'])->fromArray(['10', '25', '50', '100'])->selected($setting['value'])->required();

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
