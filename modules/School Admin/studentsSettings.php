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

if (isActionAccessible($guid, $connection2, '/modules/School Admin/studentsSettings.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Manage Students Settings').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    echo '<h3>';
    echo __($guid, 'Student Note Categories');
    echo '</h3>';
    echo '<p>';
    echo __($guid, 'This section allows you to manage the categories which can be associated with student notes. Categories can be given templates, which will pre-populate the student note on selection.');
    echo '</p>';

    try {
        $data = array();
        $sql = 'SELECT * FROM gibbonStudentNoteCategory ORDER BY name';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    echo "<div class='linkTop'>";
    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/studentsSettings_noteCategory_add.php'>".__($guid, 'Add')."<img style='margin-left: 5px' title='".__($guid, 'Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
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
        echo __($guid, 'Active');
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
            ++$count;

            if ($row['active'] == 'N') {
                $rowNum = 'error';
            }

            //COLOR ROW BY STATUS!
            echo "<tr class=$rowNum>";
            echo '<td>';
            echo $row['name'];
            echo '</td>';
            echo '<td>';
            echo ynExpander($guid, $row['active']);
            echo '</td>';
            echo '<td>';
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/studentsSettings_noteCategory_edit.php&gibbonStudentNoteCategoryID='.$row['gibbonStudentNoteCategoryID']."'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/studentsSettings_noteCategory_delete.php&gibbonStudentNoteCategoryID='.$row['gibbonStudentNoteCategoryID']."'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a>";
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }

    echo '<h3>';
    echo __($guid, 'Settings');
    echo '</h3>';

    ?>
	<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/studentsSettingsProcess.php' ?>">
		<table class='smallIntBorder fullWidth' cellspacing='0'>
            <tr class='break'>
                <td colspan=2>
                    <h3><?php echo __($guid, 'Student Notes'); ?></h3>
                </td>
            </tr>
            <tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Students' AND name='enableStudentNotes'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {}
                $row = $result->fetch();
                ?>
				<td style='width: 275px'>
					<b><?php echo __($guid, $row['nameDisplay']) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') { echo __($guid, $row['description']);}?></span>
				</td>
				<td class="right">
					<select name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" class="standardWidth">
                        <option <?php if ($row['value'] == 'N') { echo 'selected '; } ?>value="N"><?php echo __($guid, 'No') ?></option>
						<option <?php if ($row['value'] == 'Y') { echo 'selected '; } ?>value="Y"><?php echo __($guid, 'Yes') ?></option>
					</select>
				</td>
			</tr>
            <tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Students' AND name='noteCreationNotification'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {}
                $row = $result->fetch();
                ?>
				<td style='width: 275px'>
					<b><?php echo __($guid, $row['nameDisplay']) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') { echo __($guid, $row['description']);}?></span>
				</td>
				<td class="right">
					<select name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" class="standardWidth">
                        <option <?php if ($row['value'] == 'Tutors') { echo 'selected '; } ?>value="Tutors"><?php echo __($guid, 'Tutors') ?></option>
						<option <?php if ($row['value'] == 'Tutors & Teachers') { echo 'selected '; } ?>value="Tutors & Teachers"><?php echo __($guid, 'Tutors & Teachers') ?></option>
					</select>
				</td>
			</tr>
            <tr class='break'>
                <td colspan=2>
                    <h3><?php echo __($guid, 'Miscellaneous'); ?></h3>
                </td>
            </tr>
            <tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='Students' AND name='extendedBriefProfile'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {}
                $row = $result->fetch();
                ?>
				<td style='width: 275px'>
					<b><?php echo __($guid, $row['nameDisplay']) ?> *</b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') { echo __($guid, $row['description']);}?></span>
				</td>
				<td class="right">
					<select name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" class="standardWidth">
						<option <?php if ($row['value'] == 'N') { echo 'selected '; } ?>value="N"><?php echo __($guid, 'No') ?></option>
						<option <?php if ($row['value'] == 'Y') { echo 'selected '; } ?>value="Y"><?php echo __($guid, 'Yes') ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<?php
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonSetting WHERE scope='School Admin' AND name='studentAgreementOptions'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {}
                $row = $result->fetch();
                ?>
				<td style='width: 275px'>
					<b><?php echo __($guid, $row['nameDisplay']) ?></b><br/>
					<span class="emphasis small"><?php if ($row['description'] != '') { echo __($guid, $row['description']);}?></span>
				</td>
				<td class="right">
					<textarea name="<?php echo $row['name'] ?>" id="<?php echo $row['name'] ?>" type="text" class="standardWidth" rows=4><?php echo $row['value'] ?></textarea>
				</td>
			</tr>
			<tr>
				<td>
					<span class="emphasis small">* <?php echo __($guid, 'denotes a required field'); ?></span>
				</td>
				<td class="right">
					<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
					<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
				</td>
			</tr>
		</table>
	</form>
	<?php

}
?>
