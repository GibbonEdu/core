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

if (isActionAccessible($guid, $connection2, '/modules/Students/applicationForm_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Manage Applications').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $gibbonSchoolYearID = '';
    if (isset($_GET['gibbonSchoolYearID'])) {
        $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
    }
    if ($gibbonSchoolYearID == '' or $gibbonSchoolYearID == $_SESSION[$guid]['gibbonSchoolYearID']) {
        $gibbonSchoolYearID = $_SESSION[$guid]['gibbonSchoolYearID'];
        $gibbonSchoolYearName = $_SESSION[$guid]['gibbonSchoolYearName'];
    }

    if ($gibbonSchoolYearID != $_SESSION[$guid]['gibbonSchoolYearID']) {
        try {
            $data = array('gibbonSchoolYearID' => $_GET['gibbonSchoolYearID']);
            $sql = 'SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }
        if ($result->rowcount() != 1) {
            echo "<div class='error'>";
            echo __($guid, 'The specified record does not exist.');
            echo '</div>';
        } else {
            $row = $result->fetch();
            $gibbonSchoolYearID = $row['gibbonSchoolYearID'];
            $gibbonSchoolYearName = $row['name'];
        }
    }

    if ($gibbonSchoolYearID != '') {
        echo '<h2>';
        echo $gibbonSchoolYearName;
        echo '</h2>';

        echo "<div class='linkTop'>";
            //Print year picker
            if (getPreviousSchoolYearID($gibbonSchoolYearID, $connection2) != false) {
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/applicationForm_manage.php&gibbonSchoolYearID='.getPreviousSchoolYearID($gibbonSchoolYearID, $connection2)."'>".__($guid, 'Previous Year').'</a> ';
            } else {
                echo __($guid, 'Previous Year').' ';
            }
        echo ' | ';
        if (getNextSchoolYearID($gibbonSchoolYearID, $connection2) != false) {
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/applicationForm_manage.php&gibbonSchoolYearID='.getNextSchoolYearID($gibbonSchoolYearID, $connection2)."'>".__($guid, 'Next Year').'</a> ';
        } else {
            echo __($guid, 'Next Year').' ';
        }
        echo '</div>';

        //Set pagination variable
        $page = 1;
        if (isset($_GET['page'])) {
            $page = $_GET['page'];
        }
        if ((!is_numeric($page)) or $page < 1) {
            $page = 1;
        }

        $search = '';
        if (isset($_GET['search'])) {
            $search = $_GET['search'];
        }

        echo '<h4>';
        echo __($guid, 'Search');
        echo '</h2>';

        $form = Form::create('search', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');

        $form->setClass('noIntBorder fullWidth');
        $form->addHiddenValue('q', '/modules/'.$_SESSION[$guid]['module'].'/applicationForm_manage.php');
        $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        $row = $form->addRow();
            $row->addLabel('search', __('Search For'))->description(__('Application ID, preferred, surname, payment transaction ID'));
            $row->addTextField('search')->setValue($search);

        $row = $form->addRow();
            $row->addSearchSubmit($gibbon->session, __('Clear Search'), array('gibbonSchoolYearID'));

        echo $form->getOutput();

        echo '<h4>';
        echo __($guid, 'View');
        echo '</h2>';

        echo "<div class='linkTop'>";
        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/applicationForm_manage_add.php&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search'>".__($guid, 'Add')."<img style='margin-left: 5px' title='".__($guid, 'Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
        echo '</div>';

        try {
            $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
            $sql = 'SELECT * FROM gibbonApplicationForm LEFT JOIN gibbonYearGroup ON (gibbonApplicationForm.gibbonYearGroupIDEntry=gibbonYearGroup.gibbonYearGroupID) WHERE gibbonSchoolYearIDEntry=:gibbonSchoolYearID ORDER BY status, priority DESC, timestamp DESC';
            if ($search != '') {
                $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'search1' => "%$search%", 'search2' => "%$search%", 'search3' => "%$search%", 'search4' => "%$search%");
                $sql = "SELECT gibbonApplicationForm.*, gibbonYearGroup.*  FROM gibbonApplicationForm LEFT JOIN gibbonYearGroup ON (gibbonApplicationForm.gibbonYearGroupIDEntry=gibbonYearGroup.gibbonYearGroupID) LEFT JOIN gibbonPayment ON (gibbonApplicationForm.gibbonPaymentID=gibbonPayment.gibbonPaymentID AND foreignTable='gibbonApplicationForm') WHERE gibbonSchoolYearIDEntry=:gibbonSchoolYearID AND (preferredName LIKE :search1 OR surname LIKE :search2 OR gibbonApplicationFormID LIKE :search3 OR paymentTransactionID LIKE :search4) ORDER BY gibbonApplicationForm.status, priority DESC, gibbonApplicationForm.timestamp DESC";
            }
            $sqlPage = $sql.' LIMIT '.$_SESSION[$guid]['pagination'].' OFFSET '.(($page - 1) * $_SESSION[$guid]['pagination']);
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() < 1) {
            echo "<div class='error'>";
            echo 'There are no records display.';
            echo '</div>';
        } else {
            if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
                printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'top', "gibbonSchoolYearID=$gibbonSchoolYearID&search=$search");
            }

            echo "<table cellspacing='0' style='width: 100%'>";
            echo "<tr class='head'>";
            echo '<th>';
            echo __($guid, 'ID');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Student')."<br/><span style='font-style: italic; font-size: 85%'>".__($guid, 'Application Date').'</span>';
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Birth Year')."<br/><span style='font-style: italic; font-size: 85%'>".__($guid, 'Entry Year').'</span>';
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Parents');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Last School');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Status')."<br/><span style='font-style: italic; font-size: 85%'>".__($guid, 'Milestones').'</span>';
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Priority');
            echo '</th>';
            echo "<th style='width: 80px'>";
            echo __($guid, 'Actions');
            echo '</th>';
            echo '</tr>';

            $count = 0;
            $rowNum = 'odd';
            try {
                $resultPage = $connection2->prepare($sqlPage);
                $resultPage->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }
            while ($row = $resultPage->fetch()) {
                if ($count % 2 == 0) {
                    $rowNum = 'even';
                } else {
                    $rowNum = 'odd';
                }

                if ($row['status'] == 'Accepted') {
                    $rowNum = 'current';
                } elseif ($row['status'] == 'Rejected' or $row['status'] == 'Withdrawn') {
                    $rowNum = 'error';
                }

                ++$count;

                //COLOR ROW BY STATUS!
                echo "<tr class=$rowNum>";
                echo '<td>';
                echo ltrim($row['gibbonApplicationFormID'], '0');
                echo '</td>';
                echo '<td>';

                $data = array( 'gibbonApplicationFormID' => $row['gibbonApplicationFormID'] );
                $sql = "SELECT DISTINCT gibbonApplicationFormID, preferredName, surname, status FROM gibbonApplicationForm
                                JOIN gibbonApplicationFormLink ON (gibbonApplicationForm.gibbonApplicationFormID=gibbonApplicationFormLink.gibbonApplicationFormID1 OR gibbonApplicationForm.gibbonApplicationFormID=gibbonApplicationFormLink.gibbonApplicationFormID2)
                                WHERE gibbonApplicationFormID1=:gibbonApplicationFormID
                                OR gibbonApplicationFormID2=:gibbonApplicationFormID ORDER BY gibbonApplicationFormID";

                $resultLinked = $pdo->executeQuery($data, $sql);
                if ($resultLinked->rowCount() > 0) {
                    $names = '<br/>';
                    while ($rowLinked = $resultLinked->fetch()) {
                        $names .= '- '.formatName('', $rowLinked['preferredName'], $rowLinked['surname'], 'Student', true).' ('.$rowLinked['status'].')<br/>';
                    }
                    echo "<img title='" . __($guid, 'Sibling Applications') .$names. "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/attendance.png'/ style='float: right;   width:20px; height:20px;margin-left:4px;'>";
                }

                echo '<b>'.formatName('', $row['preferredName'], $row['surname'], 'Student', true).'</b><br/>';
                echo "<span style='font-style: italic; font-size: 85%'>".dateConvertBack($guid, substr($row['timestamp'], 0, 10)).'</span>';



                echo '</td>';
                echo '<td>';
                echo substr($row['dob'], 0, 4).'<br/>';
                echo "<span style='font-style: italic; font-size: 85%'>".__($guid, $row['name']).'</span>';
                echo '</td>';
                echo '<td>';
                if ($row['gibbonFamilyID'] != '') {
                    try {
                        $dataFamily2 = array('gibbonFamilyID' => $row['gibbonFamilyID']);
                        $sqlFamily2 = 'SELECT title, surname, preferredName, email FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonPerson.gibbonPersonID=gibbonFamilyAdult.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID ORDER BY contactPriority, surname, preferredName';
                        $resultFamily2 = $connection2->prepare($sqlFamily2);
                        $resultFamily2->execute($dataFamily2);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }
                    while ($rowFamily2 = $resultFamily2->fetch()) {
                        $name = formatName($rowFamily2['title'], $rowFamily2['preferredName'], $rowFamily2['surname'], 'Parent', true);
                        if ($rowFamily2['email'] != '') {
                            echo "<a href='mailto:".$rowFamily2['email']."'>".$name.'</a><br/>';
                        } else {
                            echo $name.'<br/>';
                        }
                    }
                } else {
                    $name = formatName($row['parent1title'], $row['parent1preferredName'], $row['parent1surname'], 'Parent', true);
                    if ($row['parent1email'] != '') {
                        echo "<a href='mailto:".$row['parent1email']."'>".$name.'</a><br/>';
                    } else {
                        echo $name.'<br/>';
                    }

                    if ($row['parent2surname'] != '' and $row['parent2preferredName'] != '') {
                        $name = formatName($row['parent2title'], $row['parent2preferredName'], $row['parent2surname'], 'Parent', true);
                        if ($row['parent2email'] != '') {
                            echo "<a href='mailto:".$row['parent2email']."'>".$name.'</a><br/>';
                        } else {
                            echo $name.'<br/>';
                        }
                    }
                }
                echo '</td>';
                echo '<td>';
                $school = '';
                if ($row['schoolDate1'] > $row['schoolDate2'] and $row['schoolName1'] != '') {
                    $school = $row['schoolName1'];
                } elseif ($row['schoolDate2'] > $row['schoolDate1'] and $row['schoolName2'] != '') {
                    $school = $row['schoolName2'];
                } elseif ($row['schoolName1'] != '') {
                    $school = $row['schoolName1'];
                }

                if ($school != '') {
                    if (strlen($school) <= 15) {
                        echo $school;
                    } else {
                        echo "<span title='".$school."'>".substr($school, 0, 15).'...</span>';
                    }
                }
                echo '</td>';
                echo '<td>';
                echo '<b>'.$row['status'].'</b>';
                if ($row['status'] == 'Pending') {
                    $milestones = explode(',', $row['milestones']);
                    foreach ($milestones as $milestone) {
                        echo "<br/><span style='font-style: italic; font-size: 85%'>".trim($milestone).'</span>';
                    }
                }
                echo '</td>';
                echo '<td>';
                echo $row['priority'];
                echo '</td>';
                echo '<td>';
                if ($row['status'] == 'Pending' or $row['status'] == 'Waiting List') {
                    echo "<a style='margin-left: 1px' href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/applicationForm_manage_accept.php&gibbonApplicationFormID='.$row['gibbonApplicationFormID']."&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search'><img title='".__($guid, 'Accept')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconTick.png'/></a>";
                    echo "<a style='margin-left: 5px' href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/applicationForm_manage_reject.php&gibbonApplicationFormID='.$row['gibbonApplicationFormID']."&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search'><img title='".__($guid, 'Reject')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconCross.png'/></a>";
                    echo '<br/>';
                }
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/applicationForm_manage_edit.php&gibbonApplicationFormID='.$row['gibbonApplicationFormID']."&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                if (isActionAccessible($guid, $connection2, '/modules/Students/applicationForm_manage_delete.php'))
                    echo " <a class='thickbox' href='".$_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/'.$_SESSION[$guid]['module'].'/applicationForm_manage_delete.php&gibbonApplicationFormID='.$row['gibbonApplicationFormID']."&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search&width=650&height=135'><img style='margin-left: 4px' title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a>";

                echo '</td>';
                echo '</tr>';
            }
            echo '</table>';

            if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
                printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'bottom', "gibbonSchoolYearID=$gibbonSchoolYearID&search=$search");
            }
        }
    }
}
?>
