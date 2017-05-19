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

if (isActionAccessible($guid, $connection2, '/modules/Students/student_view.php') == false) {
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
        if ($highestAction == 'View Student Profile_myChildren' || $highestAction == 'View Student Profile_my') {
            echo "<div class='trail'>";
            echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'View Student Profiles').'</div>';
            echo '</div>';

             $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);

            if ($highestAction == 'View Student Profile_myChildren') {
                // Get child list (and test permission to access)
                $sql = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup
                    FROM gibbonFamilyAdult
                    JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamilyAdult.gibbonFamilyID)
                    JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID)
                    JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                    JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID)
                    JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID)
                    WHERE gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID
                    AND gibbonPerson.status='Full' AND gibbonFamilyAdult.childDataAccess='Y'
                    AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."')
                    AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
                    GROUP BY gibbonPerson.gibbonPersonID
                    ORDER BY surname, preferredName";
            } else if ($highestAction == 'View Student Profile_my') {
                // Get self
                $sql = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup
                    FROM gibbonPerson
                    JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                    JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID)
                    JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID)
                    WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID AND gibbonPerson.status='Full'
                    AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."')
                    AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID";
            }

            try {
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($result->rowCount() < 1) {
                echo "<div class='error'>";
                echo __($guid, 'You do not have access to this action.');
                echo '</div>';
            } else {
                echo "<table class='colorOddEven' cellspacing='0' style='width: 100%'>";
                echo "<tr class='head'>";
                echo '<th>';
                echo __($guid, 'Name');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Year Group');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Roll Group');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Actions');
                echo '</th>';
                echo '</tr>';

                while ($row = $result->fetch()) {
                    echo "<tr>";
                    echo '<td>';
                    echo formatName('', $row['preferredName'], $row['surname'], 'Student', true);
                    echo '</td>';
                    echo '<td>';
                    echo __($guid, $row['yearGroup']);
                    echo '</td>';
                    echo '<td>';
                    echo $row['rollGroup'];
                    echo '</td>';
                    echo '<td>';
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/student_view_details.php&gibbonPersonID='.$row['gibbonPersonID']."'><img title='".__($guid, 'View Details')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/plus.png'/></a> ";
                    echo '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            }
        }
        if ($highestAction == 'View Student Profile_brief') {
            //Proceed!
            echo "<div class='trail'>";
            echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'View Student Profiles').'</div>';
            echo '</div>';

            echo '<h2>';
            echo __($guid, 'Filter');
            echo '</h2>';

            $gibbonPersonID = null;
            if (isset($_GET['gibbonPersonID'])) {
                $gibbonPersonID = $_GET['gibbonPersonID'];
            }
            $search = null;
            if (isset($_GET['search'])) {
                $search = $_GET['search'];
            }
            $sort = 'surname, preferredName';
            if (isset($_GET['sort'])) {
                $sort = $_GET['sort'];
            }

            ?>
			<form method="get" action="<?php echo $_SESSION[$guid]['absoluteURL']?>/index.php">
				<table class='noIntBorder' cellspacing='0' style="width: 100%">
					<tr><td style="width: 30%"></td><td></td></tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Search For') ?></b><br/>
							<?php
                                echo '<span style="font-size: 90%"><i>'.__($guid, 'Preferred, surname, username.').'</span>'; ?>
						</td>
						<td class="right">
							<input name="search" id="search" maxlength=50 value="<?php echo $search ?>" type="text" class="standardWidth">
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Sort By') ?></b><br/>
						</td>
						<td class="right">
							<select name="sort" class="standardWidth">
								<option value="surname, preferredName" <?php if ($sort == 'surname, preferredName') { echo 'selected'; } ?>><?php echo __($guid, 'Surname'); ?></option>
								<option value="preferredName" <?php if ($sort == 'preferredName') { echo 'selected'; } ?>><?php echo __($guid, 'Given Name'); ?></option>
								<option value="rollGroup" <?php if ($sort == 'rollGroup') { echo 'selected'; } ?>><?php echo __($guid, 'Roll Group'); ?></option>
								<option value="yearGroup" <?php if ($sort == 'yearGroup') { echo 'selected'; } ?>><?php echo __($guid, 'Year Group'); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td colspan=2 class="right">
							<input type="hidden" name="q" value="/modules/<?php echo $_SESSION[$guid]['module'] ?>/student_view.php">
							<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
							<?php
                                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/student_view.php'>".__($guid, 'Clear Search').'</a>'; ?>
							<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
						</td>
					</tr>
				</table>
			</form>

			<h2>
				<?php echo __($guid, 'Choose A Student'); ?>
			</h2>

			<?php
            //Set pagination variable
            $page = 1;
            if (isset($_GET['page'])) {
                $page = $_GET['page'];
            }
            if ((!is_numeric($page)) or $page < 1) {
                $page = 1;
            }

            try {
                $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                $sql = "SELECT DISTINCT gibbonPerson.gibbonPersonID, status, gibbonStudentEnrolmentID, surname, preferredName, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonPerson.status='Full'";
                $searchSql = '';
                if ($search != '') {
                    $data += array('search1' => "%$search%", 'search2' => "%$search%", 'search3' => "%$search%");
                    $searchSql = ' AND (preferredName LIKE :search1 OR surname LIKE :search2 OR username LIKE :search3)';
                }

                if ($sort != 'surname, preferredName' && $sort != 'preferredName' && $sort != 'rollGroup' && $sort != 'yearGroup') {
                    $sort = 'surname, preferredName';
                }

                $sql = $sql.$searchSql.' ORDER BY '.$sort;
                $sqlPage = $sql.' LIMIT '.$_SESSION[$guid]['pagination'].' OFFSET '.(($page - 1) * $_SESSION[$guid]['pagination']);
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($result->rowcount() < 1) {
                echo "<div class='error'>";
                echo __($guid, 'There are no records to display.');
                echo '</div>';
            } else {
                if ($result->rowcount() > $_SESSION[$guid]['pagination']) {
                    printPagination($guid, $result->rowcount(), $page, $_SESSION[$guid]['pagination'], 'top', "&search=$search&sort=$sort");
                }

                echo "<table cellspacing='0' style='width: 100%'>";
                echo "<tr class='head'>";
                echo '<th>';
                echo __($guid, 'Name');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Year Group');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Roll Group');
                echo '</th>';
                echo '<th>';
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
                    if ($row['status'] != 'Full') {
                        $rowNum = 'error';
                    }
                    ++$count;

                    //COLOR ROW BY STATUS!
                    echo "<tr class=$rowNum>";
                    echo '<td>';
                    echo formatName('', $row['preferredName'], $row['surname'], 'Student', true);
                    echo '</td>';
                    echo '<td>';
                    if ($row['yearGroup'] != '') {
                        echo __($guid, $row['yearGroup']);
                    }
                    echo '</td>';
                    echo '<td>';
                    echo $row['rollGroup'];
                    echo '</td>';
                    echo '<td>';
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/student_view_details.php&gibbonPersonID='.$row['gibbonPersonID']."&search=$search&sort=$sort'><img title='".__($guid, 'View Details')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/plus.png'/></a> ";
                    echo '</td>';
                    echo '</tr>';
                }
                echo '</table>';

                if ($result->rowcount() > $_SESSION[$guid]['pagination']) {
                    printPagination($guid, $result->rowcount(), $page, $_SESSION[$guid]['pagination'], 'bottom', "search=$search&sort=$sort");
                }
            }
        }
        if ($highestAction == 'View Student Profile_full' or $highestAction == 'View Student Profile_fullNoNotes') {
            //Proceed!
            echo "<div class='trail'>";
            echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'View Student Profiles').'</div>';
            echo '</div>';

            echo '<h2>';
            echo __($guid, 'Filter');
            echo '</h2>';

            $gibbonPersonID = null;
            if (isset($_GET['gibbonPersonID'])) {
                $gibbonPersonID = $_GET['gibbonPersonID'];
            }
            $search = null;
            if (isset($_GET['search'])) {
                $search = $_GET['search'];
            }
            $allStudents = '';
            if (isset($_GET['allStudents'])) {
                $allStudents = $_GET['allStudents'];
            }
            $sort = 'surname, preferredName';
            if (isset($_GET['sort'])) {
                $sort = $_GET['sort'];
            }

            ?>
			<form method="get" action="<?php echo $_SESSION[$guid]['absoluteURL']?>/index.php">
				<table class='noIntBorder' cellspacing='0' style="width: 100%">
					<tr><td style="width: 30%"></td><td></td></tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Search For') ?></b><br/>
							<?php
                                echo '<span style="font-size: 90%"><i>'.__($guid, 'Preferred, surname, username, student ID, email, phone number, vehicle registration, parent email.').'</span>'; ?>
						</td>
						<td class="right">
							<input name="search" id="search" maxlength=50 value="<?php echo $search ?>" type="text" class="standardWidth">
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Sort By') ?></b><br/>
						</td>
						<td class="right">
							<select name="sort" class="standardWidth">
								<option value="surname, preferredName" <?php if ($sort == 'surname, preferredName') { echo 'selected'; } ?>><?php echo __($guid, 'Surname'); ?></option>
								<option value="preferredName" <?php if ($sort == 'preferredName') { echo 'selected'; } ?>><?php echo __($guid, 'Given Name'); ?></option>
								<option value="rollGroup" <?php if ($sort == 'rollGroup') { echo 'selected'; } ?>><?php echo __($guid, 'Roll Group'); ?></option>
								<option value="yearGroup" <?php if ($sort == 'yearGroup') { echo 'selected'; } ?>><?php echo __($guid, 'Year Group'); ?></option>
							</select>
						</td>
					</tr>

					<tr>
						<td>
							<b><?php echo __($guid, 'All Students') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Include all students, regardless of status and current enrolment. Some data may not display.') ?></span>
						</td>
						<td class="right">
							<?php
                                $checked = '';
								if ($allStudents == 'on') {
									$checked = 'checked';
								}
								echo "<input $checked name=\"allStudents\" id=\"allStudents\" type=\"checkbox\">"; ?>
						</td>
					</tr>
					<tr>
						<td colspan=2 class="right">
							<input type="hidden" name="q" value="/modules/<?php echo $_SESSION[$guid]['module'] ?>/student_view.php">
							<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
							<?php
                                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/student_view.php'>".__($guid, 'Clear Search').'</a>'; ?>
							<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
						</td>
					</tr>
				</table>
			</form>

			<h2>
				<?php echo __($guid, 'Choose A Student'); ?>
			</h2>

			<?php
            //Set pagination variable
            $page = 1;
            if (isset($_GET['page'])) {
                $page = $_GET['page'];
            }
            if ((!is_numeric($page)) or $page < 1) {
                $page = 1;
            }

            try {
                $data = array();

                $searchSql = '';
                $familySQL = '';

                if (!empty($search)) {
                    $familySQL = "LEFT JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID)
                            LEFT JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID)
                            LEFT JOIN gibbonFamilyAdult AS parent1Fam ON (parent1Fam.gibbonFamilyID=gibbonFamily.gibbonFamilyID AND parent1Fam.contactPriority=1)
                            LEFT JOIN gibbonPerson AS parent1 ON (parent1Fam.gibbonPersonID=parent1.gibbonPersonID AND parent1.status='Full' AND parent1.email LIKE :search)
                            LEFT JOIN gibbonFamilyAdult AS parent2Fam ON (parent2Fam.gibbonFamilyID=gibbonFamily.gibbonFamilyID AND parent2Fam.contactPriority=2)
                            LEFT JOIN gibbonPerson AS parent2 ON (parent2Fam.gibbonPersonID=parent2.gibbonPersonID AND parent2.status='Full' AND parent2.email LIKE :search)";

                    $data['search'] = "%$search%";
                    $searchSql = " AND (
                        gibbonPerson.preferredName LIKE :search OR gibbonPerson.surname LIKE :search OR gibbonPerson.username LIKE :search OR gibbonPerson.email LIKE :search OR gibbonPerson.emailAlternate LIKE :search OR gibbonPerson.studentID LIKE :search OR gibbonPerson.phone1 LIKE :search OR gibbonPerson.phone2 LIKE :search OR gibbonPerson.phone3 LIKE :search OR gibbonPerson.phone4 LIKE :search OR gibbonPerson.vehicleRegistration LIKE :search )";
                }

                if ($allStudents != 'on') {
                    $data['gibbonSchoolYearID'] = $_SESSION[$guid]['gibbonSchoolYearID'];
                    $data['today'] = date('Y-m-d');
                    $sql = "SELECT DISTINCT gibbonPerson.gibbonPersonID, gibbonPerson.status, gibbonStudentEnrolmentID, gibbonPerson.surname, gibbonPerson.preferredName, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup
                        FROM gibbonPerson
                            INNER JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                            INNER JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID)
                            INNER JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID)
                        $familySQL
                        WHERE gibbonPerson.status='Full'
                            AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
                            AND (gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:today)
                            AND (gibbonPerson.dateEnd IS NULL  OR gibbonPerson.dateEnd>=:today) ";
                } else {
                    $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonPerson.status, NULL AS gibbonStudentEnrolmentID, gibbonPerson.surname, gibbonPerson.preferredName, NULL AS yearGroup, NULL AS rollGroup
                        FROM gibbonPerson
                            JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDAll LIKE concat('%', gibbonRole.gibbonRoleID , '%'))
                        $familySQL
                        WHERE gibbonRole.category='Student'";
                }

                if ($sort != 'surname, preferredName' && $sort != 'preferredName' && $sort != 'rollGroup' && $sort != 'yearGroup') {
                    $sort = 'surname, preferredName';
                }

                $sql = $sql.$searchSql.' ORDER BY '.$sort;
                $sqlPage = $sql.' LIMIT '.$_SESSION[$guid]['pagination'].' OFFSET '.(($page - 1) * $_SESSION[$guid]['pagination']);
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($result->rowcount() < 1) {
                echo "<div class='error'>";
                echo __($guid, 'There are no records to display.');
                echo '</div>';
            } else {
                if ($result->rowcount() > $_SESSION[$guid]['pagination']) {
                    printPagination($guid, $result->rowcount(), $page, $_SESSION[$guid]['pagination'], 'top', "&search=$search&allStudents=$allStudents&sort=$sort");
                }

                echo "<table cellspacing='0' style='width: 100%'>";
                echo "<tr class='head'>";
                echo '<th>';
                echo __($guid, 'Name');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Year Group');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Roll Group');
                echo '</th>';
                echo '<th>';
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
                    if ($row['status'] != 'Full') {
                        $rowNum = 'error';
                    }
                    ++$count;

                    //COLOR ROW BY STATUS!
                    echo "<tr class=$rowNum>";
                    echo '<td>';
                    echo formatName('', $row['preferredName'], $row['surname'], 'Student', true);
                    echo '</td>';
                    echo '<td>';
                    if ($row['yearGroup'] != '') {
                        echo __($guid, $row['yearGroup']);
                    }
                    echo '</td>';
                    echo '<td>';
                    echo $row['rollGroup'];
                    echo '</td>';
                    echo '<td>';
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/student_view_details.php&gibbonPersonID='.$row['gibbonPersonID']."&search=$search&allStudents=$allStudents&sort=$sort'><img title='".__($guid, 'View Details')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/plus.png'/></a> ";
                    echo '</td>';
                    echo '</tr>';
                }
                echo '</table>';

                if ($result->rowcount() > $_SESSION[$guid]['pagination']) {
                    printPagination($guid, $result->rowcount(), $page, $_SESSION[$guid]['pagination'], 'bottom', "search=$search&allStudents=$allStudents&sort=$sort");
                }
            }
        }
    }
}
?>
