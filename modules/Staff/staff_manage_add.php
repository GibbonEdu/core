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
use Gibbon\Domain\User\RoleGateway;

if (isActionAccessible($guid, $connection2, '/modules/Staff/staff_manage_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $search = $_GET['search'] ?? '';
    $allStaff = $_GET['allStaff'] ?? '';

    $page->breadcrumbs
        ->add(__('Manage Staff'), 'staff_manage.php', ['search' => $search, 'allStaff' => $allStaff])
        ->add(__('Add Staff'));

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Staff/staff_manage_edit.php&gibbonStaffID='.$_GET['editID'].'&search='.$_GET['search'].'&allStaff='.$_GET['allStaff'];
    }
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], $editLink, null);
    }

    if ($search != '' or $allStaff != '') {
        echo "<div class='linkTop'>";
        echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Staff/staff_manage.php&search=$search&allStaff=$allStaff'>".__('Back to Search Results').'</a>';
        echo '</div>';
    }

    $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/staff_manage_addProcess.php?search=$search&allStaff=$allStaff");
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    $form->addRow()->addHeading(__('Basic Information'));

    $row = $form->addRow();
        $row->addLabel('gibbonPersonID', __('Person'))->description(__('Must be unique.'));
        $row->addSelectUsers('gibbonPersonID')->placeholder()->required();

    $row = $form->addRow();
        $row->addLabel('initials', __('Initials'))->description(__('Must be unique if set.'));
        $row->addTextField('initials')->maxlength(4);

    $types = array('Teaching' => __('Teaching'), 'Support' => __('Support'));
    $row = $form->addRow();
        $row->addLabel('type', __('Type'));
        $row->addSelect('type')->fromArray($types)->placeholder()->required();

    $row = $form->addRow();
        $row->addLabel('jobTitle', __('Job Title'));
        $row->addTextField('jobTitle')->maxlength(100);

    $form->addRow()->addHeading(__('First Aid'));

    $row = $form->addRow();
        $row->addLabel('firstAidQualified', __('First Aid Qualified?'));
        $row->addYesNo('firstAidQualified')->placeHolder();

    $form->toggleVisibilityByClass('firstAid')->onSelect('firstAidQualified')->when('Y');

    $row = $form->addRow()->addClass('firstAid');
        $row->addLabel('firstAidQualification', __('First Aid Qualification'));
        $row->addTextField('firstAidQualification')->maxlength(100);

    $row = $form->addRow()->addClass('firstAid');
        $row->addLabel('firstAidExpiry', __('First Aid Expiry'));
        $row->addDate('firstAidExpiry');

    $form->addRow()->addHeading(__('Biography'));

    $row = $form->addRow();
        $row->addLabel('countryOfOrigin', __('Country Of Origin'));
        $row->addSelectCountry('countryOfOrigin')->placeHolder();

    $row = $form->addRow();
        $row->addLabel('qualifications', __('Qualifications'));
        $row->addTextField('qualifications')->maxlength(80);

    $row = $form->addRow();
        $row->addLabel('biographicalGrouping', __('Grouping'))->description(__('Used to group staff when creating a staff directory.'));
        $row->addTextField('biographicalGrouping')->maxlength(100);

    $row = $form->addRow();
        $row->addLabel('biographicalGroupingPriority', __('Grouping Priority'))->description(__('Higher numbers move teachers up the order within their grouping.'));
        $row->addNumber('biographicalGroupingPriority')->decimalPlaces(0)->maximum(99)->maxLength(2)->setValue('0');

    $row = $form->addRow();
        $row->addLabel('biography', __('Biography'));
        $row->addTextArea('biography')->setRows(10);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
