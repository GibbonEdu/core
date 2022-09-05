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

use Gibbon\Http\Url;
use Gibbon\Forms\Form;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

$page->breadcrumbs
    ->add(__('Manage Catalog'), 'library_manage_catalog.php')
    ->add(__('Duplicate Item'));

if (isActionAccessible($guid, $connection2, '/modules/Library/library_manage_catalog_duplicate.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    //Check if gibbonLibraryItemID specified
    $gibbonLibraryItemID = $_GET['gibbonLibraryItemID'];
    if ($gibbonLibraryItemID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        $data = array('gibbonLibraryItemID' => $gibbonLibraryItemID);
        $sql = 'SELECT gibbonLibraryItem.*, gibbonLibraryType.name AS type FROM gibbonLibraryItem JOIN gibbonLibraryType ON (gibbonLibraryItem.gibbonLibraryTypeID=gibbonLibraryType.gibbonLibraryTypeID) WHERE gibbonLibraryItemID=:gibbonLibraryItemID';
        $result = $connection2->prepare($sql);
        $result->execute($data);

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __('The specified record does not exist.');
            echo '</div>';
        } else {
            //Let's go!
            $values = $result->fetch();

            $step = null;
            if (isset($_GET['step'])) {
                $step = $_GET['step'];
            }
            if ($step != 1 and $step != 2) {
                $step = 1;
            }
            
            $urlParamKeys = array('name' => '', 'gibbonLibraryTypeID' => '', 'gibbonSpaceID' => '', 'status' => '', 'gibbonPersonIDOwnership' => '', 'typeSpecificFields' => '');

            $urlParams = array_intersect_key($_GET, $urlParamKeys);
            $urlParams = array_merge($urlParamKeys, $urlParams);

            //Step 1
            if ($step == 1) {
                if (array_filter($urlParams)) {
                    $page->navigator->addSearchResultsAction(Url::fromModuleRoute('Library', 'library_manage_catalog.php')->withQueryParams($urlParams));
                }

                $form = Form::create('action', $session->get('absoluteURL').'/index.php?q=/modules/'.$session->get('module').'/library_manage_catalog_duplicate.php&step=2&gibbonLibraryItemID='.$values['gibbonLibraryItemID'].'&'.http_build_query($urlParams));

                $form->addHiddenValue('address', $session->get('address'));

                $form->addRow()->addHeading('Step 1 - Quantity', __('Step 1 - Quantity'));

                $form->addHiddenValue('gibbonLibraryTypeID', $values['gibbonLibraryTypeID']);
                $row = $form->addRow();
                    $row->addLabel('type', __('Type'));
                    $row->addTextField('type')->setValue($values['type'])->readonly()->required();

                $row = $form->addRow();
                    $row->addLabel('name', __('Name'));
                    $row->addTextField('name')->setValue($values['name'])->readonly()->required();

                $row = $form->addRow();
                    $row->addLabel('id', __('ID'));
                    $row->addTextField('id')->setValue($values['id'])->readonly()->required();

                $row = $form->addRow();
                    $row->addLabel('producer', __('Author/Brand'));
                    $row->addTextField('producer')->setValue($values['producer'])->readonly()->required();

                $options = array();
                for ($i = 1; $i < 21; ++$i) {
                    $options[$i] = $i;
                }
                $row = $form->addRow();
                    $row->addLabel('number', __('Number of Copies'))->description('How many copies do you want to make of this item?');
                    $row->addSelect('number')->fromArray($options)->required();

                $row = $form->addRow();
                    $row->addFooter();
                    $row->addSubmit();

                echo $form->getOutput();
            }
            //Step 1
            elseif ($step == 2) {
                if (array_filter($urlParams)) {
                    $page->navigator->addSearchResultsAction(Url::fromModuleRoute('Library', 'library_manage_catalog.php')->withQueryParams($urlParams));
                }

                $number = $_POST['number'];

                $form = Form::create('action', $session->get('absoluteURL').'/modules/'.$session->get('module').'/library_manage_catalog_duplicateProcess.php?gibbonLibraryItemID='.$values['gibbonLibraryItemID'].'&'.http_build_query($urlParams));

                $form->addHiddenValue('address', $session->get('address'));
                $form->addHiddenValue('count', $number);
                $form->addHiddenValue('gibbonLibraryTypeID', $_POST['gibbonLibraryTypeID']);
                $form->addHiddenValue('gibbonLibraryItemID', $values['gibbonLibraryItemID']);

                $form->addRow()->addHeading('Step 2 - Details', __('Step 2 - Details'));

                for ($i = 1; $i <= $number; ++$i) {
                    $row = $form->addRow();
                        $row->addLabel('id'.$i, sprintf(__('Copy %1$s ID'), $i));
                        $row->addTextField('id'.$i)
                            ->uniqueField('./modules/Library/library_manage_catalog_idCheckAjax.php', array('fieldName' => 'id'))
                            ->required()
                            ->maxLength(255);
                }

                $row = $form->addRow();
                    $row->addFooter();
                    $row->addSubmit();

                echo $form->getOutput();
            }
        }
    }
}
?>
