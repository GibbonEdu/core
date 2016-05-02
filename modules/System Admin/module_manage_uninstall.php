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

$orphaned = '';
if (isset($_GET['orphaned'])) {
    if ($_GET['orphaned'] == 'true') {
        $orphaned = 'true';
    }
}

if (isActionAccessible($guid, $connection2, '/modules/System Admin/module_manage_uninstall.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/module_manage.php'>".__($guid, 'Manage Modules')."</a> > </div><div class='trailEnd'>".__($guid, 'Uninstall Module').'</div>';
    echo '</div>';

    if (isset($_GET['deleteReturn'])) {
        $deleteReturn = $_GET['deleteReturn'];
    } else {
        $deleteReturn = '';
    }
    $deleteReturnMessage = '';
    $class = 'error';
    if (!($deleteReturn == '')) {
        if ($deleteReturn == 'fail0') {
            $deleteReturnMessage = __($guid, 'Your request failed because you do not have access to this action.');
        } elseif ($deleteReturn == 'fail1') {
            $deleteReturnMessage = __($guid, 'Your request failed because your inputs were invalid.');
        } elseif ($deleteReturn == 'fail2') {
            $deleteReturnMessage = __($guid, 'Your request failed because your inputs were invalid.');
        } elseif ($deleteReturn == 'fail3') {
            $deleteReturnMessage = __($guid, 'Uninstall encountered a partial fail: the module may or may not still work.');
        }
        echo "<div class='$class'>";
        echo $deleteReturnMessage;
        echo '</div>';
    }

    //Check if school year specified
    $gibbonModuleID = $_GET['gibbonModuleID'];
    if ($gibbonModuleID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonModuleID' => $gibbonModuleID);
            $sql = 'SELECT * FROM gibbonModule WHERE gibbonModuleID=:gibbonModuleID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __($guid, 'You have not specified one or more required parameters.');
            echo '</div>';
        } else {
            //Let's go!
            $row = $result->fetch();
            ?>
			<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/module_manage_uninstallProcess.php?gibbonModuleID=$gibbonModuleID&orphaned=$orphaned" ?>">
				<table class='smallIntBorder fullWidth' cellspacing='0'>	
					<tr>
						<td style='width: 275px' colspan=2> 
							<b><?php echo __($guid, 'Are you sure you want to delete this record?');
            ?></b><br/>
							<span style="font-size: 90%; color: #cc0000"><i><?php echo __($guid, 'This operation cannot be undone, and may lead to loss of vital data in your system. PROCEED WITH CAUTION!');
            ?></span>
						</td>
					</tr>
					<tr>
						<td style='width: 275px'> 
							<b><?php echo __($guid, 'Remove Data') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Would you like to remove the following tables and views from your database?') ?></span>
						</td>
						<td class="right">
							<?php
                            if (is_file($_SESSION[$guid]['absolutePath'].'/modules/'.$row['name'].'/manifest.php') == false) {
                                echo "<div class='error'>";
                                echo __($guid, 'An error has occurred.');
                                echo '</div>';
                            } else {
                                $count = 0;
                                include $_SESSION[$guid]['absolutePath'].'/modules/'.$row['name'].'/manifest.php';
                                if (is_array($moduleTables)) {
                                    foreach ($moduleTables as $moduleTable) {
                                        $type = null;
                                        $tokens = null;
                                        $name = '';
                                        $moduleTable = trim($moduleTable);
                                        if (substr($moduleTable, 0, 12) == 'CREATE TABLE') {
                                            $type = __($guid, 'Table');
                                        } elseif (substr($moduleTable, 0, 11) == 'CREATE VIEW') {
                                            $type = __($guid, 'View');
                                        }
                                        if ($type != null) {
                                            $tokens = preg_split('/ +/', $moduleTable);
                                            if (isset($tokens[2])) {
                                                $name = str_replace('`', '', $tokens[2]);
                                                if ($name != '') {
                                                    echo '<b>'.$type.'</b>: '.$name;
                                                    echo " <input checked type='checkbox' name='remove[]' value='".$type.'-'.$name."' /><br/>";
                                                    ++$count;
                                                }
                                            }
                                        }
                                    }
                                }
                                if ($count == 0) {
                                    echo __($guid, 'There are no records to display.');
                                }
                            }
            ?>
						</td>
					</tr>
					<tr>
						<td class="right" colspan=2>
							<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
							<input type="submit" value="<?php echo __($guid, 'Submit');
            ?>">
						</td>
					</tr>
				</table>
			</form>
			<?php

        }
    }
}
?>