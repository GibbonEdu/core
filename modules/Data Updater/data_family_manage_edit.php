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

use Gibbon\Forms\Form;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Data Updater/data_family_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');
    $urlParams = ['gibbonSchoolYearID' => $gibbonSchoolYearID];

    $page->breadcrumbs
        ->add(__('Family Data Updates'), 'data_family_manage.php', $urlParams)
        ->add(__('Edit Request'));

    //Check if gibbonFamilyUpdateID specified
    $gibbonFamilyUpdateID = $_GET['gibbonFamilyUpdateID'] ?? '';
    if ($gibbonFamilyUpdateID == 'Y') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {

            $data = array('gibbonFamilyUpdateID' => $gibbonFamilyUpdateID);
            $sql = 'SELECT gibbonFamily.* FROM gibbonFamilyUpdate JOIN gibbonFamily ON (gibbonFamilyUpdate.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonFamilyUpdateID=:gibbonFamilyUpdateID';
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            $page->addError(__('The selected record does not exist, or you do not have access to it.'));
        } else {
			$data = array('gibbonFamilyUpdateID' => $gibbonFamilyUpdateID);
			$sql = 'SELECT gibbonFamilyUpdate.* FROM gibbonFamilyUpdate JOIN gibbonFamily ON (gibbonFamilyUpdate.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonFamilyUpdateID=:gibbonFamilyUpdateID';
			$newResult = $pdo->executeQuery($data, $sql);

            //Let's go!
			$oldValues = $result->fetch();
			$newValues = $newResult->fetch();
			
			// Provide a link back to edit the associated record
            if (isActionAccessible($guid, $connection2, '/modules/User Admin/family_manage_edit.php')) {
                $params = [ 
                    'gibbonFamilyID' => $oldValues['gibbonFamilyID']
                ];
                $page->navigator->addHeaderAction('edit', __('Edit Family'))
                    ->setURL('/modules/User Admin/family_manage_edit.php')
                    ->addParams($params)
                    ->setIcon('config')
                    ->displayLabel();
            }

			$compare = array(
				'nameAddress'           => __('Address Name'),
				'homeAddress'           => __('Home Address'),
				'homeAddressDistrict'   => __('Home Address (District)'),
				'homeAddressCountry'    => __('Home Address (Country)'),
				'languageHomePrimary'   => __('Home Language - Primary'),
				'languageHomeSecondary' => __('Home Language - Secondary'),
			);

			$form = Form::createTable('updateFamily', $session->get('absoluteURL').'/modules/'.$session->get('module').'/data_family_manage_editProcess.php?gibbonFamilyUpdateID='.$gibbonFamilyUpdateID);

			$form->setClass('fullWidth colorOddEven');
			$form->addHiddenValue('address', $session->get('address'));
			$form->addHiddenValue('gibbonFamilyID', $oldValues['gibbonFamilyID']);

			$row = $form->addRow()->setClass('head heading');
				$row->addContent(__('Field'));
				$row->addContent(__('Current Value'));
				$row->addContent(__('New Value'));
				$row->addContent(__('Accept'));

            $changeCount = 0;
			foreach ($compare as $fieldName => $label) {
				$isMatching = ($oldValues[$fieldName] != $newValues[$fieldName]);

				$row = $form->addRow();
					$row->addLabel('new'.$fieldName.'On', $label);
					$row->addContent($oldValues[$fieldName]);
					$row->addContent($newValues[$fieldName])->addClass($isMatching ? 'matchHighlightText' : '');

				if ($isMatching) {
					$row->addCheckbox('new'.$fieldName.'On')->checked(true)->setClass('textCenter');
					$form->addHiddenValue('new'.$fieldName, $newValues[$fieldName]);
                    $changeCount++;
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
