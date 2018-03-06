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

@session_start();

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Library/library_manage_catalog_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/library_manage_catalog.php'>".__($guid, 'Manage Catalog')."</a> > </div><div class='trailEnd'>".__($guid, 'Add Item').'</div>';
    echo '</div>';

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Library/library_manage_catalog_edit.php&gibbonLibraryItemID='.$_GET['editID'].'&name='.$_GET['name'].'&gibbonLibraryTypeID='.$_GET['gibbonLibraryTypeID'].'&gibbonSpaceID='.$_GET['gibbonSpaceID'].'&status='.$_GET['status'].'&gibbonPersonIDOwnership='.$_GET['gibbonPersonIDOwnership'].'&typeSpecificFields='.$_GET['typeSpecificFields'];
    }
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], $editLink, null);
    }

    if ($_GET['name'] != '' or $_GET['gibbonLibraryTypeID'] != '' or $_GET['gibbonSpaceID'] != '' or $_GET['status'] != '' or $_GET['gibbonPersonIDOwnership'] != '' or $_GET['typeSpecificFields'] != '') {
        echo "<div class='linkTop'>";
        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Library/library_manage_catalog.php&name='.$_GET['name'].'&gibbonLibraryTypeID='.$_GET['gibbonLibraryTypeID'].'&gibbonSpaceID='.$_GET['gibbonSpaceID'].'&status='.$_GET['status'].'&gibbonPersonIDOwnership='.$_GET['gibbonPersonIDOwnership'].'&typeSpecificFields='.$_GET['typeSpecificFields']."'>".__($guid, 'Back to Search Results').'</a>';
        echo '</div>';
    }

    $form = Form::create('search', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('q', "/modules/".$_SESSION[$guid]['module']."/library_manage_catalog.php");

    $form->addRow()->addHeading(__('Catalog Type'));

    $sql = "SELECT gibbonLibraryTypeID AS value, name FROM gibbonLibraryType WHERE active='Y' ORDER BY name";
    $row = $form->addRow();
        $row->addLabel('gibbonLibraryTypeID', __('Type'));
        $row->addSelect('gibbonLibraryTypeID')->fromQuery($pdo, $sql, array())->placeholder()->isRequired();

    $form->toggleVisibilityByClass('general')->onSelect('gibbonLibraryTypeID')->whenNot('Please select...');

    $form->addRow()->addHeading(__('General Details'))->addClass('general');

    $row = $form->addRow()->addClass('general');
        $row->addLabel('name', __('Name'))->description(__('Volume or product name.'));
        $row->addTextField('name')->isRequired()->maxLength(255);

    $row = $form->addRow()->addClass('general');
        $row->addLabel('idCheck', __('ID'))->description(__('Must be unique.'));
        $row->addTextField('idCheck')->isRequired()->maxLength(255);

    $row = $form->addRow()->addClass('general');
        $row->addLabel('producer', __('Author/Brand'))->description(__('Who created the item?'));
        $row->addTextField('producer')->isRequired()->maxLength(255);

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

    $row = $form->addRow()->addClass('general imageFile');
        $row->addLabel('imageFile', __('Image File'))
            ->description(__('240px x 240px or smaller.'));
        $row->addFileUpload('file1')
            ->accepts('.jpg,.jpeg,.gif,.png')
            ->setMaxUpload(false)
            ->isRequired();

    $form->toggleVisibilityByClass('imageLink')->onSelect('imageType')->when('Link');

    $row = $form->addRow()->addClass('general imageLink');
        $row->addLabel('imageLink', __('Image Link'))
            ->description(__('240px x 240px or smaller.'));
        $row->addURL('imageLink')->maxLength(255)->isRequired();

    $row = $form->addRow()->addClass('general');
        $row->addLabel('gibbonSpaceID', __('Location'));
        $row->addSelectSpace('gibbonSpaceID')->placeholder();

    $row = $form->addRow()->addClass('general');
        $row->addLabel('invoiceNumber', __('Location Detail'))->description(__('Shelf, cabinet, sector, etc'));
        $row->addTextField('invoiceNumber')->maxLength(255);

    $row = $form->addRow()->addClass('general');
        $row->addLabel('ownershipType', __('Ownership Type'));
        $row->addSelect('ownershipType')->fromArray(array('School' => __('School'), 'Individual' => __('Individual')))->placeholder();

    $form->toggleVisibilityByClass('ownershipSchool')->onSelect('ownershipType')->when('School');

    $row = $form->addRow()->addClass('general ownershipSchool');
        $row->addLabel('gibbonPersonIDOwnershipSchool', __('Main User'))->description(__('Person the device is assigned to.'));
        $row->addSelectUsers('gibbonPersonIDOwnershipSchool')->placeholder();

    $form->toggleVisibilityByClass('ownershipIndividual')->onSelect('ownershipType')->when('Individual');

    $row = $form->addRow()->addClass('general ownershipIndividual');
        $row->addLabel('gibbonPersonIDOwnershipIndividual', __('Owner'));
        $row->addSelectUsers('gibbonPersonIDOwnershipIndividual')->placeholder();

    $sql = "SELECT gibbonDepartmentID AS value, name FROM gibbonDepartment ORDER BY name";
    $row = $form->addRow()->addClass('general');
        $row->addLabel('gibbonDepartmentID', __('Department'))->description(__('Which department is responsible for the item?'));
        $row->addSelect('gibbonDepartmentID')->fromQuery($pdo, $sql, array())->placeholder();

    $row = $form->addRow()->addClass('general');
        $row->addLabel('bookable', __('Bookable As Facility?'))->description(__('Can item be booked via Facility Booking in Timetable? Useful for laptop carts, etc.'));
        $row->addYesNo('bookable')->placeholder();

    $row = $form->addRow()->addClass('general');
        $row->addLabel('borrowable', __('Borrowable?'))->description(__('Is item available for loan?'));
        $row->addYesNo('borrowable')->placeholder();

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
        $row->addSelect('status')->fromArray($statuses)->isRequired();

    $row = $form->addRow()->addClass('general');
        $row->addLabel('replacement', __('Plan Replacement?'));
        $row->addYesNo('replacement')->isRequired()->selected('N');

    $form->toggleVisibilityByClass('replacement')->onSelect('replacement')->when('Y');

    $row = $form->addRow()->addClass('general replacement');
            $row->addLabel('gibbonSchoolYearIDReplacement', __('Replacement Year'))->description('When is this item scheduled for replacement.');
            $row->addSelectSchoolYear('gibbonSchoolYearIDReplacement', 'All', 'DESC');

    $row = $form->addRow()->addClass('general replacement');
        $row->addLabel('payment', __('Replacement Cost'));
        $row->addCurrency('payment')->maxLength(9);

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

    $form->addRow()->addHeading(__('Type-Specific Details'))->addClass('general');

    $details = "<div id='details' name='details' style='min-height: 100px; text-align: center'>";
        $details .= "<img style='margin: 10px 0 5px 0' src='".$_SESSION[$guid]['absoluteURL']."/themes/".$_SESSION[$guid]['gibbonThemeName']."/img/loading.gif' alt='Loading' onclick='return false;' /><br/>Loading";
    $details .= "</div>";
    $form->addRow()->addContent($details)->addClass('general');;

    $row = $form->addRow();
        $row->addSubmit();

    echo $form->getOutput();
}
?>
