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

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/tt_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/tt.php&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID']."'>".__($guid, 'Manage Timetables')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Timetable').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    if (isset($_GET['deleteReturn'])) {
        $deleteReturn = $_GET['deleteReturn'];
    } else {
        $deleteReturn = '';
    }
    $deleteReturnMessage = '';
    $class = 'error';
    if (!($deleteReturn == '')) {
        if ($deleteReturn == 'success0') {
            $deleteReturnMessage = __($guid, 'Your request was completed successfully.');
            $class = 'success';
        }
        echo "<div class='$class'>";
        echo $deleteReturnMessage;
        echo '</div>';
    }

    //Check if school year specified
    $gibbonTTID = $_GET['gibbonTTID'];
    if ($gibbonTTID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonTTID' => $gibbonTTID);
            $sql = 'SELECT gibbonTT.*, gibbonSchoolYear.name as yearName FROM gibbonTT JOIN gibbonSchoolYear ON (gibbonTT.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonTTID=:gibbonTTID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __($guid, 'The specified record cannot be found.');
            echo '</div>';
        } else {
            //Let's go!
            $row = $result->fetch();
            ?>
			<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/tt_editProcess.php?gibbonTTID=$gibbonTTID&gibbonSchoolYearID=".$_GET['gibbonSchoolYearID'] ?>">
				<table class='smallIntBorder fullWidth' cellspacing='0'>	
					<tr>
						<td style='width: 275px'> 
							<b><?php echo __($guid, 'School Year') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
						</td>
						<td class="right">
							<input readonly name="gibbonSchoolYearID" id="gibbonSchoolYearID" maxlength=20 value="<?php echo $row['yearName'] ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var schoolYearName=new LiveValidation('schoolYearName');
								schoolYearname2.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Name') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Must be unique for this school year.') ?></span>
						</td>
						<td class="right">
							<input name="name" id="name" maxlength=30 value="<?php echo htmlPrep($row['name']) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var name2=new LiveValidation('name');
								name2.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Short Name') ?> *</b><br/>
							<span class="emphasis small"></span>
						</td>
						<td class="right">
							<input name="nameShort" id="nameShort" maxlength=12 value="<?php echo htmlPrep($row['nameShort']) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var nameShort=new LiveValidation('nameShort');
								nameShort.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Active') ?></b><br/>
							<span class="emphasis small"></span>
						</td>
						<td class="right">
							<select class="standardWidth" name="active">
								<?php
                                echo '<option ';
            if ($row['active'] == 'Y') {
                echo 'selected ';
            };
            echo "value='Y'>".__($guid, 'Yes').'</option>';
            echo '<option ';
            if ($row['active'] == 'N') {
                echo 'selected ';
            };
            echo " value='N'>".__($guid, 'No').'</option>'; ?>				
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Year Groups') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Groups not in an active TT this year.') ?></span>
						</td>
						<td class="right">
							<?php 
                            $yearGroups = getNonTTYearGroups($connection2, $_GET['gibbonSchoolYearID'], $gibbonTTID);
            if ($yearGroups == '') {
                echo '<i>'.__($guid, 'No year groups available.').'</i>';
            } else {
                for ($i = 0; $i < count($yearGroups); $i = $i + 2) {
                    $checked = '';
                    if (is_numeric(strpos($row['gibbonYearGroupIDList'], $yearGroups[$i]))) {
                        $checked = 'checked ';
                    }
                    echo __($guid, $yearGroups[($i + 1)])." <input $checked type='checkbox' name='gibbonYearGroupIDCheck".($i) / 2 ."'><br/>";
                    echo "<input type='hidden' name='gibbonYearGroupID".($i) / 2 ."' value='".$yearGroups[$i]."'>";
                }
            }
            ?>
							<input type="hidden" name="count" value="<?php echo(count($yearGroups)) / 2 ?>">
						</td>
					</tr>
					<tr>
						<td>
							<span class="emphasis small">* <?php echo __($guid, 'denotes a required field'); ?></span>
						</td>
						<td class="right">
							<input name="gibbonTTID" id="gibbonTTID" value="<?php echo $_GET['gibbonTTID'] ?>" type="hidden">
							<input name="gibbonSchoolYearID" id="gibbonSchoolYearID" value="<?php echo $_GET['gibbonSchoolYearID'] ?>" type="hidden">
							<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
							<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
						</td>
					</tr>
				</table>
			</form>
			<?php

            echo '<h2>';
            echo __($guid, 'Edit Timetable Days');
            echo '</h2>';

            try {
                $data = array('gibbonTTID' => $gibbonTTID);
                $sql = 'SELECT gibbonTTDay.*, gibbonTTColumn.name AS columnName FROM gibbonTTDay JOIN gibbonTTColumn ON (gibbonTTDay.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) WHERE gibbonTTID=:gibbonTTID ORDER BY gibbonTTDay.name';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            echo "<div class='linkTop'>";
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/tt_edit_day_add.php&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID']."&gibbonTTID=$gibbonTTID'>".__($guid, 'Add')."<img style='margin-left: 5px' title='".__($guid, 'Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
            echo '</div>';

            if ($result->rowCount() < 1) {
                echo "<div class='error'>";
                echo __($guid, 'There are no records to display.');
                echo '</div>';
            } else {
                echo "<table cellspacing='0' style='width: 100%'>";
                echo "<tr class='head'>";
                echo '<th>';
                echo __($guid, 'Name');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Short Name');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Column');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Actions');
                echo '</th>';
                echo '</tr>';

                $count = 0;
                $rowNum = 'odd';
                while ($row = $result->fetch()) {
                    if ($count % 2 == 0) {
                        $rowNum = 'even';
                    } else {
                        $rowNum = 'odd';
                    }

                    //COLOR ROW BY STATUS!
                    echo "<tr class=$rowNum>";
                    echo '<td>';
                    echo $row['name'];
                    echo '</td>';
                    echo '<td>';
                    echo $row['nameShort'];
                    echo '</td>';
                    echo '<td>';
                    echo $row['columnName'];
                    echo '</td>';
                    echo '<td>';
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/tt_edit_day_edit.php&gibbonTTDayID='.$row['gibbonTTDayID']."&gibbonTTID=$gibbonTTID&gibbonSchoolYearID=".$_GET['gibbonSchoolYearID']."'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/tt_edit_day_delete.php&gibbonTTDayID='.$row['gibbonTTDayID']."&gibbonTTID=$gibbonTTID&gibbonSchoolYearID=".$_GET['gibbonSchoolYearID']."'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a> ";
                    echo '</td>';
                    echo '</tr>';

                    ++$count;
                }
                echo '</table>';
            }
        }
    }
}
?>