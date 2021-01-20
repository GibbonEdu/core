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

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

$page->breadcrumbs
    ->add(__('Manage Catalog'), 'library_manage_catalog.php')
    ->add(__('Edit Item'));

if (isActionAccessible($guid, $connection2, '/modules/Library/library_manage_catalog_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Check if school year specified
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

			$urlParamKeys = array('name' => '', 'gibbonLibraryTypeID' => '', 'gibbonSpaceID' => '', 'status' => '', 'gibbonPersonIDOwnership' => '', 'typeSpecificFields' => '');

			$urlParams = array_intersect_key($_GET, $urlParamKeys);
			$urlParams = array_merge($urlParamKeys, $urlParams);

            if ($_GET['name'] != '' or $_GET['gibbonLibraryTypeID'] != '' or $_GET['gibbonSpaceID'] != '' or $_GET['status'] != '' or $_GET['gibbonPersonIDOwnership'] != '' or $_GET['typeSpecificFields'] != '') {
                echo "<div class='linkTop'>";
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Library/library_manage_catalog.php&'.http_build_query($urlParams)."'>".__('Back to Search Results').'</a>';
                echo '</div>';
			}

			$form = Form::create('libraryCatalog', $_SESSION[$guid]['absoluteURL'].'/modules/Library/library_manage_catalog_editProcess.php?'.http_build_query($urlParams));
			$form->setFactory(DatabaseFormFactory::create($pdo));

			$form->addHiddenValue('address', $_SESSION[$guid]['address']);
			$form->addHiddenValue('gibbonLibraryTypeID', $values['gibbonLibraryTypeID']);
			$form->addHiddenValue('gibbonLibraryItemID', $gibbonLibraryItemID);
			$form->addHiddenValue('type', $values['type']);
			$form->addHiddenValue('statusBorrowable', 'Available');

			$form->addRow()->addHeading(__('Catalog Type'));

			$row = $form->addRow();
				$row->addLabel('typeText', __('Type'));
				$row->addTextField('typeText')->required()->readOnly()->setValue(__($values['type']));

			$form->addRow()->addHeading(__('General Details'));

			$row = $form->addRow();
				$row->addLabel('name', __('Name'))->description(__('Volume or product name.'));
				$row->addTextField('name')->required()->maxLength(255);

			$row = $form->addRow();
				$row->addLabel('id', __('ID'));
				$row->addTextField('id')
					->uniqueField('./modules/Library/library_manage_catalog_idCheckAjax.php', array('gibbonLibraryItemID' => $gibbonLibraryItemID))
					->required()
					->maxLength(255);

			$row = $form->addRow();
				$row->addLabel('producer', __('Author/Brand'))->description(__('Who created the item?'));
				$row->addTextField('producer')->required()->maxLength(255);

			$row = $form->addRow();
				$row->addLabel('vendor', __('Vendor'))->description(__('Who supplied the item?'));
				$row->addTextField('vendor')->maxLength(100);

			$row = $form->addRow();
				$row->addLabel('purchaseDate', __('Purchase Date'));
				$row->addDate('purchaseDate');

			$row = $form->addRow();
				$row->addLabel('invoiceNumber', __('Invoice Number'));
				$row->addTextField('invoiceNumber')->maxLength(50);

			$row = $form->addRow();
				$row->addLabel('imageType', __('Image Type'));
				$row->addSelect('imageType')->fromArray(array('File' => __('File'), 'Link' => __('Link')))->placeholder();

			$form->toggleVisibilityByClass('imageFile')->onSelect('imageType')->when('File');

			$row = $form->addRow()->addClass('imageFile');
				$row->addLabel('imageFile', __('Image File'))
					->description(__('240px x 240px or smaller.'));
				$row->addFileUpload('imageFile')
					->accepts('.jpg,.jpeg,.gif,.png')
					->setMaxUpload(false)
					->required();

			$form->toggleVisibilityByClass('imageLink')->onSelect('imageType')->when('Link');

			$row = $form->addRow()->addClass('imageLink');
				$row->addLabel('imageLink', __('Image Link'))
					->description(__('240px x 240px or smaller.'));
				$row->addURL('imageLink')->maxLength(255)->required()->setValue($values['imageLocation']);

			$row = $form->addRow();
				$row->addLabel('gibbonSpaceID', __('Location'));
				$row->addSelectSpace('gibbonSpaceID')->placeholder();

			$row = $form->addRow();
				$row->addLabel('locationDetail', __('Location Detail'))->description(__('Shelf, cabinet, sector, etc'));
				$row->addTextField('locationDetail')->maxLength(255);

			$row = $form->addRow();
				$row->addLabel('ownershipType', __('Ownership Type'));
				$row->addSelect('ownershipType')->fromArray(array('School' => __('School'), 'Individual' => __('Individual')))->placeholder();

			$form->toggleVisibilityByClass('ownershipSchool')->onSelect('ownershipType')->when('School');

			$row = $form->addRow()->addClass('ownershipSchool');
				$row->addLabel('gibbonPersonIDOwnershipSchool', __('Main User'))->description(__('Person the device is assigned to.'));
				$row->addSelectUsers('gibbonPersonIDOwnershipSchool')->placeholder()->selected($values['gibbonPersonIDOwnership']);

			$form->toggleVisibilityByClass('ownershipIndividual')->onSelect('ownershipType')->when('Individual');

			$row = $form->addRow()->addClass('ownershipIndividual');
				$row->addLabel('gibbonPersonIDOwnershipIndividual', __('Owner'));
				$row->addSelectUsers('gibbonPersonIDOwnershipIndividual')->placeholder()->selected($values['gibbonPersonIDOwnership']);

			$sql = "SELECT gibbonDepartmentID AS value, name FROM gibbonDepartment ORDER BY name";
			$row = $form->addRow();
				$row->addLabel('gibbonDepartmentID', __('Department'))->description(__('Which department is responsible for the item?'));
				$row->addSelect('gibbonDepartmentID')->fromQuery($pdo, $sql, array())->placeholder();

			$row = $form->addRow();
				$row->addLabel('bookable', __('Bookable As Facility?'))->description(__('Can item be booked via Facility Booking in Timetable? Useful for laptop carts, etc.'));
				$row->addYesNo('bookable');

			$row = $form->addRow();
				$row->addLabel('borrowable', __('Borrowable?'))->description(__('Is item available for loan?'));
				$row->addYesNo('borrowable');


			$form->toggleVisibilityByClass('statusBorrowable')->onSelect('borrowable')->when('Y');
			$form->toggleVisibilityByClass('statusNotBorrowable')->onSelect('borrowable')->when('N');

			$statuses = array(
				'Available' => __('Available'),
				'In Use' => __('In Use'),
				'Reserved' => __('Reserved'),
				'Decommissioned' => __('Decommissioned'),
				'Lost' => __('Lost'),
				'Repair' => __('Repair')
			);
			$row = $form->addRow()->addClass('statusBorrowable');
				$row->addLabel('statusBorrowableText', __('Status?'));
				$row->addTextField('statusBorrowableText')->required()->readOnly()->setValue(__('Available'));

            $row = $form->addRow()->addClass('statusNotBorrowable');
				$row->addLabel('statusNotBorrowable', __('Status?'));
				$row->addSelect('statusNotBorrowable')->fromArray($statuses)->required()->selected($values['status']);

			$row = $form->addRow();
				$row->addLabel('replacement', __('Plan Replacement?'));
				$row->addYesNo('replacement')->required()->selected('N');

			$form->toggleVisibilityByClass('replacement')->onSelect('replacement')->when('Y');

			$row = $form->addRow()->addClass('replacement');
					$row->addLabel('gibbonSchoolYearIDReplacement', __('Replacement Year'))->description(__('When is this item scheduled for replacement.'));
					$row->addSelectSchoolYear('gibbonSchoolYearIDReplacement', 'All', 'DESC');

			$row = $form->addRow()->addClass('replacement');
				$row->addLabel('replacementCost', __('Replacement Cost'));
				$row->addCurrency('replacementCost')->maxLength(9);

			$conditions = array(
				'As New' => __('As New'),
				'Lightly Worn' => __('Lightly Worn'),
				'Moderately Worn' => __('Moderately Worn'),
				'Damaged' => __('Damaged'),
				'Unusable' => __('Unusable')
			);
			$row = $form->addRow();
				$row->addLabel('physicalCondition', __('Physical Condition'))->description(__('Initial availability.'));
				$row->addSelect('physicalCondition')->fromArray($conditions)->placeholder();

			$row = $form->addRow();
				$row->addLabel('comment', __('Comments/Notes'));
				$row->addTextArea('comment')->setRows(10);

			$form->addRow()->addHeading(__('Type-Specific Details'));

			// Type-specific form fields loaded via ajax
			$row = $form->addRow('detailsRow')->addContent('');

			$row = $form->addRow();
				$row->addSubmit();

			$form->loadAllValuesFrom($values);

			echo $form->getOutput();
        }
    }
}
?>
<script type='text/javascript'>
	$(document).ready(function(){

		var path = '<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/Library/library_manage_catalog_fields_ajax.php'; ?>';

		$('#detailsRow div').html("<div id='details' name='details' style='min-height: 100px; text-align: center'><img style='margin: 10px 0 5px 0' src='<?php echo $_SESSION[$guid]['absoluteURL']; ?>/themes/<?php echo $_SESSION[$guid]['gibbonThemeName']; ?>/img/loading.gif' alt='Loading' onclick='return false;' /><br/>Loading</div>");

		$('#detailsRow div').load(path, { 'gibbonLibraryTypeID': '<?php echo $values['gibbonLibraryTypeID']; ?>', 'gibbonLibraryItemID': '<?php echo $gibbonLibraryItemID; ?>' });

	});
</script>
