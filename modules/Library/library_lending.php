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
use Gibbon\Tables\DataTable;
use Gibbon\Domain\Library\LibraryGateway;
use Gibbon\Services\Format;

$page->breadcrumbs->add(__('Lending & Activity Log'));

if (isActionAccessible($guid, $connection2, '/modules/Library/library_lending.php') == false) {
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

    $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/library_lending.php');

    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q', "/modules/".$_SESSION[$guid]['module']."/library_lending.php");

    $row = $form->addRow();
        $row->addLabel('name', __('ID/Name/Producer'));
        $row->addTextField('name')->setValue($name)->maxLength(50);

    $data = array();
    $sql = "SELECT gibbonLibraryTypeID AS value, name FROM gibbonLibraryType WHERE active='Y' ORDER BY name";
    $row = $form->addRow();
        $row->addLabel('gibbonLibraryTypeID', __('Type'));
        $row->addSelect('gibbonLibraryTypeID')->fromQuery($pdo, $sql, $data)->placeholder()->selected($gibbonLibraryTypeID);

    $row = $form->addRow();
        $row->addLabel('gibbonSpaceID', __('Space'));
        $row->addSelectSpace('gibbonSpaceID')->placeholder()->selected($gibbonSpaceID);

    $statuses = array(
        'Available' => __('Available'),
        'On Loan' => __('On Loan'),
        'Repair' => __('Repair'),
        'Reserved' => __('Reserved')
    );
    $row = $form->addRow();
        $row->addLabel('status', __('Status'));
        $row->addSelect('status')->fromArray($statuses)->selected($status)->placeholder();

    $row = $form->addRow();
        $row->addFooter();
        $row->addSearchSubmit($gibbon->session);

    echo $form->getOutput();

    $gateway = $container->get(LibraryGateway::class);
    $criteria = $gateway->newQueryCriteria(true)
                        ->filterBy('name',$name)
                        ->filterBy('gibbonLibraryTypeID',$gibbonLibraryTypeID)
                        ->filterBy('gibbonSpaceID',$gibbonSpaceID)
                        ->filterBy('status',$status)
                        ->fromPOST();
    $items = $gateway->queryLending($criteria);
    $table = DataTable::createPaginated('lending',$criteria);

    $table->addColumn('id',__('ID'));
    $table->addColumn('name',__('Name'))->format(function($item) {
      return sprintf('<b>%1$s</b><br/>%2$s',$item['name'],Format::small($item['producer']));
    });
    $table->addColumn('typeName',__('Type'));
    $table->addColumn('spaceName',__('Location'))
          ->format(function($item) {
            return sprintf('<b>%1$s</b><br/>%2$s',$item['spaceName'],Format::small($item['locationDetail']));
          });
    $table->addColumn('status',__('Status'))->format(function($item) {
      $statusDetail = "";
      if($item['returnExpected'] != null)
      {
        $statusDetail .= sprintf(
          '<br/>%1$s<br/>%2$s',
          Format::small($item['returnExpected']),
          Format::small(Format::name($item['title'],$item['preferredName'],$item['surname'],'Student',false,true))
        );
      }
      return sprintf(
        '<b>%1$s</b>%2$s',
        $item['status'],
        $statusDetail
      );
    });;
    $table->addActionColumn()
          ->addParam('gibbonLibraryItemID')
          ->addParam('name')
          ->addParam('gibbonLibraryTypeID')
          ->addParam('gibbonSpaceID')
          ->addParam('status')
          ->format(function($item,$actions) {
            $actions->addAction('edit',__('Edit'))
              ->setURL('/modules/Library/library_lending_item.php');
          });

    echo $table->render($items);
}
