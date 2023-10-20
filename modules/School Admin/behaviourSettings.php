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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;

$settingGateway = $container->get(SettingGateway::class);
$enableDescriptors = $settingGateway->getSettingByScope('Behaviour', 'enableDescriptors');
$enableLevels = $settingGateway->getSettingByScope('Behaviour', 'enableLevels');
$enableNegativeBehaviourLetters = $settingGateway->getSettingByScope('Behaviour', 'enableNegativeBehaviourLetters');

if (isActionAccessible($guid, $connection2, '/modules/School Admin/behaviourSettings.php') == false) {
    //Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Behaviour Settings'));

    $form = Form::create('behaviourSettings', $session->get('absoluteURL').'/modules/'.$session->get('module').'/behaviourSettingsProcess.php');

    $form->addHiddenValue('address', $session->get('address'));

    $row = $form->addRow()->addHeading('Descriptors', __('Descriptors'));

    $setting = $settingGateway->getSettingByScope('Behaviour', 'enableDescriptors', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $form->toggleVisibilityByClass('descriptors')->onSelect($setting['name'])->when('Y');

    $setting = $settingGateway->getSettingByScope('Behaviour', 'positiveDescriptors', true);
    $row = $form->addRow()->addClass('descriptors');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value'])->required();

    $setting = $settingGateway->getSettingByScope('Behaviour', 'negativeDescriptors', true);
    $row = $form->addRow()->addClass('descriptors');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value'])->required();

    $row = $form->addRow()->addHeading('Levels', __('Levels'));

    $setting = $settingGateway->getSettingByScope('Behaviour', 'enableLevels', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $form->toggleVisibilityByClass('levels')->onSelect($setting['name'])->when('Y');

    $setting = $settingGateway->getSettingByScope('Behaviour', 'levels', true);
    $row = $form->addRow()->addClass('levels');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value'])->required();

    $row = $form->addRow()->addHeading('Notifications', __('Notifications'));

    $setting = $settingGateway->getSettingByScope('Behaviour', 'notifyTutors', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value']);

    $setting = $settingGateway->getSettingByScope('Behaviour', 'notifyEducationalAssistants', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value']);

    
    $row = $form->addRow()->addHeading('Behaviour Letters', __('Behaviour Letters'))->append(__('By using a {linkCLIScript}, {systemName} can be configured to automatically generate and email behaviour letters to parents and tutors, once certain behaviour threshold levels have been reached. Visit the {linkEmailTemplates} page to customise the templates for each behaviour letter email.', ['systemName' => $session->get('systemName'), 'linkCLIScript' => Format::link('https://gibbonedu.org/support/administrators/command-line-tools/', __('CLI script')),'linkEmailTemplates' => Format::link('./index.php?q=/modules/System Admin/emailTemplates_manage.php', __('Email Templates'))]));

    $setting = $settingGateway->getSettingByScope('Behaviour', 'enableNegativeBehaviourLetters', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $form->toggleVisibilityByClass('behaviourLettersNegative')->onSelect($setting['name'])->when('Y');

    for ($i = 1;$i < 4;++$i) {
        $setting = $settingGateway->getSettingByScope('Behaviour', 'behaviourLettersNegativeLetter'.$i.'Count', true);
        $row = $form->addRow()->addClass('behaviourLettersNegative');
            $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
            $row->addSelect($setting['name'])->fromArray(range(1,20))->selected($setting['value'])->required();
    }

    $setting = $settingGateway->getSettingByScope('Behaviour', 'enablePositiveBehaviourLetters', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $form->toggleVisibilityByClass('behaviourLettersPositive')->onSelect($setting['name'])->when('Y');

    for ($i = 1;$i < 4;++$i) {
        $setting = $settingGateway->getSettingByScope('Behaviour', 'behaviourLettersPositiveLetter'.$i.'Count', true);
        $row = $form->addRow()->addClass('behaviourLettersPositive');
            $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
            $row->addSelect($setting['name'])->fromArray(range(1,20))->selected($setting['value'])->required();
    }

    $row = $form->addRow()->addHeading('Miscellaneous', __('Miscellaneous'));

    $setting = $settingGateway->getSettingByScope('Behaviour', 'policyLink', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addURL($setting['name'])->setValue($setting['value']);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
