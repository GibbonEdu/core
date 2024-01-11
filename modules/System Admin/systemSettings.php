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


use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\System\SettingGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/systemSettings.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('System Settings'));

    $page->return->addReturns(['error6' => __('The uploaded file was missing or only partially uploaded.')]);

    $form = Form::create('systemSettings', $session->get('absoluteURL').'/modules/'.$session->get('module').'/systemSettingsProcess.php');

    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->addHiddenValue('address', $session->get('address'));

    // SYSTEM SETTINGS
    $form->addRow()->addHeading('System Settings', __('System Settings'));

    $settingGateway = $container->get(SettingGateway::class);

    $setting = $settingGateway->getSettingByScope('System', 'absoluteURL', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addURL($setting['name'])->setValue($setting['value'])->maxLength(100)->required();

    $setting = $settingGateway->getSettingByScope('System', 'absolutePath', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'])->setValue($setting['value'])->maxLength(100)->required();

    $setting = $settingGateway->getSettingByScope('System', 'systemName', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'])->setValue($setting['value'])->maxLength(50)->required();

    $setting = $settingGateway->getSettingByScope('System', 'indexText', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value'])->setRows(8)->required();

    $installTypes = array(
        'Production' => __("Production"),
        'Testing' =>  __("Testing"),
        'Development' =>  __("Development")
    );
    $setting = $settingGateway->getSettingByScope('System', 'installType', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelect($setting['name'])->fromArray($installTypes)->selected($setting['value'])->required();

    $setting = $settingGateway->getSettingByScope('System', 'cuttingEdgeCode', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'])->setValue(Format::yesNo($setting['value']))->readonly();

    $setting = $settingGateway->getSettingByScope('System', 'statsCollection', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $setting = $settingGateway->getSettingByScope('System', 'backgroundProcessing', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    // ORGANISATION
    $form->addRow()->addHeading('Organisation Settings', __('Organisation Settings'));

    $setting = $settingGateway->getSettingByScope('System', 'organisationName', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'])->setValue($setting['value'])->maxLength(50)->required();

    $setting = $settingGateway->getSettingByScope('System', 'organisationNameShort', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'])->setValue($setting['value'])->maxLength(50)->required();

    $setting = $settingGateway->getSettingByScope('System', 'organisationEmail', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addEmail($setting['name'])->setValue($setting['value'])->required();

    $setting = $settingGateway->getSettingByScope('System', 'organisationLogo', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'].'File', __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addFileUpload($setting['name'].'File')
            ->accepts('.jpg,.jpeg,.gif,.png')
            ->setAttachment('organisationLogo', $session->get('absoluteURL'), $setting['value'])->required();

    $setting = $settingGateway->getSettingByScope('System', 'organisationAdministrator', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelectStaff($setting['name'])->selected($setting['value'])->placeholder()->required();

    $setting = $settingGateway->getSettingByScope('System', 'organisationDBA', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelectStaff($setting['name'])->selected($setting['value'])->placeholder()->required();

    $setting = $settingGateway->getSettingByScope('System', 'organisationAdmissions', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelectStaff($setting['name'])->selected($setting['value'])->placeholder()->required();

    $setting = $settingGateway->getSettingByScope('System', 'organisationHR', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelectStaff($setting['name'])->selected($setting['value'])->placeholder()->required();

    // LOCALISATION
    $form->addRow()->addHeading('Localisation', __('Localisation'));

    $setting = $settingGateway->getSettingByScope('System', 'country', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelectCountry($setting['name'])->selected($setting['value']);

    $firstDayOfTheWeekOptions = array(
        'Monday' => __("Monday"),
        'Sunday' => __("Sunday"),
        'Saturday' => __("Saturday")
    );

    $setting = $settingGateway->getSettingByScope('System', 'firstDayOfTheWeek', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelect($setting['name'])->fromArray($firstDayOfTheWeekOptions)->selected($setting['value'])->required();

    $tzlist = array_reduce(DateTimeZone::listIdentifiers(DateTimeZone::ALL), function($group, $item) {
        $group[$item] = __($item);
        return $group;
    }, array());
    $setting = $settingGateway->getSettingByScope('System', 'timezone', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelect($setting['name'])->fromArray($tzlist)->selected($setting['value'])->placeholder()->required();

    $setting = $settingGateway->getSettingByScope('System', 'currency', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelectCurrency($setting['name'])->selected($setting['value'])->required();

    // MISCELLANEOUS
    $form->addRow()->addHeading('Miscellaneous', __('Miscellaneous'));

    $setting = $settingGateway->getSettingByScope('System', 'emailLink', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addURL($setting['name'])->setValue($setting['value']);

    $setting = $settingGateway->getSettingByScope('System', 'webLink', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addURL($setting['name'])->setValue($setting['value']);

    $setting = $settingGateway->getSettingByScope('System', 'pagination', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelect($setting['name'])->fromArray(['10', '25', '50', '100'])->selected($setting['value'])->required();

    $setting = $settingGateway->getSettingByScope('System', 'analytics', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value'])->setRows(8);

    $sql = "SELECT gibbonScaleID as value, name FROM gibbonScale WHERE active='Y' ORDER BY name";

    $setting = $settingGateway->getSettingByScope('System', 'defaultAssessmentScale', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelect($setting['name'])->fromQuery($pdo, $sql)->selected($setting['value']);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
