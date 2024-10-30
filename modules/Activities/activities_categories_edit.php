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
use Gibbon\Services\Format;
use Gibbon\Domain\Activities\ActivityCategoryGateway;
use Gibbon\Domain\Activities\ActivityGateway;

if (isActionAccessible($guid, $connection2, '/modules/Activities/activities_categories_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $gibbonActivityCategoryID = $_GET['gibbonActivityCategoryID'] ?? '';

    $page->breadcrumbs
        ->add(__('Manage Categories'), 'activities_categories.php')
        ->add(__('Edit Category'));

    if (empty($gibbonActivityCategoryID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $values = $container->get(ActivityCategoryGateway::class)->getByID($gibbonActivityCategoryID);

    if (empty($values)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $form = Form::create('event', $session->get('absoluteURL').'/modules/'.$session->get('module').'/activities_categories_editProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonActivityCategoryID', $gibbonActivityCategoryID);
    $form->addHiddenValue('gibbonSchoolYearID', $values['gibbonSchoolYearID']);

    // DETAILS
    $form->addRow()->addHeading(__('Basic Details'));

    $row = $form->addRow();
        $row->addLabel('name', __('Name'))->description(__('Must be unique for this school year.'));
        $row->addTextField('name')->required()->maxLength(90);

    $row = $form->addRow();
        $row->addLabel('nameShort', __('Short Name'))->description(__('Must be unique for this school year.'));
        $row->addTextField('nameShort')->required()->maxLength(12);

    $row = $form->addRow();
        $row->addLabel('signUpChoices', __('Sign Up Choices'))->description(__('How many choices to select when signing up? These will be used as backup choices for activity enrolment.'));
        $row->addRange('signUpChoices', 1, 5, 1)->required();

    // DISPLAY
    $form->addRow()->addHeading(__('Display'));

    if (!empty($values['viewableDate']) && empty($values['backgroundImage'])) {
        $form->addRow()->addContent(Format::alert(__('This category is missing a header image and is not viewable in the categories list.'), 'warning'));
    }
    $row = $form->addRow();
        $row->addLabel('backgroundImageFile', __('Header Image'));
        $row->addFileUpload('backgroundImageFile')
            ->accepts('.jpg,.jpeg,.gif,.png')
            ->setAttachment('backgroundImage', $session->get('absoluteURL'), $values['backgroundImage']);
    
    $col = $form->addRow()->addColumn();
        $col->addLabel('description', __('Introduction'));
        $col->addEditor('description', $guid)->showMedia(true);

    // ACCESS
    $form->addRow()->addHeading(__('Access'));

    $row = $form->addRow();
        $row->addLabel('active', __('Active'))->description(__('Inactive categories are only visible to users with full Manage Activities access.'));
        $row->addYesNo('active')->required()->selected('N');

    $row = $form->addRow();
        $row->addLabel('viewableDate', __('Viewable'))->prepend('1. ')->append('<br/>2. '.__('Activities are not publicly viewable until this date.'));
        $col = $row->addColumn('viewableDate')->setClass('inline');
        $col->addDate('viewableDate')->setValue($values['viewableDate'])->addClass('mr-2');
        $col->addTime('viewableTime')->setValue($values['viewableDate']);

    $row = $form->addRow();
        $row->addLabel('accessOpenDate', __('Open Sign-up'))->prepend('1. ')->append('<br/>2. '.__('Sign-up in unavailable until this date and time.'));
        $col = $row->addColumn('accessOpenDate')->setClass('inline');
        $col->addDate('accessOpenDate')->setValue($values['accessOpenDate'])->addClass('mr-2');
        $col->addTime('accessOpenTime')->setValue($values['accessOpenDate']);

    $row = $form->addRow();
        $row->addLabel('accessCloseDate', __('Close Sign-up'))->prepend('1. ')->append('<br/>2. '.__('Sign-up will automatically close on this date.'));
        $col = $row->addColumn('accessCloseDate')->setClass('inline');
        $col->addDate('accessCloseDate')->setValue($values['accessCloseDate'])->addClass('mr-2');
        $col->addTime('accessCloseTime')->setValue($values['accessCloseDate']);

    $row = $form->addRow();
        $row->addLabel('accessEnrolmentDate', __('Reveal Enrolment'))->prepend('1. ')->append('<br/>2. '.__('Activity enrolments are hidden from participants until this date.'));
        $col = $row->addColumn('accessEnrolmentDate')->setClass('inline');
        $col->addDate('accessEnrolmentDate')->setValue($values['accessEnrolmentDate'])->addClass('mr-2');
        $col->addTime('accessEnrolmentTime')->setValue($values['accessEnrolmentDate']);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    $form->loadAllValuesFrom($values);

    echo $form->getOutput();
}
