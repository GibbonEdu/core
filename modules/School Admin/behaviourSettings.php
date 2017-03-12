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

$enableDescriptors = getSettingByScope($connection2, 'Behaviour', 'enableDescriptors');
$enableLevels = getSettingByScope($connection2, 'Behaviour', 'enableLevels');
$enableBehaviourLetters = getSettingByScope($connection2, 'Behaviour', 'enableBehaviourLetters');

if (isActionAccessible($guid, $connection2, '/modules/School Admin/behaviourSettings.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Manage Behaviour Settings').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $form = Form::create('behaviourSettings', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/behaviourSettingsProcess.php');

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    $row = $form->addRow()->addHeading(__('Descriptors'));

    $setting = getSettingByScope($connection2, 'Behaviour', 'enableDescriptors', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->isRequired();

    $form->toggleVisibilityByClass('descriptors')->onSelect($setting['name'])->when('Y');

    $setting = getSettingByScope($connection2, 'Behaviour', 'positiveDescriptors', true);
    $row = $form->addRow()->addClass('descriptors');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value'])->isRequired();

    $setting = getSettingByScope($connection2, 'Behaviour', 'negativeDescriptors', true);
    $row = $form->addRow()->addClass('descriptors');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value'])->isRequired();

    $row = $form->addRow()->addHeading(__('Levels'));

    $setting = getSettingByScope($connection2, 'Behaviour', 'enableLevels', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->isRequired();

    $form->toggleVisibilityByClass('levels')->onSelect($setting['name'])->when('Y');

    $setting = getSettingByScope($connection2, 'Behaviour', 'levels', true);
    $row = $form->addRow()->addClass('levels');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value'])->isRequired();

    $row = $form->addRow()->addHeading(__('Behaviour Letters'))->append(sprintf(__('By using an %1$sincluded CLI script%2$s, %3$s can be configured to automatically generate and email behaviour letters to parents and tutors, once certain negative behaviour threshold levels have been reached. In your letter text you may use the following fields: %4$s'), "<a target='_blank' href='https://gibbonedu.org/support/administrators/command-line-tools/'>", '</a>', $_SESSION[$guid]['systemName'], '[studentName], [rollGroup], [behaviourCount], [behaviourRecord]'));

    $setting = getSettingByScope($connection2, 'Behaviour', 'enableBehaviourLetters', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->isRequired();

    $form->toggleVisibilityByClass('behaviourLetters')->onSelect($setting['name'])->when('Y');

    for ($i = 1;$i < 4;++$i) {
        $setting = getSettingByScope($connection2, 'Behaviour', 'behaviourLettersLetter'.$i.'Count', true);
        $row = $form->addRow()->addClass('behaviourLetters');
            $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
            $row->addSelect($setting['name'])->fromString('1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20')->selected($setting['value'])->isRequired();

        $setting = getSettingByScope($connection2, 'Behaviour', 'behaviourLettersLetter'.$i.'Text', true);
        $row = $form->addRow()->addClass('behaviourLetters');
            $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
            $row->addTextArea($setting['name'])->setValue($setting['value'])->isRequired();
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
?>
