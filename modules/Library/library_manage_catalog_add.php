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
    ->add(__('Add Item'));

if (isActionAccessible($guid, $connection2, '/modules/Library/library_manage_catalog_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $urlParamKeys = array('name' => '', 'gibbonLibraryTypeID' => '', 'gibbonSpaceID' => '', 'status' => '', 'gibbonPersonIDOwnership' => '', 'typeSpecificFields' => '');

    $urlParams = array_intersect_key($_GET, $urlParamKeys);
    $urlParams = array_merge($urlParamKeys, $urlParams);

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Library/library_manage_catalog_edit.php&gibbonLibraryItemID='.$_GET['editID'].'&'.http_build_query($urlParams);
    }
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], $editLink, null);
    }

    if (array_filter($urlParams)) {
        echo "<div class='linkTop'>";
        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Library/library_manage_catalog.php&'.http_build_query($urlParams)."'>".__('Back to Search Results').'</a>';
        echo '</div>';
	}

    $form = Form::create('libraryCatalog', $_SESSION[$guid]['absoluteURL'].'/modules/Library/library_manage_catalog_addProcess.php?'.http_build_query($urlParams));
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    $form->addRow()->addHeading(__('Catalog Type'));

    $sql = "SELECT gibbonLibraryTypeID AS value, name FROM gibbonLibraryType WHERE active='Y' ORDER BY name";
    $row = $form->addRow();
        $row->addLabel('gibbonLibraryTypeID', __('Type'));
        $row->addSelect('gibbonLibraryTypeID')
            ->fromQuery($pdo, $sql, array())
            ->placeholder()
            ->required()
            ->selected($urlParams['gibbonLibraryTypeID']);

    $form->toggleVisibilityByClass('general')->onSelect('gibbonLibraryTypeID')->whenNot('Please select...');

    $form->addRow()->addClass('general')->addHeading(__('General Details'))->addClass('general');

    $row = $form->addRow()->addClass('general');
        $row->addLabel('name', __('Name'))->description(__('Volume or product name.'));
        $row->addTextField('name')->required()->maxLength(255);

    $row = $form->addRow()->addClass('general');
        $row->addLabel('idCheck', __('ID'));
        $row->addTextField('idCheck')
            ->uniqueField('./modules/Library/library_manage_catalog_idCheckAjax.php')
            ->required()
            ->maxLength(255);

    $row = $form->addRow()->addClass('general');
        $row->addLabel('producer', __('Author/Brand'))->description(__('Who created the item?'));
        $row->addTextField('producer')->required()->maxLength(255);

    $row = $form->addRow()->addClass('general');
        $row->addLabel('vendor', __('Vendor'))->description(__('Who supplied the item?'));
        $row->addTextField('vendor')->maxLength(100);

    $row = $form->addRow()->addClass('general');
        $row->addLabel('purchaseDate', __('Purchase Date'));
        $row->addDate('purchaseDate');

    $row = $form->addRow()->addClass('general');
        $row->addLabel('invoiceNumber', __('Invoice Number'));
        $row->addTextField('invoiceNumber')->maxLength(50);

    $row = $form->addRow()->addClass('general');
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
        $row->addURL('imageLink')->maxLength(255)->required();

    $row = $form->addRow()->addClass('general');
        $row->addLabel('gibbonSpaceID', __('Location'));
        $row->addSelectSpace('gibbonSpaceID')->placeholder();

    $row = $form->addRow()->addClass('general');
        $row->addLabel('locationDetail', __('Location Detail'))->description(__('Shelf, cabinet, sector, etc'));
        $row->addTextField('locationDetail')->maxLength(255);

    $row = $form->addRow()->addClass('general');
        $row->addLabel('ownershipType', __('Ownership Type'));
        $row->addSelect('ownershipType')->fromArray(array('School' => __('School'), 'Individual' => __('Individual')))->placeholder();

    $form->toggleVisibilityByClass('ownershipSchool')->onSelect('ownershipType')->when('School');

    $row = $form->addRow()->addClass('ownershipSchool');
        $row->addLabel('gibbonPersonIDOwnershipSchool', __('Main User'))->description(__('Person the device is assigned to.'));
        $row->addSelectUsers('gibbonPersonIDOwnershipSchool')->placeholder();

    $form->toggleVisibilityByClass('ownershipIndividual')->onSelect('ownershipType')->when('Individual');

    $row = $form->addRow()->addClass('ownershipIndividual');
        $row->addLabel('gibbonPersonIDOwnershipIndividual', __('Owner'));
        $row->addSelectUsers('gibbonPersonIDOwnershipIndividual')->placeholder();

    $sql = "SELECT gibbonDepartmentID AS value, name FROM gibbonDepartment ORDER BY name";
    $row = $form->addRow()->addClass('general');
        $row->addLabel('gibbonDepartmentID', __('Department'))->description(__('Which department is responsible for the item?'));
        $row->addSelect('gibbonDepartmentID')->fromQuery($pdo, $sql, array())->placeholder();

    $row = $form->addRow()->addClass('general');
        $row->addLabel('bookable', __('Bookable As Facility?'))->description(__('Can item be booked via Facility Booking in Timetable? Useful for laptop carts, etc.'));
        $row->addYesNo('bookable')->selected('N');

    $row = $form->addRow()->addClass('general');
        $row->addLabel('borrowable', __('Borrowable?'))->description(__('Is item available for loan?'));
        $row->addYesNo('borrowable');

    $statuses = array(
        'Available' => __('Available'),
        'In Use' => __('In Use'),
        'Reserved' => __('Reserved'),
        'Decommissioned' => __('Decommissioned'),
        'Lost' => __('Lost'),
        'Repair' => __('Repair')
    );
    $row = $form->addRow()->addClass('general');
        $row->addLabel('status', __('Status?'))->description(__('Initial availability.'));
        $row->addSelect('status')->fromArray($statuses)->required();

    $row = $form->addRow()->addClass('general');
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
    $row = $form->addRow()->addClass('general');
        $row->addLabel('physicalCondition', __('Physical Condition'))->description(__('Initial availability.'));
        $row->addSelect('physicalCondition')->fromArray($conditions)->placeholder();

    $row = $form->addRow()->addClass('general');
        $row->addLabel('comment', __('Comments/Notes'));
        $row->addTextArea('comment')->setRows(10);

    $form->addRow()->addClass('general')->addHeading(__('Type-Specific Details'))->addClass('general');

    // Type-specific form fields loaded via ajax
    $row = $form->addRow('detailsRow')->addClass('general')->addContent('')->addClass('general');

    $row = $form->addRow()->addClass('general');
        $row->addSubmit();

    echo $form->getOutput();
}
?>
<script type='text/javascript'>
	$(document).ready(function(){
		$('#gibbonLibraryTypeID').change(function(){
			var path = '<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/Library/library_manage_catalog_fields_ajax.php'; ?>';

            $('#detailsRow .general').html("<div id='details' name='details' style='min-height: 100px; text-align: center'><img style='margin: 10px 0 5px 0' src='<?php echo $_SESSION[$guid]['absoluteURL']; ?>/themes/<?php echo $_SESSION[$guid]['gibbonThemeName']; ?>/img/loading.gif' alt='Loading' onclick='return false;' /><br/>Loading</div>");

			$('#detailsRow .general').load(path, { 'gibbonLibraryTypeID': $(this).val() });
		});
	});
</script>
