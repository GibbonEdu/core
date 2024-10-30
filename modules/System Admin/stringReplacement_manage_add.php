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

if (isActionAccessible($guid, $connection2, '/modules/System Admin/stringReplacement_manage_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $search = '';
    if (isset($_GET['search'])) {
        $search = $_GET['search'] ?? '';
    }

    //Proceed!
    $page->breadcrumbs
        ->add(__('Manage String Replacements'), 'stringReplacement_manage.php')
        ->add(__('Add String'));

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $session->get('absoluteURL').'/index.php?q=/modules/System Admin/stringReplacement_manage_edit.php&gibbonStringID='.$_GET['editID'];
    }
    $page->return->setEditLink($editLink);

    if ($search != '') {
        $page->navigator->addSearchResultsAction(Url::fromModuleRoute('System Admin', 'stringReplacement_manage.php')->withQueryParam('search', $search));
    }

    $form = Form::create('addString', $session->get('absoluteURL').'/modules/'.$session->get('module').'/stringReplacement_manage_addProcess.php?search='.$search);

    $form->addHiddenValue('address', $session->get('address'));

    $row = $form->addRow();
        $row->addLabel('original', __('Original String'));
        $row->addTextField('original')->required()->maxLength(255);

    $row = $form->addRow();
        $row->addLabel('replacement', __('Replacement String'));
        $row->addTextField('replacement')->required()->maxLength(255);

    $row = $form->addRow();
        $row->addLabel('mode', __('Mode'));
        $row->addSelect('mode')->fromArray(array('Whole' => __('Whole'), 'Partial' => __('Partial')));

    $row = $form->addRow();
        $row->addLabel('caseSensitive', __('Case Sensitive'));
        $row->addYesNo('caseSensitive')->selected('N')->required();

    $row = $form->addRow();
        $row->addLabel('priority', __('Priority'))->description(__('Higher priorities are substituted first.'));
        $row->addNumber('priority')->required()->maxLength(2)->setValue('0');

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
