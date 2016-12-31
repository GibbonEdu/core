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

if (isActionAccessible($guid, $connection2, '/modules/Students/report_students_new') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'New Students').'</div>';
    echo '</div>';

    echo '<h2>';
    echo __($guid, 'Choose Options');
    echo '</h2>';

    $type = null;
    if (isset($_GET['type'])) {
        $type = $_GET['type'];
    }
    $ignoreEnrolment = null;
    if (isset($_GET['ignoreEnrolment'])) {
        $ignoreEnrolment = $_GET['ignoreEnrolment'];
    }
    $startDateFrom = null;
    if (isset($_GET['startDateFrom'])) {
        $startDateFrom = $_GET['startDateFrom'];
    }
    $startDateTo = null;
    if (isset($_GET['startDateTo'])) {
        $startDateTo = $_GET['startDateTo'];
    }
    ?>

	<form method="get" action="<?php echo $_SESSION[$guid]['absoluteURL']?>/index.php">
		<table class='smallIntBorder fullWidth' cellspacing='0'>
			<script type="text/javascript">
				$(document).ready(function(){
                    <?php if ($type != 'Date Range') { echo "startDateFrom.disable(); startDateTo.disable();"; } ?>
					$("#type").change(function(){
						if ($('#type').val()=="Date Range" ) {
							$("#startDateFromRow").slideDown("fast", $("#startDateFromRow").css("display","table-row"));
							$("#startDateToRow").slideDown("fast", $("#startDateToRow").css("display","table-row"));
							$("#ignoreEnrolmentRow").slideDown("fast", $("#ignoreEnrolmentRow").css("display","table-row"));
                            startDateFrom.enable();
                            startDateTo.enable();
						} else {
							$("#startDateFromRow").css("display","none");
							$("#startDateToRow").css("display","none");
							$("#ignoreEnrolmentRow").css("display","none");
                            startDateFrom.disable();
                            startDateTo.disable();
						}
					 });
				});
			</script>
			<tr>
				<td style='width: 275px'>
					<b><?php echo __($guid, 'Type') ?> *</b><br/>
				</td>
				<td class="right">
					<select class="standardWidth" name="type" id="type" class="type">
						<?php
                        echo '<option';
                        if ($type == 'Current School Year') {
                            echo ' selected';
                        }
                        echo " value='Current School Year'>".__($guid, 'Current School Year').'</option>';
                        echo '<option';
                        if ($type == 'Date Range') {
                            echo ' selected';
                        }
                        echo " value='Date Range'>".__($guid, 'Date Range').'</option>';?>
					</select>
				</td>
			</tr>
			<tr id='startDateFromRow' <?php if ($type != 'Date Range') { echo "style='display: none'"; } ?>>
				<td>
					<b><?php echo __($guid, 'From Date') ?> *</b><br/>
					<span class="emphasis small"><?php echo __($guid, 'Earliest student start date to include.') ?><br/><?php echo __($guid, 'Format:') ?> <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') { echo 'dd/mm/yyyy';
					} else {
						echo $_SESSION[$guid]['i18n']['dateFormat'];
					}
    				?></span>
				</td>
				<td class="right">
					<input name="startDateFrom" id="startDateFrom" maxlength=10 value="<?php echo $startDateFrom ?>" type="text" class="standardWidth">
					<script type="text/javascript">
						var startDateFrom=new LiveValidation('startDateFrom');
						startDateFrom.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
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
                        startDateFrom.add(Validate.Presence);
					</script>
					<script type="text/javascript">
						$(function() {
							$( "#startDateFrom" ).datepicker();
						});
					</script>
				</td>
			</tr>
			<tr id='startDateToRow' <?php if ($type != 'Date Range') { echo "style='display: none'"; } ?>>
				<td>
					<b><?php echo __($guid, 'To Date') ?> *</b><br/>
					<span class="emphasis small"><?php echo __($guid, 'Latest student start date to include.') ?><br/><?php echo __($guid, 'Format:') ?> <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') { echo 'dd/mm/yyyy';
					} else {
						echo $_SESSION[$guid]['i18n']['dateFormat'];
					}
    				?></span>
				</td>
				<td class="right">
					<input name="startDateTo" id="startDateTo" maxlength=10 value="<?php echo $startDateTo ?>" type="text" class="standardWidth">
					<script type="text/javascript">
						var startDateTo=new LiveValidation('startDateTo');
						startDateTo.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
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
                        startDateTo.add(Validate.Presence);
					</script>
					<script type="text/javascript">
						$(function() {
							$( "#startDateTo" ).datepicker();
						});
					</script>
				</td>
			</tr>
			<tr id='ignoreEnrolmentRow' <?php if ($type != 'Date Range') { echo "style='display: none'"; } ?>>
				<td>
					<b><?php echo __($guid, 'Ignore Enrolment') ?></b><br/>
					<span class="emphasis small"><?php echo __($guid, 'This is useful for picking up students who are set to Full, have a start date but are not yet enroled.') ?></span>
				</td>
				<td class="right">
					<input <?php if ($ignoreEnrolment == 'on') { echo 'checked'; } ?> name="ignoreEnrolment" id="ignoreEnrolment" type="checkbox">
				</td>
			</tr>
			<tr>
				<td colspan=2 class="right">
					<input type="hidden" name="q" value="/modules/<?php echo $_SESSION[$guid]['module'] ?>/report_students_new.php">
					<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
				</td>
			</tr>
		</table>
	</form>
	<?php

    if ($type != '') {
        echo '<h2>';
        echo __($guid, 'Report Data');
        echo '</h2>';

        $proceed = true;
        if ($type == 'Date Range') {
            echo '<p>';
            echo __($guid, 'This report shows all students whose Start Date is on or between the indicated dates.');
            echo '</p>';

            if ($startDateFrom == '' or $startDateTo == '') {
                $proceed = false;
            }
        } elseif ($type == 'Current School Year') {
            echo '<p>';
            echo __($guid, 'This report shows all students who are newly arrived in the school during the current academic year (e.g. they were not enroled in the previous academic year).');
            echo '</p>';
        }

        if ($proceed == false) {
            echo "<div class='error'>";
            echo __($guid, 'Your request failed because your inputs were invalid.');
            echo '</div>';
        } else {
            try {
                if ($type == 'Date Range') {
                    if ($ignoreEnrolment != 'on') {
                        $data = array('startDateFrom' => dateConvert($guid, $startDateFrom), 'startDateTo' => dateConvert($guid, $startDateTo));
                        $sql = "SELECT DISTINCT gibbonPerson.gibbonPersonID, surname, preferredName, username, dateStart, lastSchool, (SELECT nameShort FROM gibbonRollGroup JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) JOIN gibbonSchoolYear ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID ORDER BY gibbonSchoolYear.sequenceNumber LIMIT 0, 1) AS rollGroup FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE dateStart>=:startDateFrom AND dateStart<=:startDateTo AND status='Full' ORDER BY dateStart, surname, preferredName";
                    } else {
                        $data = array('startDateFrom' => dateConvert($guid, $startDateFrom), 'startDateTo' => dateConvert($guid, $startDateTo));
                        $sql = "SELECT DISTINCT gibbonPerson.gibbonPersonID, surname, preferredName, username, dateStart, lastSchool, (SELECT nameShort FROM gibbonRollGroup JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) JOIN gibbonSchoolYear ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID ORDER BY gibbonSchoolYear.sequenceNumber LIMIT 0, 1) AS rollGroup FROM gibbonPerson WHERE dateStart>=:startDateFrom AND dateStart<=:startDateTo AND status='Full' ORDER BY dateStart, surname, preferredName";
                    }
                } elseif ($type == 'Current School Year') {
                    $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                    $sql = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName, gibbonRollGroup.nameShort AS rollGroup, username, dateStart, lastSchool FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND status='Full' ORDER BY rollGroup, surname, preferredName";
                }
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }
            if ($result->rowCount() > 0) {
                if ($type == 'Current School Year') {
                    echo "<table cellspacing='0' style='width: 100%'>";
                    echo "<tr class='head'>";
                    echo '<th>';
                    echo __($guid, 'Count');
                    echo '</th>';
                    echo '<th>';
                    echo __($guid, 'Name');
                    echo '</th>';
                    echo '<th>';
                    echo __($guid, 'Roll Group');
                    echo '</th>';
                    echo '<th>';
                    echo __($guid, 'Username');
                    echo '</th>';
                    echo '<th>';
                    echo __($guid, 'Start Date');
                    echo '</th>';
                    echo '<th>';
                    echo __($guid, 'Last School');
                    echo '</th>';
                    echo '<th>';
                    echo __($guid, 'Parents');
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
                        try {
                            $data2 = array('gibbonSchoolYearID' => getPreviousSchoolYearID($_SESSION[$guid]['gibbonSchoolYearID'], $connection2), 'gibbonPersonID' => $row['gibbonPersonID']);
                            $sql2 = "SELECT surname, preferredName, gibbonRollGroup.nameShort AS rollGroup, username FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND status='Full' AND gibbonPerson.gibbonPersonID=:gibbonPersonID ORDER BY rollGroup, surname, preferredName";
                            $result2 = $connection2->prepare($sql2);
                            $result2->execute($data2);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }
                        if ($result2->rowCount() == 0) {
                            ++$count;
                            echo "<tr class=$rowNum>";
                            echo '<td>';
                            echo $count;
                            echo '</td>';
                            echo '<td>';
                            echo formatName('', $row['preferredName'], $row['surname'], 'Student', true);
                            echo '</td>';
                            echo '<td>';
                            echo $row['rollGroup'];
                            echo '</td>';
                            echo '<td>';
                            echo $row['username'];
                            echo '</td>';
                            echo '<td>';
                            echo dateConvertBack($guid, $row['dateStart']);
                            echo '</td>';
                            echo '<td>';
                            echo $row['lastSchool'];
                            echo '</td>';
                            echo '<td>';
                            try {
                                $dataFamily = array('gibbonPersonID' => $row['gibbonPersonID']);
                                $sqlFamily = 'SELECT gibbonFamilyID FROM gibbonFamilyChild WHERE gibbonPersonID=:gibbonPersonID';
                                $resultFamily = $connection2->prepare($sqlFamily);
                                $resultFamily->execute($dataFamily);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }
                            while ($rowFamily = $resultFamily->fetch()) {
                                try {
                                    $dataFamily2 = array('gibbonFamilyID' => $rowFamily['gibbonFamilyID']);
                                    $sqlFamily2 = 'SELECT gibbonPerson.* FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonPerson.gibbonPersonID=gibbonFamilyAdult.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID ORDER BY contactPriority, surname, preferredName';
                                    $resultFamily2 = $connection2->prepare($sqlFamily2);
                                    $resultFamily2->execute($dataFamily2);
                                } catch (PDOException $e) {
                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                }
                                while ($rowFamily2 = $resultFamily2->fetch()) {
                                    echo '<u>'.formatName($rowFamily2['title'], $rowFamily2['preferredName'], $rowFamily2['surname'], 'Parent').'</u><br/>';
                                    $numbers = 0;
                                    for ($i = 1; $i < 5; ++$i) {
                                        if ($rowFamily2['phone'.$i] != '') {
                                            if ($rowFamily2['phone'.$i.'Type'] != '') {
                                                echo '<i>'.$rowFamily2['phone'.$i.'Type'].':</i> ';
                                            }
                                            if ($rowFamily2['phone'.$i.'CountryCode'] != '') {
                                                echo '+'.$rowFamily2['phone'.$i.'CountryCode'].' ';
                                            }
                                            echo $rowFamily2['phone'.$i].'<br/>';
                                            ++$numbers;
                                        }
                                    }
                                    if ($rowFamily2['citizenship1'] != '' or $rowFamily2['citizenship1Passport'] != '') {
                                        echo '<i>Passport</i>: '.$rowFamily2['citizenship1'].' '.$rowFamily2['citizenship1Passport'].'<br/>';
                                    }
                                    if ($rowFamily2['nationalIDCardNumber'] != '') {
                                        if ($_SESSION[$guid]['country'] == '') {
                                            echo '<i>National ID Card</i>: ';
                                        } else {
                                            echo '<i>'.$_SESSION[$guid]['country'].' ID Card</i>: ';
                                        }
                                        echo $rowFamily2['nationalIDCardNumber'].'<br/>';
                                    }
                                }
                            }
                            echo '</td>';
                            echo '</tr>';
                        }
                    }
                    echo '</table>';
                } elseif ($type == 'Date Range') {
                    echo "<table cellspacing='0' style='width: 100%'>";
                    echo "<tr class='head'>";
                    echo '<th>';
                    echo 'Count';
                    echo '</th>';
                    echo '<th>';
                    echo __($guid, 'Name');
                    echo '</th>';
                    echo '<th>';
                    echo __($guid, 'Roll Group');
                    echo '</th>';
                    echo '<th>';
                    echo __($guid, 'Username');
                    echo '</th>';
                    echo '<th>';
                    echo 'Start Date';
                    echo '</th>';
                    echo '<th>';
                    echo __($guid, 'Last School');
                    echo '</th>';
                    echo '<th>';
                    echo __($guid, 'Parents');
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
                        echo "<tr class=$rowNum>";
                        echo '<td>';
                        echo $count;
                        echo '</td>';
                        echo '<td>';
                        echo formatName('', $row['preferredName'], $row['surname'], 'Student', true);
                        echo '</td>';
                        echo '<td>';
                        echo $row['rollGroup'];
                        echo '</td>';
                        echo '<td>';
                        echo $row['username'];
                        echo '</td>';
                        echo '<td>';
                        echo dateConvertBack($guid, $row['dateStart']);
                        echo '</td>';
                        echo '<td>';
                        echo $row['lastSchool'];
                        echo '</td>';
                        echo '<td>';
                        try {
                            $dataFamily = array('gibbonPersonID' => $row['gibbonPersonID']);
                            $sqlFamily = 'SELECT gibbonFamilyID FROM gibbonFamilyChild WHERE gibbonPersonID=:gibbonPersonID';
                            $resultFamily = $connection2->prepare($sqlFamily);
                            $resultFamily->execute($dataFamily);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }
                        while ($rowFamily = $resultFamily->fetch()) {
                            try {
                                $dataFamily2 = array('gibbonFamilyID' => $rowFamily['gibbonFamilyID']);
                                $sqlFamily2 = 'SELECT gibbonPerson.* FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonPerson.gibbonPersonID=gibbonFamilyAdult.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID ORDER BY contactPriority, surname, preferredName';
                                $resultFamily2 = $connection2->prepare($sqlFamily2);
                                $resultFamily2->execute($dataFamily2);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }
                            while ($rowFamily2 = $resultFamily2->fetch()) {
                                echo '<u>'.formatName($rowFamily2['title'], $rowFamily2['preferredName'], $rowFamily2['surname'], 'Parent').'</u><br/>';
                                $numbers = 0;
                                for ($i = 1; $i < 5; ++$i) {
                                    if ($rowFamily2['phone'.$i] != '') {
                                        if ($rowFamily2['phone'.$i.'Type'] != '') {
                                            echo '<i>'.$rowFamily2['phone'.$i.'Type'].':</i> ';
                                        }
                                        if ($rowFamily2['phone'.$i.'CountryCode'] != '') {
                                            echo '+'.$rowFamily2['phone'.$i.'CountryCode'].' ';
                                        }
                                        echo $rowFamily2['phone'.$i].'<br/>';
                                        ++$numbers;
                                    }
                                }
                                if ($rowFamily2['citizenship1'] != '' or $rowFamily2['citizenship1Passport'] != '') {
                                    echo '<i>Passport</i>: '.$rowFamily2['citizenship1'].' '.$rowFamily2['citizenship1Passport'].'<br/>';
                                }
                                if ($rowFamily2['nationalIDCardNumber'] != '') {
                                    if ($_SESSION[$guid]['country'] == '') {
                                        echo '<i>National ID Card</i>: ';
                                    } else {
                                        echo '<i>'.$_SESSION[$guid]['country'].' ID Card</i>: ';
                                    }
                                    echo $rowFamily2['nationalIDCardNumber'].'<br/>';
                                }
                            }
                        }
                        echo '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                }
            } else {
                echo "<div class='warning'>";
                echo __($guid, 'There are no records to display.');
                echo '</div>';
            }
        }
    }
}
?>
