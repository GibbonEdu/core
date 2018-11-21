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
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Data Updater/data_family_manage_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    $gibbonSchoolYearID = isset($_REQUEST['gibbonSchoolYearID'])? $_REQUEST['gibbonSchoolYearID'] : $_SESSION[$guid]['gibbonSchoolYearID'];
    $urlParams = ['gibbonSchoolYearID' => $gibbonSchoolYearID];
    
    $page->breadcrumbs
        ->add(__('Family Data Updates'), 'data_family_manage.php', $urlParams)
        ->add(__('Edit Request'));
    
    //Check if school year specified
    $gibbonFamilyUpdateID = $_GET['gibbonFamilyUpdateID'];
    if ($gibbonFamilyUpdateID == 'Y') {
        echo "<div class='error'>";
        echo __('You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonFamilyUpdateID' => $gibbonFamilyUpdateID);
            $sql = 'SELECT gibbonFamily.* FROM gibbonFamilyUpdate JOIN gibbonFamily ON (gibbonFamilyUpdate.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonFamilyUpdateID=:gibbonFamilyUpdateID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __('The selected record does not exist, or you do not have access to it.');
            echo '</div>';
        } else {
            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return'], null, null);
			}
			
			$data = array('gibbonFamilyUpdateID' => $gibbonFamilyUpdateID);
			$sql = 'SELECT gibbonFamilyUpdate.* FROM gibbonFamilyUpdate JOIN gibbonFamily ON (gibbonFamilyUpdate.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonFamilyUpdateID=:gibbonFamilyUpdateID';
			$newResult = $pdo->executeQuery($data, $sql);

            //Let's go!
			$oldValues = $result->fetch(); 
			$newValues = $newResult->fetch();
            
            // Provide a link back to edit the associated record
            if (isActionAccessible($guid, $connection2, '/modules/User Admin/family_manage_edit.php') == true) {
                echo "<div class='linkTop'>";
                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/User Admin/family_manage_edit.php&gibbonFamilyID=".$oldValues['gibbonFamilyID']."'>".__('Edit Family')."<img style='margin: 0 0 -4px 5px' title='".__('Edit Family')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                echo '</div>';
            }

			$compare = array(
				'nameAddress'           => __('Address Name'),
				'homeAddress'           => __('Home Address'),
				'homeAddressDistrict'   => __('Home Address (District)'),
				'homeAddressCountry'    => __('Home Address (Country)'),
				'languageHomePrimary'   => __('Home Language - Primary'),
				'languageHomeSecondary' => __('Home Language - Secondary'),
			);

			$form = Form::create('updateFamily', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/data_family_manage_editProcess.php?gibbonFamilyUpdateID='.$gibbonFamilyUpdateID);
			
			$form->setClass('fullWidth colorOddEven');
			$form->addHiddenValue('address', $_SESSION[$guid]['address']);
			$form->addHiddenValue('gibbonFamilyID', $oldValues['gibbonFamilyID']);

			$row = $form->addRow()->setClass('head heading');
				$row->addContent(__('Field'));
				$row->addContent(__('Current Value'));
				$row->addContent(__('New Value'));
				$row->addContent(__('Accept'));

			foreach ($compare as $fieldName => $label) {
				$isMatching = ($oldValues[$fieldName] != $newValues[$fieldName]);

				$row = $form->addRow();
					$row->addLabel('new'.$fieldName.'On', $label);
					$row->addContent($oldValues[$fieldName]);
					$row->addContent($newValues[$fieldName])->addClass($isMatching ? 'matchHighlightText' : '');
				
				if ($isMatching) {
					$row->addCheckbox('new'.$fieldName.'On')->checked(true)->setClass('textCenter');
					$form->addHiddenValue('new'.$fieldName, $newValues[$fieldName]);
				} else {
					$row->addContent();
				}
			}
			
			$row = $form->addRow();
				$row->addSubmit();

			echo $form->getOutput();
        }
    }
}
