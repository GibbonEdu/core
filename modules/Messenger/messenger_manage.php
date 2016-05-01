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

@session_start();

if (isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __($guid, 'The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        //Proceed!
        echo "<div class='trail'>";
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Manage Messages').'</div>';
        echo '</div>';

        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, null);
        }

        echo '<h2>';
        echo __($guid, 'Search');
        echo '</h2>';

        $search = null;
        if (isset($_GET['search'])) {
            $search = $_GET['search'];
        }

        ?>
		<form method="get" action="<?php echo $_SESSION[$guid]['absoluteURL']?>/index.php">
			<table class='noIntBorder' cellspacing='0' style="width: 100%">	
				<tr><td style="width: 30%"></td><td></td></tr>
				<tr>
					<td> 
						<b><?php echo __($guid, 'Search In') ?></b><br/>
						<span class="emphasis small"><?php echo __($guid, 'Subject, body.') ?></span>
					</td>
					<td class="right">
						<input name="search" id="search" maxlength=20 value="<?php echo $search ?>" type="text" class="standardWidth">
					</td>
				</tr>
				<tr>
					<td colspan=2 class="right">
						<input type="hidden" name="q" value="/modules/<?php echo $_SESSION[$guid]['module'] ?>/messenger_manage.php">
						<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
						<?php
                        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/messenger_manage.php'>".__($guid, 'Clear Search').'</a>';
        ?>
						<input type="submit" value="<?php echo __($guid, 'Submit');
        ?>">
					</td>
				</tr>
			</table>
		</form>
		<?php

        echo '<h2>';
        echo __($guid, 'Messages');
        echo '</h2>';

        //Set pagination variable
        $page = 1;
        if (isset($_GET['page'])) {
            $page = $_GET['page'];
        }
        if ((!is_numeric($page)) or $page < 1) {
            $page = 1;
        }

        try {
            if ($highestAction == 'Manage Messages_all') {
                if ($search == '') {
                    $data = array();
                    $sql = 'SELECT gibbonMessenger.*, title, surname, preferredName, category FROM gibbonMessenger JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) ORDER BY timestamp DESC';
                } else {
                    $data = array('search1' => "%$search%", 'search2' => "%$search%");
                    $sql = 'SELECT gibbonMessenger.*, title, surname, preferredName, category FROM gibbonMessenger JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) WHERE (subject LIKE :search1 OR body LIKE :search2) ORDER BY timestamp DESC';
                }
            } else {
                if ($search == '') {
                    $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                    $sql = 'SELECT gibbonMessenger.*, title, surname, preferredName, category FROM gibbonMessenger JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) WHERE gibbonMessenger.gibbonPersonID=:gibbonPersonID ORDER BY timestamp DESC';
                } else {
                    $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'search1' => "%$search%", 'search2' => "%$search%");
                    $sql = 'SELECT gibbonMessenger.*, title, surname, preferredName, category FROM gibbonMessenger JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) WHERE gibbonMessenger.gibbonPersonID=:gibbonPersonID AND (subject LIKE :search1 OR body LIKE :search2) ORDER BY timestamp DESC';
                }
            }
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        $sqlPage = $sql.' LIMIT '.$_SESSION[$guid]['pagination'].' OFFSET '.(($page - 1) * $_SESSION[$guid]['pagination']);

        if (isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_post.php') == true or isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_postQuickWall.php') == true) {
            echo "<div class='linkTop'>";
            if (isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_post.php') == true) {
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/messenger_post.php'>".__($guid, 'New Message')."<img style='margin-left: 5px' title='".__($guid, 'New Message')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
            }
            if (isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_postQuickWall.php') == true) {
                if (isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_post.php') == true) {
                    echo ' | ';
                }
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/messenger_postQuickWall.php'>".__($guid, 'New Quick Wall Message')."<img style='margin-left: 5px' title='".__($guid, 'New Quick Wall Message')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
            }
            echo '</div>';
        }

        if ($result->rowCount() < 1) {
            echo "<div class='error'>";
            echo __($guid, 'There are no records to display.');
            echo '</div>';
        } else {
            if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
                printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'top');
            }

            echo "<table cellspacing='0' style='width: 100%'>";
            echo "<tr class='head'>";
            echo '<th>';
            echo __($guid, 'Subject');
            echo '</th>';
            echo "<th style='width: 100px'>";
            echo __($guid, 'Date Sent').'<br/>';
            echo "<span style='font-style: italic; font-size: 85%'>".__($guid, 'Dates Published').'</span>';
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Author');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Recipients');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Email');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Wall');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'SMS');
            echo '</th>';
            echo "<th style='width: 120px'>";
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

                    //COLOR ROW BY STATUS!
                    echo "<tr class=$rowNum>";
                echo '<td>';
                echo '<b>'.$row['subject'].'</b><br/>';
                echo '</td>';
                echo '<td>';
                echo dateConvertBack($guid, substr($row['timestamp'], 0, 10)).'<br/>';
                if ($row['messageWall'] == 'Y') {
                    echo "<span style='font-style: italic; font-size: 85%'>";
                    if ($row['messageWall_date1'] != '') {
                        echo dateConvertBack($guid, $row['messageWall_date1']).'<br/>';
                    }
                    if ($row['messageWall_date2'] != '') {
                        echo dateConvertBack($guid, $row['messageWall_date2']).'<br/>';
                    }
                    if ($row['messageWall_date3'] != '') {
                        echo dateConvertBack($guid, $row['messageWall_date3']).'<br/>';
                    }
                    echo '</span>';
                }
                echo '</td>';
                echo '<td>';
                echo formatName($row['title'], $row['preferredName'], $row['surname'], $row['category']);
                echo '</td>';
                echo '<td>';
                try {
                    $dataTargets = array('gibbonMessengerID' => $row['gibbonMessengerID']);
                    $sqlTargets = 'SELECT type, id FROM gibbonMessengerTarget WHERE gibbonMessengerID=:gibbonMessengerID ORDER BY type, id';
                    $resultTargets = $connection2->prepare($sqlTargets);
                    $resultTargets->execute($dataTargets);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
                $targets = '';
                while ($rowTargets = $resultTargets->fetch()) {
                    if ($rowTargets['type'] == 'Activity') {
                        try {
                            $dataTarget = array('gibbonActivityID' => $rowTargets['id']);
                            $sqlTarget = 'SELECT name FROM gibbonActivity WHERE gibbonActivityID=:gibbonActivityID';
                            $resultTarget = $connection2->prepare($sqlTarget);
                            $resultTarget->execute($dataTarget);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }
                        if ($resultTarget->rowCount() == 1) {
                            $rowTarget = $resultTarget->fetch();
                            $targets .= '<b>'.__($guid, $rowTargets['type']).'</b> - '.$rowTarget['name'].'<br/>';
                        }
                    } elseif ($rowTargets['type'] == 'Class') {
                        try {
                            $dataTarget = array('gibbonCourseClassID' => $rowTargets['id']);
                            $sqlTarget = 'SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassID=:gibbonCourseClassID';
                            $resultTarget = $connection2->prepare($sqlTarget);
                            $resultTarget->execute($dataTarget);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }
                        if ($resultTarget->rowCount() == 1) {
                            $rowTarget = $resultTarget->fetch();
                            $targets .= '<b>'.__($guid, $rowTargets['type']).'</b> - '.$rowTarget['course'].'.'.$rowTarget['class'].'<br/>';
                        }
                    } elseif ($rowTargets['type'] == 'Course') {
                        try {
                            $dataTarget = array('gibbonCourseID' => $rowTargets['id']);
                            $sqlTarget = 'SELECT name FROM gibbonCourse WHERE gibbonCourseID=:gibbonCourseID';
                            $resultTarget = $connection2->prepare($sqlTarget);
                            $resultTarget->execute($dataTarget);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }
                        if ($resultTarget->rowCount() == 1) {
                            $rowTarget = $resultTarget->fetch();
                            $targets .= '<b>'.__($guid, $rowTargets['type']).'</b> - '.$rowTarget['name'].'<br/>';
                        }
                    } elseif ($rowTargets['type'] == 'Role') {
                        try {
                            $dataTarget = array('gibbonRoleID' => $rowTargets['id']);
                            $sqlTarget = 'SELECT name FROM gibbonRole WHERE gibbonRoleID=:gibbonRoleID';
                            $resultTarget = $connection2->prepare($sqlTarget);
                            $resultTarget->execute($dataTarget);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }
                        if ($resultTarget->rowCount() == 1) {
                            $rowTarget = $resultTarget->fetch();
                            $targets .= '<b>'.__($guid, $rowTargets['type']).'</b> - '.__($guid, $rowTarget['name']).'<br/>';
                        }
                    } elseif ($rowTargets['type'] == 'Role Category') {
                        $targets .= '<b>'.__($guid, $rowTargets['type']).'</b> - '.__($guid, $rowTargets['id']).'<br/>';
                    } elseif ($rowTargets['type'] == 'Roll Group') {
                        try {
                            $dataTarget = array('gibbonRollGroupID' => $rowTargets['id']);
                            $sqlTarget = 'SELECT name FROM gibbonRollGroup WHERE gibbonRollGroupID=:gibbonRollGroupID';
                            $resultTarget = $connection2->prepare($sqlTarget);
                            $resultTarget->execute($dataTarget);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }
                        if ($resultTarget->rowCount() == 1) {
                            $rowTarget = $resultTarget->fetch();
                            $targets .= '<b>'.__($guid, $rowTargets['type']).'</b> - '.$rowTarget['name'].'<br/>';
                        }
                    } elseif ($rowTargets['type'] == 'Year Group') {
                        try {
                            $dataTarget = array('gibbonYearGroupID' => $rowTargets['id']);
                            $sqlTarget = 'SELECT name FROM gibbonYearGroup WHERE gibbonYearGroupID=:gibbonYearGroupID';
                            $resultTarget = $connection2->prepare($sqlTarget);
                            $resultTarget->execute($dataTarget);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }
                        if ($resultTarget->rowCount() == 1) {
                            $rowTarget = $resultTarget->fetch();
                            $targets .= '<b>'.__($guid, $rowTargets['type']).'</b> - '.__($guid, $rowTarget['name']).'<br/>';
                        }
                    } elseif ($rowTargets['type'] == 'Applicants') {
                        try {
                            $dataTarget = array('gibbonSchoolYearID' => $rowTargets['id']);
                            $sqlTarget = 'SELECT name FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID';
                            $resultTarget = $connection2->prepare($sqlTarget);
                            $resultTarget->execute($dataTarget);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }
                        if ($resultTarget->rowCount() == 1) {
                            $rowTarget = $resultTarget->fetch();
                            $targets .= '<b>'.__($guid, $rowTargets['type']).'</b> - '.$rowTarget['name'].'<br/>';
                        }
                    } elseif ($rowTargets['type'] == 'Houses') {
                        try {
                            $dataTarget = array('gibbonHouseID' => $rowTargets['id']);
                            $sqlTarget = 'SELECT name FROM gibbonHouse WHERE gibbonHouseID=:gibbonHouseID';
                            $resultTarget = $connection2->prepare($sqlTarget);
                            $resultTarget->execute($dataTarget);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }
                        if ($resultTarget->rowCount() == 1) {
                            $rowTarget = $resultTarget->fetch();
                            $targets .= '<b>'.__($guid, $rowTargets['type']).'</b> - '.$rowTarget['name'].'<br/>';
                        }
                    } elseif ($rowTargets['type'] == 'Individuals') {
                        try {
                            $dataTarget = array('gibbonPersonID' => $rowTargets['id']);
                            $sqlTarget = 'SELECT preferredName, surname FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID';
                            $resultTarget = $connection2->prepare($sqlTarget);
                            $resultTarget->execute($dataTarget);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }
                        if ($resultTarget->rowCount() == 1) {
                            $rowTarget = $resultTarget->fetch();
                            $targets .= '<b>'.__($guid, $rowTargets['type']).'</b> - '.formatName('', $rowTarget['preferredName'], $rowTarget['surname'], 'Student', true).'<br/>';
                        }
                    }
                }
                echo $targets;
                echo '</td>';
                echo '<td>';
                if ($row['email'] == 'Y') {
                    echo "<img title='".__($guid, 'Sent by email.')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconTick.png'/> ";
                } else {
                    echo "<img title='".__($guid, 'Not sent by email.')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconCross.png'/> ";
                }
                echo '</td>';
                echo '<td>';
                if ($row['messageWall'] == 'Y') {
                    echo "<img title='".__($guid, 'Sent by message wall.')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconTick.png'/> ";
                } else {
                    echo "<img title='".__($guid, 'Not sent by message wall.')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconCross.png'/> ";
                }
                echo '</td>';
                echo '<td>';
                if ($row['sms'] == 'Y') {
                    echo "<img title='".__($guid, 'Sent by sms.')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconTick.png'/> ";
                } else {
                    echo "<img title='".__($guid, 'Not sent by sms.')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconCross.png'/> ";
                }
                echo '</td>';
                echo '<td>';
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/messenger_manage_edit.php&gibbonMessengerID='.$row['gibbonMessengerID']."&sidebar=true&search=$search'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/messenger_manage_delete.php&gibbonMessengerID='.$row['gibbonMessengerID']."&sidebar=true&search=$search'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a> ";
                echo "<script type='text/javascript'>";
                echo '$(document).ready(function(){';
                echo "\$(\".comment-$count\").hide();";
                echo "\$(\".show_hide-$count\").fadeIn(1000);";
                echo "\$(\".show_hide-$count\").click(function(){";
                echo "\$(\".comment-$count\").fadeToggle(1000);";
                echo '});';
                echo '});';
                echo '</script>';
                if ($row['smsReport'] != '' or $row['emailReport'] != '') {
                    echo "<a title='".__($guid, 'View Send Report')."' class='show_hide-$count' onclick='false' href='#'><img style='padding-right: 5px' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/page_down.png' alt='".__($guid, 'Show Comment')."' onclick='return false;' /></a>";
                }
                echo '</td>';
                echo '</tr>';
                if ($row['smsReport'] != '' or $row['emailReport'] != '') {
                    echo "<tr class='comment-$count' id='comment-$count'>";
                    echo "<td style='background-color: #fff' colspan=8>";
                    if ($row['emailReport'] != '') {
                        echo '<b><u>Email Report</u></b><br/>';
                        $emails = explode(',', $row['emailReport']);
                        $emails = array_unique($emails);
                        $emails = msort($emails);
                        foreach ($emails as $email) {
                            echo $email.'<br/>';
                        }
                    }
                    if ($row['smsReport'] != '') {
                        echo '<b><u>SMS Report</u></b><br/>';
                        $smss = explode(',', $row['smsReport']);
                        $smss = array_unique($smss);
                        $smss = msort($smss);
                        foreach ($smss as $sms) {
                            echo $sms.'<br/>';
                        }
                    }
                    echo '</td>';
                    echo '</tr>';
                }

                ++$count;
            }
            echo '</table>';

            if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
                printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'bottom');
            }
        }
    }
}
?>