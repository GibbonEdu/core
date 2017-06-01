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

//Module includes from User Admin (for custom fields)
include './modules/User Admin/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Students/applicationForm_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__('Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__(getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/applicationForm_manage.php&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID']."'>".__('Manage Applications')."</a> > </div><div class='trailEnd'>".__('Add Form').'</div>';
    echo '</div>';

    $form = Form::create('addApplication', $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/applicationForm.php');

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    $types = array(
        'blank' => __('Blank Application'),
        'family' => __('Current').' '.__('Family'),
        'person' => __('Current').' '.__('User'),
    );

    $row = $form->addRow();
        $row->addLabel('applicationType', __('Type'));
        $row->addSelect('applicationType')->fromArray($types)->isRequired();

    $sql = "SELECT gibbonFamily.gibbonFamilyID as value, CONCAT(gibbonFamily.name, ' (', GROUP_CONCAT(DISTINCT CONCAT(gibbonPerson.preferredName, ' ', gibbonPerson.surname) SEPARATOR ', '), ')') as name FROM gibbonFamily
        JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID)
        JOIN gibbonPerson ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID)
        GROUP BY gibbonFamily.gibbonFamilyID
        HAVING count(DISTINCT gibbonFamilyAdult.gibbonPersonID) > 0
        ORDER BY name";

    $form->toggleVisibilityByClass('typeFamily')->onSelect('applicationType')->when('family');

    $row = $form->addRow()->addClass('typeFamily');
        $row->addLabel('gibbonFamilyID', __('Family'));
        $row->addSelect('gibbonFamilyID')->fromQuery($pdo, $sql)->isRequired();

    $sql = "SELECT gibbonPersonID as value, CONCAT(gibbonPerson.surname, ', ', gibbonPerson.preferredName, ' (', gibbonRole.category, ': ', gibbonPerson.username, ')') as name
            FROM gibbonPerson
            JOIN gibbonRole ON (gibbonRole.gibbonRoleID=gibbonPerson.gibbonRoleIDPrimary)
            WHERE gibbonRole.category <> 'Student'
            AND gibbonPerson.status='Full'
            ORDER BY gibbonPerson.surname, gibbonPerson.preferredname";

    $form->toggleVisibilityByClass('typePerson')->onSelect('applicationType')->when('person');

    $row = $form->addRow()->addClass('typePerson');
        $row->addLabel('gibbonPersonID', __('Person'));
        $row->addSelect('gibbonPersonID')->fromQuery($pdo, $sql)->isRequired();

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
?>
