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
use Gibbon\Domain\User\FamilyGateway;
use Gibbon\Domain\Students\ApplicationFormGateway;
use Gibbon\Services\Format;

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Students/applicationForm_manage_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__('Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__(getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/applicationForm_manage.php&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID']."'>".__('Manage Applications')."</a> > </div><div class='trailEnd'>".__('Edit Family').'</div>';
    echo '</div>';

    //Check if school year specified
    $gibbonFamilyIDExisting = isset($_GET['gibbonFamilyIDExisting'])? $_GET['gibbonFamilyIDExisting'] : '';
    $gibbonApplicationFormID = isset($_GET['gibbonApplicationFormID'])? $_GET['gibbonApplicationFormID'] : '';
    $gibbonSchoolYearID = isset($_GET['gibbonSchoolYearID'])? $_GET['gibbonSchoolYearID'] : '';
    $search = isset($_GET['search'])? $_GET['search'] : '';

    if (empty($gibbonApplicationFormID) || empty($gibbonFamilyIDExisting) || empty($gibbonSchoolYearID)) {
        echo "<div class='error'>";
        echo __('You have not specified one or more required parameters.');
        echo '</div>';
        return;
    }

    $applicationGateway = $container->get(ApplicationFormGateway::class);
    $familyGateway = $container->get(FamilyGateway::class);

    $application = $applicationGateway->getApplicationFormByID($gibbonApplicationFormID);
    $family = $familyGateway->getFamilyByID($gibbonFamilyIDExisting);

    if (empty($application) || empty($family)) {
        echo "<div class='error'>";
        echo __('The specified record does not exist.');
        echo '</div>';
        return;
    }

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Let's go!
    $proceed = true;

    $familyGateway = $container->get(FamilyGateway::class);

    $familyAdults = $familyGateway->selectAdultsByFamily($gibbonFamilyIDExisting)->fetchAll();
    $familyAdults = Format::keyValue($familyAdults, 'gibbonPersonID', 'name', ['title', 'preferredName', 'surname', 'Parent']);

    $familyChildren = $familyGateway->selectChildrenByFamily($gibbonFamilyIDExisting)->fetchAll();
    $familyChildren = Format::keyValue($familyChildren, 'gibbonPersonID', 'name', ['', 'preferredName', 'surname', 'Student']);
    
    $form = Form::create('applicationFamily', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/applicationForm_manage_familyProcess.php?search='.$search);
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);
    $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);
    $form->addHiddenValue('gibbonApplicationFormID', $gibbonApplicationFormID);
    $form->addHiddenValue('gibbonFamilyIDExisting', $gibbonFamilyIDExisting);

    $row = $form->addRow();
        $row->addHeading(__('Family'))->append(sprintf(__('The applying family will be attached to an existing %1$s family.'), $_SESSION[$guid]['organisationName']));

    $row = $form->addRow();
        $row->addLabel('familyName', __('Family Name'));
        $row->addTextField('familyName')->isRequired()->readonly()->setValue($family['name']);

    $row = $form->addRow();
        $row->addHeading(__('Assign Users'))
            ->append(__('If the student or parent(s) already exist in this family you can connect them here. Otherwise they will be added as new users in this family.'))
            ->append('<div class="warning">'.__('Connecting a family member to an existing user will update the personal details for that account.').'</div>');

    $form->addRow()->addSubheading(__('Student'));

    $row = $form->addRow();
        $row->addLabel('studentName', __('Student'));
        $row->addTextField('studentName')->readonly()->setValue(formatName('', $application['preferredName'], $application['surname'], 'Student'));

    $userOptions = array('new' => __('New User'), 'existing' => __('Existing User'));
    $row = $form->addRow();
        $row->addLabel('studentUserType', __('User Type'));
        $row->addSelect('studentUserType')->fromArray($userOptions)->isRequired();

    $form->toggleVisibilityByClass('studentUserType')->onSelect('studentUserType')->when('existing');
    $row = $form->addRow()->addClass('studentUserType');
        $row->addLabel('gibbonPersonIDStudent', __('User Account'));    
        $row->addSelect('gibbonPersonIDStudent')->fromArray($familyChildren)->isRequired()->placeholder();

    $form->addRow()->addSubheading(__('Parent/Guardian').' 1');

    $row = $form->addRow();
        $row->addLabel('parent1name', __('Parent/Guardian').' 1 '.__('Name'));
        $row->addTextField('parent1name')->readonly()->setValue(formatName($application['parent1title'], $application['parent1preferredName'], $application['parent1surname'], 'Parent'));
        
    $row = $form->addRow();
        $row->addLabel('parent1relationship', __('Relationship'));
        $row->addSelectRelationship('parent1relationship')->isRequired()->selected($application['parent1relationship']);

    if (!empty($application['parent1gibbonPersonID'])) {
        $form->addHiddenValue('parent1UserType', 'existing');
        $form->addHiddenValue('parent1gibbonPersonID', $application['parent1gibbonPersonID']);

        $row = $form->addRow();
            $row->addLabel('parent1UserTypeLabel', __('User Type'));
            $row->addTextField('parent1UserTypeLabel')->isRequired()->readOnly()->setValue(__('Existing User'));
    } else {
        $row = $form->addRow();
            $row->addLabel('parent1UserType', __('User Type'));
            $row->addSelect('parent1UserType')->fromArray($userOptions)->isRequired();

        $form->toggleVisibilityByClass('parent1UserType')->onSelect('parent1UserType')->when('existing');
        $row = $form->addRow()->addClass('parent1UserType');
            $row->addLabel('parent1gibbonPersonID', __('User Account'));    
            $row->addSelect('parent1gibbonPersonID')->fromArray($familyAdults)->isRequired()->placeholder();
    }
    
    if (!empty($application['parent2surname'])) {
        $form->addRow()->addSubheading(__('Parent/Guardian').' 2');

        $row = $form->addRow();
            $row->addLabel('parent2name', __('Parent/Guardian').' 2 '.__('Name'));
            $row->addTextField('parent2name')->readonly()->setValue(formatName($application['parent2title'], $application['parent2preferredName'], $application['parent2surname'], 'Parent'));

        $row = $form->addRow();
            $row->addLabel('parent2relationship', __('Relationship'));
            $row->addSelectRelationship('parent2relationship')->isRequired()->selected($application['parent2relationship']);
            
        if (!empty($application['parent2gibbonPersonID'])) {
            $form->addHiddenValue('parent2UserType', 'existing');
            $form->addHiddenValue('parent2gibbonPersonID', $application['parent2gibbonPersonID']);
    
            $row = $form->addRow();
                $row->addLabel('parent2UserTypeLabel', __('User Type'));
                $row->addTextField('parent2UserTypeLabel')->isRequired()->readOnly()->setValue(__('Existing User'));
        } else {
            $row = $form->addRow();
                $row->addLabel('parent2UserType', __('User Type'));
                $row->addSelect('parent2UserType')->fromArray($userOptions)->isRequired();
        
            $form->toggleVisibilityByClass('parent2UserType')->onSelect('parent2UserType')->when('existing');
            $row = $form->addRow()->addClass('parent2UserType');
                $row->addLabel('parent2gibbonPersonID', __('User Account'));
                $row->addSelect('parent2gibbonPersonID')->fromArray($familyAdults)->isRequired()->placeholder();
        }
    }

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
