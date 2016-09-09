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

if (isActionAccessible($guid, $connection2, '/modules/School Admin/schoolYearSpecialDay_manage_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/schoolYearSpecialDay_manage.php&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID']."'>".__($guid, 'Manage Special Days')."</a> > </div><div class='trailEnd'>".__($guid, 'Add Special Day').'</div>';
    echo '</div>';

    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
    $dateStamp = $_GET['dateStamp'];
    $gibbonSchoolYearTermID = $_GET['gibbonSchoolYearTermID'];
    $firstDay = $_GET['firstDay'];
    $lastDay = $_GET['lastDay'];

    if ($gibbonSchoolYearID == '' or $dateStamp == '' or $gibbonSchoolYearTermID == '' or $firstDay == '' or $lastDay == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
            $sql = 'SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __($guid, 'The specified record does not exist.');
            echo '</div>';
        } elseif ($dateStamp < $firstDay or $dateStamp > $lastDay) {
            echo "<div class='error'>";
            echo __($guid, 'The specified date is outside of the allowed range.');
            echo '</div>';
        } else {
            ?>
			<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/schoolYearSpecialDay_manage_addProcess.php' ?>">
				<table class='smallIntBorder fullWidth' cellspacing='0'>
					<tr>
						<td style='width: 275px'>
							<b><?php echo __($guid, 'Date') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Must be unique.') ?> <?php echo __($guid, 'This value cannot be changed.') ?></span>
						</td>
						<td class="right">
							<input readonly name="date" id="date" maxlength=10 value="<?php echo dateConvertBack($guid, date('Y-m-d', $dateStamp)) ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var date=new LiveValidation('date');
								date.add(Validate.Presence);
								date.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
								echo "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i";
								} else {
									echo $_SESSION[$guid]['i18n']['dateFormatRegEx'];
								}
								?>, failureMessage: "Use <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
									echo 'dd/mm/yyyy';
								} else {
									echo $_SESSION[$guid]['i18n']['dateFormat'];
								}
								?>." } );
							</script>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Type') ?> *</b>
						</td>
						<td class="right">
							<select name="type" id="type" class="standardWidth">
								<option value="Please select..."><?php echo __($guid, 'Please select...') ?></option>
								<option value="School Closure"><?php echo __($guid, 'School Closure') ?></option>
								<option value="Timing Change"><?php echo __($guid, 'Timing Change') ?></option>
							</select>
							<script type="text/javascript">
								var type=new LiveValidation('type');
								type.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
							</script>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Name') ?> *</b><br/>
							<span class="emphasis small"></span>
						</td>
						<td class="right">
							<input name="name" id="name" maxlength=20 value="" type="text" class="standardWidth">
							<script type="text/javascript">
								var name2=new LiveValidation('name');
								name2.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Description') ?></b><br/>
							<span class="emphasis small"></span>
						</td>
						<td class="right">
							<input name="description" id="description" maxlength=255 value="" type="text" class="standardWidth">
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'School Opens') ?></b>
						</td>
						<td class="right">
							<select style="width:100px" name="schoolOpenM" id="schoolOpenM">
								<?php
                                echo "<option value='Minutes'>".__($guid, 'Minutes').'</option>';
								for ($i = 0;$i < 60;++$i) {
									$iPrint = $i;
									if (strlen($i) == 1) {
										$iPrint = '0'.$i;
									}
									echo "<option value='".$iPrint."'>".$iPrint.'</option>';
								}
								?>
							</select>
							<select style="width:100px" name="schoolOpenH" id="schoolOpenH">
								<?php
                                echo "<option value='Hours'>".__($guid, 'Hours').'</option>';
								for ($i = 0;$i < 24;++$i) {
									$iPrint = $i;
									if (strlen($i) == 1) {
										$iPrint = '0'.$i;
									}
                                    echo "<option value='".$iPrint."'>".$iPrint.'</option>';
								}
								?>
							</select>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'School Starts') ?></b>
						</td>
						<td class="right">
							<select style="width:100px" name="schoolStartM" id="schoolStartM">
								<?php
                                echo "<option value='Minutes'>".__($guid, 'Minutes').'</option>';
								for ($i = 0;$i < 60;++$i) {
									$iPrint = $i;
									if (strlen($i) == 1) {
										$iPrint = '0'.$i;
									}
                                    echo "<option value='".$iPrint."'>".$iPrint.'</option>';
								}
								?>
							</select>
							<select style="width:100px" name="schoolStartH" id="schoolStartH">
								<?php
                                echo "<option value='Hours'>".__($guid, 'Hours').'</option>';
								for ($i = 0;$i < 24;++$i) {
									$iPrint = $i;
									if (strlen($i) == 1) {
										$iPrint = '0'.$i;
									}
                                    echo "<option value='".$iPrint."'>".$iPrint.'</option>';
								}
								?>
							</select>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'School Ends') ?></b>
						</td>
						<td class="right">
							<select style="width:100px" name="schoolEndM" id="schoolEndM">
								<?php
                                echo "<option value='Minutes'>".__($guid, 'Minutes').'</option>';
								for ($i = 0;$i < 60;++$i) {
									$iPrint = $i;
									if (strlen($i) == 1) {
										$iPrint = '0'.$i;
									}
                                    echo "<option value='".$iPrint."'>".$iPrint.'</option>';
								}
								?>
							</select>
							<select style="width:100px" name="schoolEndH" id="schoolEndH">
								<?php
                                echo "<option value='Hours'>".__($guid, 'Hours').'</option>';
								for ($i = 0;$i < 24;++$i) {
									$iPrint = $i;
									if (strlen($i) == 1) {
										$iPrint = '0'.$i;
									}
                                    echo "<option value='".$iPrint."'>".$iPrint.'</option>';
								}
								?>
							</select>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'School Closes') ?></b>
						</td>
						<td class="right">
							<select style="width:100px" name="schoolCloseM" id="schoolCloseM">
								<?php
                                echo "<option value='Minutes'>".__($guid, 'Minutes').'</option>';
								for ($i = 0;$i < 60;++$i) {
									$iPrint = $i;
									if (strlen($i) == 1) {
										$iPrint = '0'.$i;
									}
                                    echo "<option value='".$iPrint."'>".$iPrint.'</option>';
								}
								?>
							</select>
							<select style="width:100px" name="schoolCloseH" id="schoolCloseH">
								<?php
                                echo "<option value='Hours'>".__($guid, 'Hours').'</option>';
								for ($i = 0;$i < 24;++$i) {
									$iPrint = $i;
									if (strlen($i) == 1) {
										$iPrint = '0'.$i;
									}
                                    echo "<option value='".$iPrint."'>".$iPrint.'</option>';
								}
								?>
							</select>
						</td>
					</tr>
					<tr>
						<td>
							<span class="emphasis small">* <?php echo __($guid, 'denotes a required field'); ?></span>
						</td>
						<td class="right">
							<input name="gibbonSchoolYearID" id="gibbonSchoolYearID" value="<?php echo $gibbonSchoolYearID ?>" type="hidden">
							<input name="dateStamp" id="dateStamp" value="<?php echo $dateStamp ?>" type="hidden">
							<input name="firstDay" id="firstDay" value="<?php echo $firstDay ?>" type="hidden">
							<input name="lastDay" id="lastDay" value="<?php echo $lastDay ?>" type="hidden">
							<input name="gibbonSchoolYearTermID" id="gibbonSchoolYearTermID" value="<?php echo $gibbonSchoolYearTermID ?>" type="hidden">
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
