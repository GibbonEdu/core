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
use Gibbon\Tables\DataTable;
use Gibbon\Domain\Library\LibraryGateway;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Services\Format;

$page->breadcrumbs->add(__('Manage Catalog'));

if (isActionAccessible($guid, $connection2, '/modules/Library/library_manage_catalog.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return']);
    }

    echo '<h3>';
    echo __('Search & Filter');
    echo '</h3>';

    //Get current filter values
    $name = null;
    if (isset($_POST['name'])) {
        $name = trim($_POST['name']);
    }
    if ($name == '') {
        if (isset($_GET['name'])) {
            $name = trim($_GET['name']);
        }
    }
    $gibbonLibraryTypeID = null;
    if (isset($_POST['gibbonLibraryTypeID'])) {
        $gibbonLibraryTypeID = trim($_POST['gibbonLibraryTypeID']);
    }
    if ($gibbonLibraryTypeID == '') {
        if (isset($_GET['gibbonLibraryTypeID'])) {
            $gibbonLibraryTypeID = trim($_GET['gibbonLibraryTypeID']);
        }
    }
    $gibbonSpaceID = null;
    if (isset($_POST['gibbonSpaceID'])) {
        $gibbonSpaceID = trim($_POST['gibbonSpaceID']);
    }
    if ($gibbonSpaceID == '') {
        if (isset($_GET['gibbonSpaceID'])) {
            $gibbonSpaceID = trim($_GET['gibbonSpaceID']);
        }
    }
    $status = null;
    if (isset($_POST['status'])) {
        $status = trim($_POST['status']);
    }
    if ($status == '') {
        if (isset($_GET['status'])) {
            $status = trim($_GET['status']);
        }
    }
    $gibbonPersonIDOwnership = null;
    if (isset($_POST['gibbonPersonIDOwnership'])) {
        $gibbonPersonIDOwnership = trim($_POST['gibbonPersonIDOwnership']);
    }
    if ($gibbonPersonIDOwnership == '') {
        if (isset($_GET['gibbonPersonIDOwnership'])) {
            $gibbonPersonIDOwnership = trim($_GET['gibbonPersonIDOwnership']);
        }
    }
    $typeSpecificFields = null;
    if (isset($_POST['typeSpecificFields'])) {
        $typeSpecificFields = trim($_POST['typeSpecificFields']);
    }
    if ($typeSpecificFields == '') {
        if (isset($_GET['typeSpecificFields'])) {
            $typeSpecificFields = trim($_GET['typeSpecificFields']);
        }
    }

    $form = Form::create('searchForm', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q', "/modules/".$_SESSION[$guid]['module']."/library_manage_catalog.php");

    $row = $form->addRow();
        $row->addLabel('name', __('ID/Name/Producer'));
        $row->addTextField('name')->setValue($name);

    $sql = "SELECT gibbonLibraryTypeID AS value, name FROM gibbonLibraryType WHERE active='Y' ORDER BY name";
    $row = $form->addRow();
        $row->addLabel('gibbonLibraryTypeID', __('Type'));
        $row->addSelect('gibbonLibraryTypeID')->fromQuery($pdo, $sql, array())->selected($gibbonLibraryTypeID)->placeholder();

    $row = $form->addRow();
        $row->addLabel('gibbonSpaceID', __('Location'));
        $row->addSelectSpace('gibbonSpaceID')->selected($gibbonSpaceID)->placeholder();

    $statuses = array(
        'Available' => __('Available'),
        'Decommissioned' => __('Decommissioned'),
        'In Use' => __('In Use'),
        'Lost' => __('Lost'),
        'On Loan' => __('On Loan'),
        'Repair' => __('Repair'),
        'Reserved' => __('Reserved')
    );
    $row = $form->addRow();
        $row->addLabel('status', __('Status'));
        $row->addSelect('status')->fromArray($statuses)->selected($status)->placeholder();

    $row = $form->addRow();
        $row->addLabel('gibbonPersonIDOwnership', __('Owner/User'));
        $row->addSelectUsers('gibbonPersonIDOwnership')->selected($gibbonPersonIDOwnership)->placeholder();

    $row = $form->addRow();
        $row->addLabel('typeSpecificFields', __('Type-Specific Fields'))->description(__('For example, a computer\'s MAC address or a book\'s ISBN.'));
        $row->addTextField('typeSpecificFields')->setValue($typeSpecificFields);

    $row = $form->addRow();
        $row->addSearchSubmit($gibbon->session, __('Clear Search'));

    echo $form->getOutput();

    //Set pagination variable
    $page = 1;
    if (isset($_GET['page'])) {
        $page = $_GET['page'];
    }
    if ((!is_numeric($page)) or $page < 1) {
        $page = 1;
    }

    //Search with filters applied
    try {
        $data = array();
        $sqlWhere = 'WHERE ';
        if ($name != '') {
            $data['name'] = '%'.$name.'%';
            $data['producer'] = '%'.$name.'%';
            $data['id'] = '%'.$name.'%';
            $sqlWhere .= '(name LIKE :name  OR producer LIKE :producer OR id LIKE :id) AND ';
        }
        if ($gibbonLibraryTypeID != '') {
            $data['gibbonLibraryTypeID'] = $gibbonLibraryTypeID;
            $sqlWhere .= 'gibbonLibraryTypeID=:gibbonLibraryTypeID AND ';
        }
        if ($gibbonSpaceID != '') {
            $data['gibbonSpaceID'] = $gibbonSpaceID;
            $sqlWhere .= 'gibbonSpaceID=:gibbonSpaceID AND ';
        }
        if ($status != '') {
            $data['status'] = $status;
            $sqlWhere .= 'status=:status AND ';
        }
        if ($gibbonPersonIDOwnership != '') {
            $data['gibbonPersonIDOwnership'] = $gibbonPersonIDOwnership;
            $sqlWhere .= 'gibbonPersonIDOwnership=:gibbonPersonIDOwnership AND ';
        }
        if ($typeSpecificFields != '') {
            $data['fields'] = '%'.$typeSpecificFields.'%';
            $sqlWhere .= 'fields LIKE :fields AND ';
        }
        if ($sqlWhere == 'WHERE ') {
            $sqlWhere = '';
        } else {
            $sqlWhere = substr($sqlWhere, 0, -5);
        }

        $sql = "SELECT * FROM gibbonLibraryItem $sqlWhere ORDER BY id";
        $sqlPage = $sql.' LIMIT '.$_SESSION[$guid]['pagination'].' OFFSET '.(($page - 1) * $_SESSION[$guid]['pagination']);
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    echo "<div class='linkTop'>";
    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/library_manage_catalog_add.php&name=$name&gibbonLibraryTypeID=$gibbonLibraryTypeID&gibbonSpaceID=$gibbonSpaceID&status=$status&gibbonPersonIDOwnership=$gibbonPersonIDOwnership&typeSpecificFields=".urlencode($typeSpecificFields)."'>".__('Add')."<img style='margin-left: 5px' title='".__('Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
    echo '</div>';

    if ($result->rowCount() < 1) {
        echo '<h3>';
        echo __('View');
        echo '</h3>';

        echo "<div class='error'>";
        echo __('There are no records to display.');
        echo '</div>';
    } else {
        echo '<h3>';
        echo __('View');
        echo "<span style='font-weight: normal; font-style: italic; font-size: 55%'> ".sprintf(__('%1$s record(s) in current view'), $result->rowCount()).'</span>';
        echo '</h3>';

        if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
            printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'top', "name=$name&gibbonLibraryTypeID=$gibbonLibraryTypeID&gibbonSpaceID=$gibbonSpaceID&status=$status");
        }
        
        $gateway = $container->get(LibraryGateway::class);
        $criteria = $gateway->newQueryCriteria(true)
                            ->filterBy('name',$name)
                            ->filterBy('type',$gibbonLibraryTypeID)
                            ->filterBy('location',$gibbonSpaceID)
                            ->filterBy('status',$status)
                            ->filterBy('owner',$gibbonPersonIDOwnership)
                            ->filterBy('typeSpecificFields',$typeSpecificFields)
                            ->fromPOST();
        $items = $gateway->queryCatalog($criteria);
        $table = DataTable::createPaginated('items',$criteria);
        $table->addColumn('id',__('School ID (Type)'))->format(function ($item) {
          return sprintf('<b>%1$s</b><br/>%2$s', $item['id'], Format::small($item['itemType']));
        });
        $table->addColumn('name',__('Name (Producer)'))->format(function ($item) {
          return sprintf('<b>%1$s</b><br/>%2$s', $item['name'], Format::small($item['producer']));
        });
        $table->addColumn('location', __('Location'))->format(function ($item) {    
            return sprintf('<b>%1$s</b><br/>%2$s', $item['spaceName'],Format::small($item['locationDetail']));
        });
        $table->addColumn('ownership',__('Ownership (User/Owner)'))->format(function ($item) {
            if ($item['ownershipType'] == 'School') {
                echo sprintf('<b>%1$s</b>',$_SESSION[$guid]['organisationNameShort']);
            } elseif ($item['ownershipType'] == 'Individual') {
                echo '<b>Individual</b>';
	    }
            echo sprintf('<br/>%1$s',Format::small(Format::name($item['title'],$item['preferredName'],$item['surname'])));
        });
        $table->addColumn('borrowable',__('Borrowable'))->format(function ($item) {
          echo '<b>' . $item['status'] . '</b><br/>';
          echo Format::small($item['borrowable'] == 'Y' ? 'Yes' : 'No');
        });
        $actions = $table->addActionColumn()
              ->addParam('gibbonLibraryItemID')
              ->addParam('name')
              ->addParam('gibbonSpaceID')
              ->addParam('status')
              ->addParam('gibbonPersonIDOwnership')
              ->addParam('typeSpecificFields')
              ->format(function ($item,$actions) {
                  $actions->addAction('edit',__('Edit'))
                          ->setURL('/modules/Library/library_manage_catalog_edit.php');
                  $actions->addAction('lending',__('Lending'))
                          ->setURL('/modules/Library/library_lending_item.php')
                          ->setIcon('search');
                  $actions->addAction('delete',__('Delete'))
                          ->setURL('/modules/Library/library_manage_catalog_delete.php');
                  $actions->addAction('duplicate',__('Duplicate'))
                          ->setURL('/modules/Library/library_manage_catalog_duplicate.php')
                          ->setIcon('copy');
              });
        echo $table->render($items);
    }
}
?>
