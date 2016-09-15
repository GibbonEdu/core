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

if (isActionAccessible($guid, $connection2, '/modules/Attendance/report_rollGroupsNotRegistered_byDate.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Roll Groups Not Registered').'</div>';
    echo '</div>';
    echo '<h2>';
    echo __($guid, 'Choose Date');
    echo '</h2>';

    if (isset($_GET['dateStart']) == false) {
        $dateStart = date('Y-m-d');
    } else {
        $dateStart = dateConvert($guid, $_GET['dateStart']);
    }
    if (isset($_GET['dateEnd']) == false) {
        $dateEnd = date('Y-m-d');
    } else {
        $dateEnd = dateConvert($guid, $_GET['dateEnd']);
    }
    ?>
	
	<form method="get" action="<?php echo $_SESSION[$guid]['absoluteURL']?>/index.php">
		<table class='smallIntBorder fullWidth' cellspacing='0'>	
			<tr>
				<td style='width: 275px'> 
					<b><?php echo __($guid, 'Start Date') ?> *</b><br/>
					<span class="emphasis small"><?php echo __($guid, 'Format:').' '.$_SESSION[$guid]['i18n']['dateFormat']  ?></span>
				</td>
				<td class="right">
					<input name="dateStart" id="dateStart" maxlength=10 value="<?php echo dateConvertBack($guid, $dateStart) ?>" type="text" class="standardWidth">
					<script type="text/javascript">
						var dateStart=new LiveValidation('dateStart');
						dateStart.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
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
						dateStart.add(Validate.Presence);
					</script>
					 <script type="text/javascript">
						$(function() {
							$( "#dateStart" ).datepicker();
						});
					</script>
				</td>
			</tr>
			<tr>
				<td style='width: 275px'> 
					<b><?php echo __($guid, 'End Date') ?> *</b><br/>
					<span class="emphasis small"><?php echo __($guid, 'Format:').' '.$_SESSION[$guid]['i18n']['dateFormat']  ?></span>
				</td>
				<td class="right">
					<input name="dateEnd" id="dateEnd" maxlength=10 value="<?php echo dateConvertBack($guid, $dateEnd) ?>" type="text" class="standardWidth">
					<script type="text/javascript">
						var dateEnd=new LiveValidation('dateEnd');
						dateEnd.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
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
						dateEnd.add(Validate.Presence);
					</script>
					 <script type="text/javascript">
						$(function() {
							$( "#dateEnd" ).datepicker();
						});
					</script>
				</td>
			</tr>
			<tr>
				<td colspan=2 class="right">
					<input type="hidden" name="q" value="/modules/<?php echo $_SESSION[$guid]['module'] ?>/report_rollGroupsNotRegistered_byDate.php">
					<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
				</td>
			</tr>
		</table>
	</form>
	<?php

    if ($dateStart != '') {
        echo '<h2>';
        echo __($guid, 'Report Data');
        echo '</h2>';

        //Produce array of attendance data
        try {
            $data = array('dateStart' => $dateStart, 'dateEnd' => $dateEnd);
            $sql = 'SELECT date, gibbonRollGroupID FROM gibbonAttendanceLogRollGroup WHERE date>=:dateStart AND date<=:dateEnd ORDER BY date';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }
        $log = array();
        while ($row = $result->fetch()) {
            $log[$row['date']][$row['gibbonRollGroupID']] = true;
        }

        try {
            $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
            $sql = "SELECT gibbonRollGroupID, name, gibbonPersonIDTutor, gibbonPersonIDTutor2, gibbonPersonIDTutor3 FROM gibbonRollGroup WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND attendance='Y' ORDER BY LENGTH(name), name";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() < 1) {
            echo "<div class='error'>";
            echo __($guid, 'There are no records to display.');
            echo '</div>';
        } else {
            //Produce array of roll groups
            $rollGroups = $result->fetchAll();

            echo "<div class='linkTop'>";
            echo "<a target='_blank' href='".$_SESSION[$guid]['absoluteURL'].'/report.php?q=/modules/'.$_SESSION[$guid]['module'].'/report_rollGroupsNotRegistered_byDate_print.php&dateStart='.dateConvertBack($guid, $dateStart).'&dateEnd='.dateConvertBack($guid, $dateEnd)."'>".__($guid, 'Print')."<img style='margin-left: 5px' title='".__($guid, 'Print')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/print.png'/></a>";
            echo '</div>';

            echo "<table cellspacing='0' style='width: 100%'>";
            echo "<tr class='head'>";
            echo '<th>';
            echo __($guid, 'Roll Group');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Date');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Tutor');
            echo '</th>';
            echo '</tr>';

            $count = 0;
            $rowNum = 'odd';

			//Loop through each date
			$timestampStart = dateConvertToTimestamp($dateStart);
            $timestampEnd = dateConvertToTimestamp($dateEnd);
            for ($i = $timestampStart; $i <= $timestampEnd; $i = ($i + (60 * 60 * 24))) {
                if (isSchoolOpen($guid, date('Y-m-d', $i), $connection2, true)) {
                    //Loop through each roll group
                        foreach ($rollGroups as $row) {
                            //Output row only if not registered on specified date
                            if (isset($log[date('Y-m-d', $i)][$row['gibbonRollGroupID']]) == false) {
                                if ($count % 2 == 0) {
                                    $rowNum = 'even';
                                } else {
                                    $rowNum = 'odd';
                                }
                                ++$count;

                                //COLOR ROW BY STATUS!
                                echo "<tr class=$rowNum>";
                                echo '<td>';
                                echo $row['name'];
                                echo '</td>';
                                echo '<td>';
                                echo dateConvertBack($guid, date('Y-m-d', $i));
                                echo '</td>';
                                echo '<td>';
                                if ($row['gibbonPersonIDTutor'] == '' and $row['gibbonPersonIDTutor2'] == '' and $row['gibbonPersonIDTutor3'] == '') {
                                    echo '<i>Not set</i>';
                                } else {
                                    try {
                                        $dataTutor = array('gibbonPersonID1' => $row['gibbonPersonIDTutor'], 'gibbonPersonID2' => $row['gibbonPersonIDTutor2'], 'gibbonPersonID3' => $row['gibbonPersonIDTutor3']);
                                        $sqlTutor = 'SELECT surname, preferredName FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID1 OR gibbonPersonID=:gibbonPersonID2 OR gibbonPersonID=:gibbonPersonID3';
                                        $resultTutor = $connection2->prepare($sqlTutor);
                                        $resultTutor->execute($dataTutor);
                                    } catch (PDOException $e) {
                                        echo "<div class='error'>".$e->getMessage().'</div>';
                                    }

                                    while ($rowTutor = $resultTutor->fetch()) {
                                        echo formatName('', $rowTutor['preferredName'], $rowTutor['surname'], 'Staff', true, true).'<br/>';
                                    }
                                }
                                echo '</td>';
                                echo '</tr>';
                            }
                        }
                }
            }

            if ($count == 0) {
                echo "<tr class=$rowNum>";
                echo '<td colspan=3>';
                echo __($guid, 'All roll groups have been registered.');
                echo '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
    }
}
?>