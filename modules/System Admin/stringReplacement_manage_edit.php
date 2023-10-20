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

if (isActionAccessible($guid, $connection2, '/modules/System Admin/stringReplacement_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs
        ->add(__('Manage String Replacements'), 'stringReplacement_manage.php')
        ->add(__('Edit String'));

    $search = $_GET['search'] ?? '';

    //Check if StringID specified
    $gibbonStringID = $_GET['gibbonStringID'] ?? '';
    
    if ($gibbonStringID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        
            $data = array('gibbonStringID' => $gibbonStringID);
            $sql = 'SELECT * FROM gibbonString WHERE gibbonStringID=:gibbonStringID';
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            $page->addError(__('The specified record cannot be found.'));
        } else {
            //Let's go!
            $values = $result->fetch();

            if ($search != '') {
                $page->navigator->addSearchResultsAction(Url::fromModuleRoute('System Admin', 'stringReplacement_manage.php')->withQueryParam('search', $search));
            }

            $form = Form::create('editString', $session->get('absoluteURL').'/modules/'.$session->get('module').'/stringReplacement_manage_editProcess.php?gibbonStringID='.$values['gibbonStringID'].'&search='.$search);

            $form->addHiddenValue('address', $session->get('address'));

            $row = $form->addRow();
                $row->addLabel('original', __('Original String'));
                $row->addTextField('original')->required()->maxLength(255)->setValue($values['original']);

            $row = $form->addRow();
                $row->addLabel('replacement', __('Replacement String'));
                $row->addTextField('replacement')->required()->maxLength(255)->setValue($values['replacement']);

            $row = $form->addRow();
                $row->addLabel('mode', __('Mode'));
                $row->addSelect('mode')
                    ->fromArray(array('Whole' => __('Whole'), 'Partial' => __('Partial')))
                    ->selected($values['mode']);

            $row = $form->addRow();
                $row->addLabel('caseSensitive', __('Case Sensitive'));
                $row->addYesNo('caseSensitive')->selected('N')->required()->selected($values['caseSensitive']);

            $row = $form->addRow();
                $row->addLabel('priority', __('Priority'))->description(__('Higher priorities are substituted first.'));
                $row->addNumber('priority')->required()->maxLength(2)->setValue($values['priority']);

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            echo $form->getOutput();
        }
    }
}
