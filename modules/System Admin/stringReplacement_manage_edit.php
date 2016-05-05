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

if (isActionAccessible($guid, $connection2, '/modules/System Admin/stringReplacement_manage_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/System Admin/stringReplacement_manage.php'>".__($guid, 'Manage String Replacements')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit String').'</div>';
    echo '</div>';

    $search = '';
    if (isset($_GET['search'])) {
        $search = $_GET['search'];
    }

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Check if school year specified
    $gibbonStringID = $_GET['gibbonStringID'];
    if ($gibbonStringID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonStringID' => $gibbonStringID);
            $sql = 'SELECT * FROM gibbonString WHERE gibbonStringID=:gibbonStringID';
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

            if ($search != '') {
                echo "<div class='linkTop'>";
                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/System Admin/stringReplacement_manage.php&search=$search'>".__($guid, 'Back to Search Results').'</a>';
                echo '</div>';
            }
            ?>
			<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/stringReplacement_manage_editProcess.php?gibbonStringID='.$row['gibbonStringID']."&search=$search" ?>">
				<table class='smallIntBorder fullWidth' cellspacing='0'>	
					<tr>
						<td> 
							<b><?php echo __($guid, 'Original String') ?> *</b><br/>
						</td>
						<td class="right">
							<input name="original" id="original" maxlength=100 value="<?php echo htmlPrep($row['original']) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var original=new LiveValidation('original');
								original.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Replacement String') ?> *</b><br/>
						</td>
						<td class="right">
							<input name="replacement" id="replacement" maxlength=100 value="<?php echo htmlPrep($row['replacement']) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var replacement=new LiveValidation('replacement');
								replacement.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Mode') ?> *</b><br/>
						</td>
						<td class="right">
							<select name="mode" id="mode" class="standardWidth">
								<?php
                                $selected = '';
								if ($row['mode'] == 'Whole') {
									$selected = 'selected';
								}
								echo "<option $selected value=\"Whole\">".__($guid, 'Whole').'</option>';
								$selected = '';
								if ($row['mode'] == 'Partial') {
									$selected = 'selected';
								}
								echo "<option $selected value=\"Partial\">".__($guid, 'Partial').'</option>'; ?>
							</select>
						</td>
					</tr>
			
					<tr>
						<td> 
							<b><?php echo __($guid, 'Case Sensitive') ?> *</b><br/>
						</td>
						<td class="right">
							<select name="caseSensitive" id="caseSensitive" class="standardWidth">
								<?php
                                $selected = '';
								if ($row['caseSensitive'] == 'N') {
									$selected = 'selected';
								}
								echo "<option $selected value='N'>".ynExpander($guid, 'N').'</option>';
								$selected = '';
								if ($row['caseSensitive'] == 'Y') {
									$selected = 'selected';
								}
								echo "<option $selected value='Y'>".ynExpander($guid, 'Y').'</option>'; ?>
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Priority') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Higher priorities are substituted first.') ?></span>
						</td>
						<td class="right">
							<input name="priority" id="priority" maxlength=2 value="<?php echo htmlPrep($row['priority']) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var priority=new LiveValidation('priority');
								priority.add(Validate.Presence);
								priority.add(Validate.Numericality);
							</script>
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
    }
}
?>