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

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Library/library_manage_catalog_duplicate.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/library_manage_catalog.php'>".__($guid, 'Manage Catalog')."</a> > </div><div class='trailEnd'>".__($guid, 'Duplicate Item').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Check if school year specified
    $gibbonLibraryItemID = $_GET['gibbonLibraryItemID'];
    if ($gibbonLibraryItemID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonLibraryItemID' => $gibbonLibraryItemID);
            $sql = 'SELECT gibbonLibraryItem.*, gibbonLibraryType.name AS type FROM gibbonLibraryItem JOIN gibbonLibraryType ON (gibbonLibraryItem.gibbonLibraryTypeID=gibbonLibraryType.gibbonLibraryTypeID) WHERE gibbonLibraryItemID=:gibbonLibraryItemID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __($guid, 'The specified record does not exist.');
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

            //Step 1
            if ($step == 1) {
                if ($_GET['name'] != '' or $_GET['gibbonLibraryTypeID'] != '' or $_GET['gibbonSpaceID'] != '' or $_GET['status'] != '' or $_GET['gibbonPersonIDOwnership'] != '' or $_GET['typeSpecificFields'] != '') {
                    echo "<div class='linkTop'>";
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Library/library_manage_catalog.php&name='.$_GET['name'].'&gibbonLibraryTypeID='.$_GET['gibbonLibraryTypeID'].'&gibbonSpaceID='.$_GET['gibbonSpaceID'].'&status='.$_GET['status'].'&gibbonPersonIDOwnership='.$_GET['gibbonPersonIDOwnership'].'&typeSpecificFields='.$_GET['typeSpecificFields']."'>".__($guid, 'Back to Search Results').'</a>';
                    echo '</div>';
                }

                $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/library_manage_catalog_duplicate.php&step=2&gibbonLibraryItemID='.$values['gibbonLibraryItemID'].'&name='.$_GET['name'].'&gibbonLibraryTypeID='.$_GET['gibbonLibraryTypeID'].'&gibbonSpaceID='.$_GET['gibbonSpaceID'].'&status='.$_GET['status'].'&gibbonPersonIDOwnership='.$_GET['gibbonPersonIDOwnership'].'&typeSpecificFields='.$_GET['typeSpecificFields']);

                $form->addHiddenValue('address', $_SESSION[$guid]['address']);

                $form->addRow()->addHeading(__('Step 1 - Quantity'));

                $form->addHiddenValue('gibbonLibraryTypeID', $values['gibbonLibraryTypeID']);
                $row = $form->addRow();
                    $row->addLabel('type', __('Type'));
                    $row->addTextField('type')->setValue($values['type'])->readonly()->isRequired();

                $row = $form->addRow();
                    $row->addLabel('name', __('Name'));
                    $row->addTextField('name')->setValue($values['name'])->readonly()->isRequired();

                $row = $form->addRow();
                    $row->addLabel('id', __('ID'));
                    $row->addTextField('id')->setValue($values['id'])->readonly()->isRequired();

                $row = $form->addRow();
                    $row->addLabel('producer', __('Author/Brand'));
                    $row->addTextField('producer')->setValue($values['producer'])->readonly()->isRequired();

                $options = array();
                for ($i = 1; $i < 21; ++$i) {
                    $options[$i] = $i;
                }
                $row = $form->addRow();
                    $row->addLabel('number', __('Number of Copies'))->description('How many copies do you want to make of this item?');
                    $row->addSelect('number')->fromArray($options)->isRequired();

                $row = $form->addRow();
                    $row->addFooter();
                    $row->addSubmit();

                echo $form->getOutput();
            }
            //Step 1
            elseif ($step == 2) {
                if ($_GET['name'] != '' or $_GET['gibbonLibraryTypeID'] != '' or $_GET['gibbonSpaceID'] != '' or $_GET['status'] != '' or $_GET['gibbonPersonIDOwnership'] != '' or $_GET['typeSpecificFields'] != '') {
                    echo "<div class='linkTop'>";
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Library/library_manage_catalog.php&name='.$_GET['name'].'&gibbonLibraryTypeID='.$_GET['gibbonLibraryTypeID'].'&gibbonSpaceID='.$_GET['gibbonSpaceID'].'&status='.$_GET['status'].'&gibbonPersonIDOwnership='.$_GET['gibbonPersonIDOwnership'].'&typeSpecificFields='.$_GET['typeSpecificFields']."'>".__($guid, 'Back to Search Results').'</a>';
                    echo '</div>';
                }

                $number = $_POST['number'];

                $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/library_manage_catalog_duplicateProcess.php?gibbonLibraryItemID='.$values['gibbonLibraryItemID'].'&name='.$_GET['name'].'&gibbonLibraryTypeID='.$_GET['gibbonLibraryTypeID'].'&gibbonSpaceID='.$_GET['gibbonSpaceID'].'&status='.$_GET['status'].'&gibbonPersonIDOwnership='.$_GET['gibbonPersonIDOwnership'].'&typeSpecificFields='.$_GET['typeSpecificFields']);

                $form->addHiddenValue('address', $_SESSION[$guid]['address']);
                $form->addHiddenValue('count', $number);
                $form->addHiddenValue('gibbonLibraryTypeID', $_POST['gibbonLibraryTypeID']);
                $form->addHiddenValue('gibbonLibraryItemID', $values['gibbonLibraryItemID']);

                $form->addRow()->addHeading(__('Step 2 - Details'));

                for ($i = 1; $i <= $number; ++$i) {
                    $row = $form->addRow();
                        $row->addLabel('id'.$i, sprintf(__('Copy %1$s ID'), $i));
                        $row->addTextField('id'.$i)
                            ->isUnique('./modules/Library/library_manage_catalog_idCheckAjax.php', array('fieldName' => 'id'))
                            ->isRequired()
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
