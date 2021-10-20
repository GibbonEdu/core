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
use Gibbon\Services\Format;

$enableDescriptors = getSettingByScope($connection2, 'Behaviour', 'enableDescriptors');
$enableLevels = getSettingByScope($connection2, 'Behaviour', 'enableLevels');
$enableNegativeBehaviourLetters = getSettingByScope($connection2, 'Behaviour', 'enableNegativeBehaviourLetters');

if (isActionAccessible($guid, $connection2, '/modules/School Admin/behaviourSettings.php') == false) {
    //Access denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Behaviour Settings'));

    $form = Form::create('behaviourSettings', $session->get('absoluteURL').'/modules/'.$session->get('module').'/behaviourSettingsProcess.php');

    $form->addHiddenValue('address', $session->get('address'));

    $row = $form->addRow()->addHeading(__('Descriptors'));

    $setting = getSettingByScope($connection2, 'Behaviour', 'enableDescriptors', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $form->toggleVisibilityByClass('descriptors')->onSelect($setting['name'])->when('Y');

    $setting = getSettingByScope($connection2, 'Behaviour', 'positiveDescriptors', true);
    $row = $form->addRow()->addClass('descriptors');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value'])->required();

    $setting = getSettingByScope($connection2, 'Behaviour', 'negativeDescriptors', true);
    $row = $form->addRow()->addClass('descriptors');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value'])->required();

    $row = $form->addRow()->addHeading(__('Levels'));

    $setting = getSettingByScope($connection2, 'Behaviour', 'enableLevels', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $form->toggleVisibilityByClass('levels')->onSelect($setting['name'])->when('Y');

    $setting = getSettingByScope($connection2, 'Behaviour', 'levels', true);
    $row = $form->addRow()->addClass('levels');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value'])->required();

    $row = $form->addRow()->addHeading(__('Notifications'));

    $setting = getSettingByScope($connection2, 'Behaviour', 'notifyTutors', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value']);

    $setting = getSettingByScope($connection2, 'Behaviour', 'notifyEducationalAssistants', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value']);

    

    $row = $form->addRow()->addHeading(__('Behaviour Letters'))->append(__('By using a {linkCLIScript}, {systemName} can be configured to automatically generate and email behaviour letters to parents and tutors, once certain behaviour threshold levels have been reached. Visit the {linkEmailTemplates} page to customise the templates for each behaviour letter email.', ['systemName' => $session->get('systemName'), 'linkCLIScript' => Format::link('https://gibbonedu.org/support/administrators/command-line-tools/', __('CLI script')),'linkEmailTemplates' => Format::link('./index.php?q=/modules/System Admin/emailTemplates_manage.php', __('Email Templates'))]));

    $setting = getSettingByScope($connection2, 'Behaviour', 'enableNegativeBehaviourLetters', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $form->toggleVisibilityByClass('behaviourLetters')->onSelect($setting['name'])->when('Y');

    for ($i = 1;$i < 4;++$i) {
        $setting = getSettingByScope($connection2, 'Behaviour', 'behaviourLettersNegativeLetter'.$i.'Count', true);
        $row = $form->addRow()->addClass('behaviourLetters');
            $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
            $row->addSelect($setting['name'])->fromArray(range(1,20))->selected($setting['value'])->required();
    }

    $row = $form->addRow()->addHeading(__('Miscellaneous'));

    $setting = getSettingByScope($connection2, 'Behaviour', 'policyLink', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addURL($setting['name'])->setValue($setting['value']);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
