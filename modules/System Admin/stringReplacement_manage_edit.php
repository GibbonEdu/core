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

if (isActionAccessible($guid, $connection2, '/modules/System Admin/stringReplacement_manage_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/System Admin/stringReplacement_manage.php'>".__($guid, 'Manage String Replacements')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit String').'</div>';
    echo '</div>';

    $search = '';
    if (isset($_GET['search'])) {
        $search = $_GET['search'];
    }

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Check if school year specified
    $gibbonStringID = $_GET['gibbonStringID'];
    if ($gibbonStringID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonStringID' => $gibbonStringID);
            $sql = 'SELECT * FROM gibbonString WHERE gibbonStringID=:gibbonStringID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __($guid, 'The specified record cannot be found.');
            echo '</div>';
        } else {
            //Let's go!
            $values = $result->fetch();

            if ($search != '') {
                echo "<div class='linkTop'>";
                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/System Admin/stringReplacement_manage.php&search=$search'>".__($guid, 'Back to Search Results').'</a>';
                echo '</div>';
            }

            $form = Form::create('editString', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/stringReplacement_manage_editProcess.php?gibbonStringID='.$values['gibbonStringID'].'&search='.$search);

            $form->addHiddenValue('address', $_SESSION[$guid]['address']);

            $row = $form->addRow();
                $row->addLabel('original', __('Original String'));
                $row->addTextField('original')->isRequired()->maxLength(100)->setValue($values['original']);

            $row = $form->addRow();
                $row->addLabel('replacement', __('Replacement String'));
                $row->addTextField('replacement')->isRequired()->maxLength(100)->setValue($values['replacement']);

            $row = $form->addRow();
                $row->addLabel('mode', __('Mode'));
                $row->addSelect('mode')
                    ->fromArray(array('Whole' => __('Whole'), 'Partial' => __('Partial')))
                    ->selected($values['mode']);

            $row = $form->addRow();
                $row->addLabel('caseSensitive', __('Case Sensitive'));
                $row->addYesNo('caseSensitive')->selected('N')->isRequired()->selected($values['caseSensitive']);

            $row = $form->addRow();
                $row->addLabel('priority', __('Priority'))->description(__('Higher priorities are substituted first.'));
                $row->addNumber('priority')->isRequired()->maxLength(2)->setValue($values['priority']);

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            echo $form->getOutput();
        }
    }
}
