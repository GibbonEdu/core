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

@session_start();

if (isActionAccessible($guid, $connection2, '/modules/User Admin/family_manage_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/User Admin/family_manage.php'>".__($guid, 'Manage Families')."</a> > </div><div class='trailEnd'>".__($guid, 'Add Family').'</div>';
    echo '</div>';

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/User Admin/family_manage_edit.php&gibbonFamilyID='.$_GET['editID'].'&search='.$_GET['search'];
    }
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], $editLink, null);
    }

    $search = $_GET['search'];
    if ($search != '') {
        echo "<div class='linkTop'>";
        echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/User Admin/family_manage.php&search=$search'>".__($guid, 'Back to Search Results').'</a>';
        echo '</div>';
    }

    $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/family_manage_addProcess.php?search=$search");

    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setClass('smallIntBorder fullWidth');

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    $form->addRow()->addHeading(__('General Information'));

    $row = $form->addRow();
        $row->addLabel('name', __('Family Name'));
        $row->addTextField('name')->maxLength(100)->isRequired();

    $row = $form->addRow();
		$row->addLabel('status', __('Marital Status'));
		$row->addSelectMaritalStatus('status')->isRequired();

    $row = $form->addRow();
        $row->addLabel('languageHomePrimary', __('Home Language - Primary'));
        $row->addSelectLanguage('languageHomePrimary');

    $row = $form->addRow();
        $row->addLabel('languageHomeSecondary', __('Home Language - Secondary'));
        $row->addSelectLanguage('languageHomeSecondary');

    $row = $form->addRow();
        $row->addLabel('nameAddress', __('Address Name'))->description(__('Formal name to address parents with.'));
        $row->addTextField('nameAddress')->maxLength(100)->isRequired();

    $row = $form->addRow();
        $row->addLabel('homeAddress', __('Home Address'))->description(__('Unit, Building, Street'));
        $row->addTextField('homeAddress')->maxLength(255);

    $row = $form->addRow();
        $row->addLabel('homeAddressDistrict', __('Home Address (District)'))->description(__('County, State, District'));
        $row->addTextFieldDistrict('homeAddressDistrict');

    $row = $form->addRow();
        $row->addLabel('homeAddressCountry', __('Home Address (Country)'));
        $row->addSelectCountry('homeAddressCountry');

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
?>
