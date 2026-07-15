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

use Gibbon\Http\Url;
use Gibbon\Forms\Form;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Forms\DatabaseFormFactory;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Individual Needs/in_supportPlan_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $page->breadcrumbs
        ->add(__('Student Support Plans'), 'in_supportPlan_manage.php')
        ->add(__('Add'));

    $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';

    $editLink = '';
    if (isset($_GET['editID'])) {
            $editLink = $session->get('absoluteURL').'/index.php?q=/modules/Individual Needs/in_supportPlan_edit.php'
            .'&gibbonStudentSupportPlanID='.$_GET['editID']
            .'&gibbonPersonID='.$gibbonPersonID;
    }
    $page->return->setEditLink($editLink);

    if (!empty($gibbonPersonID)) {
        $page->navigator->addSearchResultsAction(Url::fromModuleRoute('Individual Needs', 'in_supportPlan_manage.php')
                ->withQueryParams(['gibbonPersonID' => $gibbonPersonID])
        );
    }

    $student = $container->get(UserGateway::class)->getByID($gibbonPersonID);

    $form = Form::create('addform', $session->get('absoluteURL').'/modules/Individual Needs/in_supportPlan_addProcess.php?gibbonPersonID='.urlencode($gibbonPersonID));
    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->addHiddenValue('address', '/modules/Individual Needs/in_supportPlan_add.php');
    $form->addHiddenValue('gibbonPersonID', $gibbonPersonID);

    $row = $form->addRow();
        $row->addHeading('Basic Information', __('Basic Information'));

    // Student
    $row = $form->addRow();
        $row->addLabel('studentName', __('Student'));
        $studentName = !empty($student) ? $student['preferredName'].' '.$student['surname'] : '';
        $row->addTextField('studentName')->setValue($studentName)->readonly();

    // Name
    $row = $form->addRow();
        $row->addLabel('name', __('Name'));
        $row->addTextField('name')->maxLength(255)->required();

    // Description
    $row = $form->addRow();
        $row->addLabel('description', __('Description'));
        $row->addTextArea('description')->setRows(4);

    // Type
    $row = $form->addRow();
        $row->addLabel('type', __('Type'));
        $row->addSelect('type')->fromArray(['File' => __('File (PDF)'), 'Link' => __('Link')])->required()->selected('File');

    $form->toggleVisibilityByClass('rowFile')->onSelect('type')->when('File');
    $form->toggleVisibilityByClass('rowLink')->onSelect('type')->when('Link');

    // File upload
    $row = $form->addRow()->addClass('rowFile');
        $row->addLabel('file', __('File'));
        $row->addFileUpload('file')->accepts('.pdf')->required();

    // Link
    $row = $form->addRow()->addClass('rowLink');
        $row->addLabel('filePath', __('Link'));
        $row->addURL('filePath')->maxLength(255)->required();

    $row = $form->addRow();
        $row->addHeading('Access', __('Access'));

    // Viewable to Staff
    $row = $form->addRow();
        $row->addLabel('viewableStaff', __('Viewable to Staff'));
        $row->addYesNo('viewableStaff')->required()->selected('Y');

    // Viewable to Parents
    $row = $form->addRow();
        $row->addLabel('viewableParents', __('Viewable to Parents'));
        $row->addYesNo('viewableParents')->required()->selected('Y');

    // Active
    $row = $form->addRow();
        $row->addLabel('active', __('Active'));
        $row->addYesNo('active')->required()->selected('Y');

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
