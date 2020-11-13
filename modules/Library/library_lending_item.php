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

use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\Library\LibraryGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

$page->breadcrumbs
    ->add(__('Lending & Activity Log'), 'library_lending.php')
    ->add(__('View Item'));

if (isActionAccessible($guid, $connection2, '/modules/Library/library_lending_item.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return']);
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
            $row = $result->fetch();

            $overdue = (strtotime(date('Y-m-d')) - strtotime($row['returnExpected'])) / (60 * 60 * 24);
            if ($overdue > 0 and $row['status'] == 'On Loan') {
                echo "<div class='error'>";
                echo sprintf(__('This item is now %1$s%2$s days overdue'), '<u><b>', $overdue).'</b></u>.';
                echo '</div>';
            }

            $name = '';
            if (isset($_GET['name'])) {
                $name = $_GET['name'];
            }
            $gibbonLibraryTypeID = '';
            if (isset($_GET['gibbonLibraryTypeID'])) {
                $gibbonLibraryTypeID = $_GET['gibbonLibraryTypeID'];
            }
            $gibbonSpaceID = '';
            if (isset($_GET['gibbonSpaceID'])) {
                $gibbonSpaceID = $_GET['gibbonSpaceID'];
            }
            $status = '';
            if (isset($_GET['status'])) {
                $status = $_GET['status'];
            }

            if ($name != '' or $gibbonLibraryTypeID != '' or $gibbonSpaceID != '' or $status != '') {
                echo "<div class='linkTop'>";
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Library/library_lending.php&name='.$name.'&gibbonLibraryTypeID='.$gibbonLibraryTypeID.'&gibbonSpaceID='.$gibbonSpaceID.'&status='.$status."'>".__('Back to Search Results').'</a>';
                echo '</div>';
            }

            echo '<h3>';
            echo __('Item Details');
            echo '</h3>';

            echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
            echo '<tr>';
            echo "<td style='width: 33%; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__('Type').'</span><br/>';
            echo '<i>'.__($row['type']).'</i>';
            echo '</td>';
            echo "<td style='width: 34%; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__('ID').'</span><br/>';
            echo '<i>'.$row['id'].'</i>';
            echo '</td>';
            echo "<td style='width: 34%; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__('Name').'</span><br/>';
            echo '<i>'.$row['name'].'</i>';
            echo '</td>';
            echo '</tr>';
            echo '<tr>';
            echo "<td style='padding-top: 15px; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__('Author/Brand').'</span><br/>';
            echo '<i>'.$row['producer'].'</i>';
            echo '</td>';
            echo "<td style='padding-top: 15px; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__('Status').'</span><br/>';
            echo '<i>'.__($row['status']).'</i>';
            echo '</td>';
            echo "<td style='padding-top: 15px; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__('Borrowable').'</span><br/>';
            echo '<i>'.Format::yesNo($row['borrowable']).'</i>';
            echo '</td>';
            echo '</tr>';
            echo '</table>';

            
            $gateway = $container->get(LibraryGateway::class);
            $criteria = $gateway->newQueryCriteria(true)
                ->sortBy('gibbonLibraryItemEvent.timestampOut', 'DESC')
                ->filterBy('gibbonLibraryItemID', $gibbonLibraryItemID)
                ->filterBy('name', $name)
                ->filterBy('gibbonLibraryTypeID', $gibbonLibraryTypeID)
                ->filterBy('gibbonSpaceID', $gibbonSpaceID)
                ->filterBy('status', $status)
                ->fromPOST();

            $item = $gateway->queryLendingDetail($criteria); 
            $table = DataTable::createPaginated('lendingLog', $criteria);
            $table->setTitle(__('Lending & Activity Log'));

            $table->modifyRows(function ($item, $row) {
                if ($item['status'] == 'On Loan') {
                    return $item['pastDue'] == 'Y' ? $row->addClass('error') : $row->addClass('warning');
                }
                return $row;
            });

            if ($row['status'] == 'Available') {
              $table
                ->addHeaderAction('signout', __('Sign Out'))
                ->setURL('/modules/Library/library_lending_item_signout.php')
                ->setIcon('page_right')
                ->addParam('gibbonLibraryItemID', $gibbonLibraryItemID)
                ->addParam('name', $name)
                ->addParam('gibbonLibraryTypeID', $gibbonLibraryTypeID)
                ->addParam('gibbonSpaceID', $gibbonSpaceID)
                ->addParam('status', $status)
                ->displayLabel();
            } else {
                $table->addHeaderAction('signout', __('This item has already been signed out.'));
            }

            $table
              ->addColumn('user', __('User'))
              ->sortable(['responsiblePersonSurname', 'responsiblePersonPreferredName'])
              ->format(function ($item) {
                if ($item['gibbonPersonIDStatusResponsible'] != '') {
                  return sprintf('%1$s<div style="margin-top: 3px; font-weight: bold">%2$s</div>', Format::userPhoto($item['responsiblePersonImage']), Format::name($item['responsiblePersonTitle'], $item['responsiblePersonPreferredName'], $item['responsiblePersonSurname'], 'Staff', false, true));
                } else {
                  return null;
                }
              });
            $table->addColumn('status', __('Status'))
                  ->description(__('Date Out & In'))
                  ->format(function ($event) {
                    $timeInOut = Format::date($event['timestampOut']);
                    if ($event['timestampReturn'] != '') {
                      $timeInOut .= ' - ' . Format::date($event['timestampReturn']);
                    }
                    return sprintf('%1$s<br/>%2$s', __($event['status']), Format::small($timeInOut));
                  });
            $table
              ->addColumn('returnExpected', __('Due Date'))
              ->format(function ($event) {
                if ($event['status'] != 'Returned' && $event['returnExpected'] != '') {
                  return Format::date($event['returnExpected']);
                }
              });
            $table
              ->addColumn('returnAction', __('Return Action'))
              ->format(function ($event) {
                if ($event['status'] != 'Returned' && $event['returnAction'] != ''){
                  return __($event['returnAction']);
                }
              });
            $table
              ->addColumn('outPersonID', __('Recorded By'))
              ->format(function ($event) {
                $outPerson = "";
                $inPerson = "";
                if ($event['outPersonID']) {
                    $outPerson .= __('Out:'). ' ' . Format::name($event['outPersonTitle'], $event['outPersonPreferredName'], $event['outPersonSurname'], 'Staff', false, true);
                }
                if ($event['inPersonID']) {
                    $inPerson .= __('In:'). ' ' . Format::name($event['inPersonTitle'], $event['inPersonPreferredName'], $event['inPersonSurname'], 'Staff', false, true);
                }
                return sprintf('%1$s<br/>%2$s', $outPerson, $inPerson);
              });

            $table
              ->addActionColumn()
              ->addParam('gibbonLibraryItemID')
              ->addParam('gibbonLibraryItemEventID')
              ->addParam('name', $name)
              ->addParam('gibbonLibraryTypeID', $gibbonLibraryTypeID)
              ->addParam('gibbonSpaceID', $gibbonSpaceID)
              ->addParam('status', $status)
              ->format(function ($event, $actions) {
                if ($event['status'] != 'Returned') {
                  //Edit function cannot be used unless the responsible person ID is set
                  if ($event['responsiblePersonID'] != null) {
                    $actions
                      ->addAction('edit', __('Edit'))
                      ->setURL('/modules/Library/library_lending_item_edit.php');
                  }

                  $actions
                    ->addAction('return', __('Return'))
                    ->setIcon('page_left')
                    ->setURL('/modules/Library/library_lending_item_return.php');

                  //Renew feature is only usable when the responsible person ID is set
                  if ($event['responsiblePersonID'] != null) {
                    $actions
                      ->addAction('renew', __('Renew'))
                      ->setIcon('page_right')
                      ->setURL('/modules/Library/library_lending_item_renew.php');
                  }
                }
              });
            echo $table->render($item);

            $_SESSION[$guid]['sidebarExtra'] = '';
            $_SESSION[$guid]['sidebarExtra'] .= getImage($guid, $row['imageType'], $row['imageLocation']);
        }
    }
}
