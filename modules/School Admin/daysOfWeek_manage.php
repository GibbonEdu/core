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

if (isActionAccessible($guid, $connection2, '/modules/School Admin/daysOfWeek_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Manage Days of the Week').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    try {
        $data = array();
        $sql = "SELECT * FROM gibbonDaysOfWeek WHERE name='Monday' OR name='Tuesday' OR name='Wednesday' OR name='Thursday' OR name='Friday' OR name='Saturday' OR name='Sunday' ORDER BY sequenceNumber";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    if ($result->rowCount() != 7) {
        echo "<div class='error'>";
        echo __($guid, 'There is a problem with your database information for school days.');
        echo '</div>';
    } else {
        //Let's go!
        ?>
		<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/daysOfWeek_manageProcess.php'?>">
			<table class='smallIntBorder fullWidth' cellspacing='0'>	
			<?php
            while ($row = $result->fetch()) {
                ?>
				<tr class='break'>
					<td colspan=2> 
						<h3><?php echo __($guid, $row['name']).' ('.__($guid, $row['nameShort']).')' ?></h3>
					</td>
				</tr>
				<input name="<?php echo $row['name']?>sequenceNumber" id="<?php echo $row['name']?>sequenceNumber" maxlength=2 value="<?php echo $row['sequenceNumber'] ?>" type="hidden" class="standardWidth">
				<tr>
					<td style='width: 275px'> 
						<b><?php echo __($guid, 'School Day') ?> *</b>
					</td>
					<td class="right">
						<select class="standardWidth" name="<?php echo $row['name']?>schoolDay" id="<?php echo $row['name']?>schoolDay">
							<?php
                            if ($row['schoolDay'] == 'Y') {
                                echo "<option selected value='Y'>".__($guid, 'Yes').'</option>';
                                echo "<option value='N'>".__($guid, 'No').'</option>';
                            } else {
                                echo "<option value='Y'>".__($guid, 'Yes').'</option>';
                                echo "<option selected value='N'>".__($guid, 'No').'</option>';
                            }
                ?>				
						</select>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php echo __($guid, 'School Opens') ?></b>
					</td>
					<td class="right">
						<select style="width:100px" name="<?php echo $row['name']?>schoolOpenM" id="<?php echo $row['name']?>schoolOpenM">
							<?php
                            echo "<option value='Minutes'>".__($guid, 'Minutes').'</option>';
                for ($i = 0;$i < 60;++$i) {
                    $iPrint = $i;
                    if (strlen($i) == 1) {
                        $iPrint = '0'.$i;
                    }

                    if (substr($row['schoolOpen'], 3, 2) == $i and $row['schoolOpen'] != null) {
                        echo "<option selected value='".$iPrint."'>".$iPrint.'</option>';
                    } else {
                        echo "<option value='".$iPrint."'>".$iPrint.'</option>';
                    }
                }
                ?>				
						</select>
						<select style="width:100px" name="<?php echo $row['name']?>schoolOpenH" id="<?php echo $row['name']?>schoolOpenH">
							<?php
                            echo "<option value='Hours'>".__($guid, 'Hours').'</option>';
                for ($i = 0;$i < 24;++$i) {
                    $iPrint = $i;
                    if (strlen($i) == 1) {
                        $iPrint = '0'.$i;
                    }

                    if (substr($row['schoolOpen'], 0, 2) == $i and $row['schoolOpen'] != null) {
                        echo "<option selected value='".$iPrint."'>".$iPrint.'</option>';
                    } else {
                        echo "<option value='".$iPrint."'>".$iPrint.'</option>';
                    }
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
						<select style="width:100px" name="<?php echo $row['name']?>schoolStartM" id="<?php echo $row['name']?>schoolStartM">
							<?php
                            echo "<option value='Minutes'>".__($guid, 'Minutes').'</option>';
                for ($i = 0;$i < 60;++$i) {
                    $iPrint = $i;
                    if (strlen($i) == 1) {
                        $iPrint = '0'.$i;
                    }

                    if (substr($row['schoolStart'], 3, 2) == $i and $row['schoolStart'] != null) {
                        echo "<option selected value='".$iPrint."'>".$iPrint.'</option>';
                    } else {
                        echo "<option value='".$iPrint."'>".$iPrint.'</option>';
                    }
                }
                ?>				
						</select>
						<select style="width:100px" name="<?php echo $row['name']?>schoolStartH" id="<?php echo $row['name']?>schoolStartH">
							<?php
                            echo "<option value='Hours'>".__($guid, 'Hours').'</option>';
                for ($i = 0;$i < 24;++$i) {
                    $iPrint = $i;
                    if (strlen($i) == 1) {
                        $iPrint = '0'.$i;
                    }

                    if (substr($row['schoolStart'], 0, 2) == $i and $row['schoolStart'] != null) {
                        echo "<option selected value='".$iPrint."'>".$iPrint.'</option>';
                    } else {
                        echo "<option value='".$iPrint."'>".$iPrint.'</option>';
                    }
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
						<select style="width:100px" name="<?php echo $row['name']?>schoolEndM" id="<?php echo $row['name']?>schoolEndM">
							<?php
                            echo "<option value='Minutes'>".__($guid, 'Minutes').'</option>';
                for ($i = 0;$i < 60;++$i) {
                    $iPrint = $i;
                    if (strlen($i) == 1) {
                        $iPrint = '0'.$i;
                    }

                    if (substr($row['schoolEnd'], 3, 2) == $i and $row['schoolEnd'] != null) {
                        echo "<option selected value='".$iPrint."'>".$iPrint.'</option>';
                    } else {
                        echo "<option value='".$iPrint."'>".$iPrint.'</option>';
                    }
                }
                ?>				
						</select>
						<select style="width:100px" name="<?php echo $row['name']?>schoolEndH" id="<?php echo $row['name']?>schoolEndH">
							<?php
                            echo "<option value='Hours'>".__($guid, 'Hours').'</option>';
                for ($i = 0;$i < 24;++$i) {
                    $iPrint = $i;
                    if (strlen($i) == 1) {
                        $iPrint = '0'.$i;
                    }

                    if (substr($row['schoolEnd'], 0, 2) == $i and $row['schoolEnd'] != null) {
                        echo "<option selected value='".$iPrint."'>".$iPrint.'</option>';
                    } else {
                        echo "<option value='".$iPrint."'>".$iPrint.'</option>';
                    }
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
						<select style="width:100px" name="<?php echo $row['name']?>schoolCloseM" id="<?php echo $row['name']?>schoolCloseM">
							<?php
                            echo "<option value='Minutes'>".__($guid, 'Minutes').'</option>';
                for ($i = 0;$i < 60;++$i) {
                    $iPrint = $i;
                    if (strlen($i) == 1) {
                        $iPrint = '0'.$i;
                    }

                    if (substr($row['schoolClose'], 3, 2) == $i and $row['schoolClose'] != null) {
                        echo "<option selected value='".$iPrint."'>".$iPrint.'</option>';
                    } else {
                        echo "<option value='".$iPrint."'>".$iPrint.'</option>';
                    }
                }
                ?>				
						</select>
						<select style="width:100px" name="<?php echo $row['name']?>schoolCloseH" id="<?php echo $row['name']?>schoolCloseH">
							<?php
                            echo "<option value='Hours'>".__($guid, 'Hours').'</option>';
                for ($i = 0;$i < 24;++$i) {
                    $iPrint = $i;
                    if (strlen($i) == 1) {
                        $iPrint = '0'.$i;
                    }

                    if (substr($row['schoolClose'], 0, 2) == $i and $row['schoolClose'] != null) {
                        echo "<option selected value='".$iPrint."'>".$iPrint.'</option>';
                    } else {
                        echo "<option value='".$iPrint."'>".$iPrint.'</option>';
                    }
                }
                ?>				
						</select>
					</td>
				</tr>
			
				<?php

            }
        ?>
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
?>