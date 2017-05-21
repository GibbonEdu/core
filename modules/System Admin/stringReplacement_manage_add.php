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

if (isActionAccessible($guid, $connection2, '/modules/System Admin/stringReplacement_manage_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    $search = '';
    if (isset($_GET['search'])) {
        $search = $_GET['search'];
    }

    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/System Admin/stringReplacement_manage.php&search=$search'>".__($guid, 'Manage String Replacements')."</a> > </div><div class='trailEnd'>".__($guid, 'Add String').'</div>';
    echo '</div>';

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/System Admin/stringReplacement_manage_edit.php&gibbonStringID='.$_GET['editID'];
    }
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], $editLink, null);
    }

    if ($search != '') {
        echo "<div class='linkTop'>";
        echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/System Admin/stringReplacement_manage.php&search=$search'>".__($guid, 'Back to Search Results').'</a>';
        echo '</div>';
    }

    $form = Form::create('addString', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/stringReplacement_manage_addProcess.php?search='.$search);

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    $row = $form->addRow();
        $row->addLabel('original', __('Original String'));
        $row->addTextField('original')->isRequired()->maxLength(100);

    $row = $form->addRow();
        $row->addLabel('replacement', __('Replacement String'));
        $row->addTextField('replacement')->isRequired()->maxLength(100);

    $row = $form->addRow();
        $row->addLabel('mode', __('Mode'));
        $row->addSelect('mode')->fromArray(array('Whole' => __('Whole'), 'Partial' => __('Partial')));

    $row = $form->addRow();
        $row->addLabel('caseSensitive', __('Case Sensitive'));
        $row->addYesNo('caseSensitive')->selected('N')->isRequired();

    $row = $form->addRow();
        $row->addLabel('priority', __('Priority'))->description(__('Higher priorities are substituted first.'));
        $row->addNumber('priority')->isRequired()->maxLength(2)->setValue('0');

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
