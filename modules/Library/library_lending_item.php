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

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Library/library_lending_item.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/library_lending.php'>".__($guid, 'Lending & Activity Log')."</a> > </div><div class='trailEnd'>".__($guid, 'View Item').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, array('success0' => 'Your request was completed successfully.'));
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
            $row = $result->fetch();

            $overdue = (strtotime(date('Y-m-d')) - strtotime($row['returnExpected'])) / (60 * 60 * 24);
            if ($overdue > 0 and $row['status'] == 'On Loan') {
                echo "<div class='error'>";
                echo sprintf(__($guid, 'This item is now %1$s%2$s days overdue'), '<u><b>', $overdue).'</b></u>.';
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
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Library/library_lending.php&name='.$name.'&gibbonLibraryTypeID='.$gibbonLibraryTypeID.'&gibbonSpaceID='.$gibbonSpaceID.'&status='.$status."'>".__($guid, 'Back to Search Results').'</a>';
                echo '</div>';
            }

            echo '<h3>';
            echo __($guid, 'Item Details');
            echo '</h3>';

            echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
            echo '<tr>';
            echo "<td style='width: 33%; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Type').'</span><br/>';
            echo '<i>'.__($guid, $row['type']).'</i>';
            echo '</td>';
            echo "<td style='width: 34%; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'ID').'</span><br/>';
            echo '<i>'.$row['id'].'</i>';
            echo '</td>';
            echo "<td style='width: 34%; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Name').'</span><br/>';
            echo '<i>'.$row['name'].'</i>';
            echo '</td>';
            echo '</tr>';
            echo '<tr>';
            echo "<td style='padding-top: 15px; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Author/Brand').'</span><br/>';
            echo '<i>'.$row['producer'].'</i>';
            echo '</td>';
            echo "<td style='padding-top: 15px; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Status').'</span><br/>';
            echo '<i>'.$row['status'].'</i>';
            echo '</td>';
            echo "<td style='padding-top: 15px; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Borrowable').'</span><br/>';
            echo '<i>'.$row['borrowable'].'</i>';
            echo '</td>';
            echo '</tr>';
            echo '</table>';

            echo '<h3>';
            echo __($guid, 'Lending & Activity Log');
            echo '</h3>';
            //Set pagination variable
            $page = 1;
            if (isset($_GET['page'])) {
                $page = $_GET['page'];
            }
            if ((!is_numeric($page)) or $page < 1) {
                $page = 1;
            }
            try {
                $dataEvent = array('gibbonLibraryItemID' => $gibbonLibraryItemID);
                $sqlEvent = 'SELECT * FROM gibbonLibraryItemEvent WHERE gibbonLibraryItemID=:gibbonLibraryItemID ORDER BY timestampOut DESC';
                $sqlEventPage = $sqlEvent.' LIMIT '.$_SESSION[$guid]['pagination'].' OFFSET '.(($page - 1) * $_SESSION[$guid]['pagination']);
                $resultEvent = $connection2->prepare($sqlEvent);
                $resultEvent->execute($dataEvent);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            echo "<div class='linkTop'>";
            if ($row['status'] == 'Available') {
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/library_lending_item_signout.php&gibbonLibraryItemID=$gibbonLibraryItemID&name=".$name.'&gibbonLibraryTypeID='.$gibbonLibraryTypeID.'&gibbonSpaceID='.$gibbonSpaceID.'&status='.$status."'>".__($guid, 'Sign Out')." <img  style='margin: 0 0 -4px 3px' title='".__($guid, 'Sign Out')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_right.png'/></a>";
            } else {
                echo '<i>'.__($guid, 'This item has already been signed out.').'</i>';
            }
            echo '</div>';

            if ($resultEvent->rowCount() < 1) {
                echo "<div class='error'>";
                echo __($guid, 'There are no records to display.');
                echo '</div>';
            } else {
                if ($resultEvent->rowCount() > $_SESSION[$guid]['pagination']) {
                    printPagination($guid, $resultEvent->rowCount(), $page, $_SESSION[$guid]['pagination'], 'top', '');
                }

                echo "<table cellspacing='0' style='width: 100%'>";
                echo "<tr class='head'>";
                echo "<th style='text-align: center; min-width: 90px'>";
                echo __($guid, 'User');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Status').'<br/>';
                echo "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Date Out & In').'</span><br/>';
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Due Date');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Return Action');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Recorded By');
                echo '</th>';
                echo "<th style='width: 110px'>";
                echo __($guid, 'Actions');
                echo '</th>';
                echo '</tr>';

                $count = 0;
                $rowNum = 'odd';
                try {
                    $resultEventPage = $connection2->prepare($sqlEventPage);
                    $resultEventPage->execute($dataEvent);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
                while ($rowEvent = $resultEventPage->fetch()) {
                    if ($count % 2 == 0) {
                        $rowNum = 'even';
                    } else {
                        $rowNum = 'odd';
                    }
                    ++$count;

					//COLOR ROW BY STATUS!
					echo "<tr class=$rowNum>";
                    if ($rowEvent['gibbonPersonIDStatusResponsible'] != '') {
                        try {
                            $dataPerson = array('gibbonPersonID' => $rowEvent['gibbonPersonIDStatusResponsible']);
                            $sqlPerson = 'SELECT title, preferredName, surname, image_240 FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID';
                            $resultPerson = $connection2->prepare($sqlPerson);
                            $resultPerson->execute($dataPerson);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }
                        if ($resultPerson->rowCount() == 1) {
                            $rowPerson = $resultPerson->fetch();
                        }
                    }
                    echo '<td style=\'text-align: center\'>';
                    if (is_array($rowPerson)) {
                        echo getUserPhoto($guid, $rowPerson['image_240'], 75);
                    }
                    if (is_array($rowPerson)) {
                        echo "<div style='margin-top: 3px; font-weight: bold'>".formatName($rowPerson['title'], $rowPerson['preferredName'], $rowPerson['surname'], 'Staff', false, true).'</div>';
                    }
                    echo '</td>';
                    echo '<td>';
                    echo $rowEvent['status'].'<br/>';
                    if ($rowEvent['timestampOut'] != '') {
                        echo "<span style='font-size: 85%; font-style: italic'>".dateConvertBack($guid, substr($rowEvent['timestampOut'], 0, 10));

                        if ($rowEvent['timestampReturn'] != '') {
                            echo ' - '.dateConvertBack($guid, substr($rowEvent['timestampReturn'], 0, 10));
                        }
                        echo '</span>';
                    }
                    echo '</td>';
                    echo '<td>';
                    if ($rowEvent['status'] != 'Returned' and $rowEvent['returnExpected'] != '') {
                        echo dateConvertBack($guid, substr($rowEvent['returnExpected'], 0, 10)).'<br/>';
                    }
                    echo '</td>';
                    echo '<td>';
                    if ($rowEvent['status'] != 'Returned' and  $rowEvent['returnAction'] != '') {
                        echo $rowEvent['returnAction'];
                    }
                    echo '</td>';
                    echo '<td>';
                    if ($rowEvent['gibbonPersonIDOut'] != '') {
                        try {
                            $dataPerson = array('gibbonPersonID' => $rowEvent['gibbonPersonIDOut']);
                            $sqlPerson = 'SELECT title, preferredName, surname, image_240 FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID';
                            $resultPerson = $connection2->prepare($sqlPerson);
                            $resultPerson->execute($dataPerson);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }
                        if ($resultPerson->rowCount() == 1) {
                            $rowPerson = $resultPerson->fetch();
                        }
                        echo __($guid, 'Out:').' '.formatName($rowPerson['title'], $rowPerson['preferredName'], $rowPerson['surname'], 'Staff', false, true).'<br/>';
                    }
                    if ($rowEvent['gibbonPersonIDIn'] != '') {
                        try {
                            $dataPerson = array('gibbonPersonID' => $rowEvent['gibbonPersonIDIn']);
                            $sqlPerson = 'SELECT title, preferredName, surname, image_240 FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID';
                            $resultPerson = $connection2->prepare($sqlPerson);
                            $resultPerson->execute($dataPerson);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }
                        if ($resultPerson->rowCount() == 1) {
                            $rowPerson = $resultPerson->fetch();
                        }
                        echo __($guid, 'In:').' '.formatName($rowPerson['title'], $rowPerson['preferredName'], $rowPerson['surname'], 'Staff', false, true);
                    }
                    echo '</td>';
                    echo '<td>';
                    if ($count == 1 and $rowEvent['status'] != 'Returned') {
                        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/library_lending_item_edit.php&gibbonLibraryItemID=$gibbonLibraryItemID&gibbonLibraryItemEventID=".$rowEvent['gibbonLibraryItemEventID'].'&name='.$name.'&gibbonLibraryTypeID='.$gibbonLibraryTypeID.'&gibbonSpaceID='.$gibbonSpaceID.'&status='.$status."'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/library_lending_item_return.php&gibbonLibraryItemID=$gibbonLibraryItemID&gibbonLibraryItemEventID=".$rowEvent['gibbonLibraryItemEventID'].'&name='.$name.'&gibbonLibraryTypeID='.$gibbonLibraryTypeID.'&gibbonSpaceID='.$gibbonSpaceID.'&status='.$status."'><img title='Return' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_left.png'/></a>";
                        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/library_lending_item_renew.php&gibbonLibraryItemID=$gibbonLibraryItemID&gibbonLibraryItemEventID=".$rowEvent['gibbonLibraryItemEventID'].'&name='.$name.'&gibbonLibraryTypeID='.$gibbonLibraryTypeID.'&gibbonSpaceID='.$gibbonSpaceID.'&status='.$status."'><img style='margin-left: 3px' title='Renew' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_right.png'/></a>";
                    }
                    echo '</td>';
                    echo '</tr>';
                }
                echo '</table>';

                if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
                    printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'bottom', '');
                }
            }

            $_SESSION[$guid]['sidebarExtra'] = '';
            $_SESSION[$guid]['sidebarExtra'] .= getImage($guid, $row['imageType'], $row['imageLocation']);
        }
    }
}
