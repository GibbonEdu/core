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

if (isActionAccessible($guid, $connection2, '/modules/Attendance/report_summary_byDate.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Attendance Summary by Date').'</div>';
    echo '</div>';

    echo '<h2>';
    echo __($guid, 'Choose Date');
    echo '</h2>';

    $dateEnd = (isset($_POST['dateEnd']))? dateConvert($guid, $_POST['dateEnd']) : date('Y-m-d');
    $dateStart = (isset($_POST['dateStart']))? dateConvert($guid, $_POST['dateStart']) : date('Y-m-d', strtotime( $dateEnd.' -1 month') );

    $group = !empty($_POST['group'])? $_POST['group'] : '';
    $sort = !empty($_POST['sort'])? $_POST['sort'] : 'surname, preferredName';

    $gibbonCourseClassID = (isset($_POST["gibbonCourseClassID"]))? $_POST["gibbonCourseClassID"] : 0;
    $gibbonRollGroupID = (isset($_POST["gibbonRollGroupID"]))? $_POST["gibbonRollGroupID"] : 0;

    
    require_once './modules/Attendance/src/attendanceView.php';
    $attendance = new Module\Attendance\attendanceView(NULL, NULL, $pdo);

    ?>
	
	<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL']?>/index.php?q=/modules/<?php echo $_SESSION[$guid]['module'] ?>/report_summary_byDate.php">
		<table class='smallIntBorder fullWidth' cellspacing='0'>	
			<tr>
				<td style='width: 275px'> 
					<b><?php echo __($guid, 'Start Date') ?> *</b><br/>
					<span class="emphasis small"><?php echo __($guid, 'Format:').' ';
					if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
						echo 'dd/mm/yyyy';
					} else {
						echo $_SESSION[$guid]['i18n']['dateFormat'];
					}
					?></span>
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
                <td>
                    <b><?php echo __($guid, 'Group By') ?></b><br/>
                </td>
                <td class="right">
                    <select id="group" name="group" class="standardWidth">
                    <option value="" <?php if ($group == '') { echo 'selected'; } ?>><?php echo __($guid, 'Please select...'); ?></option>
                        <option value="all" <?php if ($group == 'all') { echo 'selected'; } ?>><?php echo __($guid, 'All Students'); ?></option>
                        <?php if (getSettingByScope($connection2, 'Attendance', 'attendanceEnableByClass') == 'Y') : ?>
                        <option value="class" <?php if ($group == 'class') { echo 'selected'; } ?>><?php echo __($guid, 'Class'); ?></option>
                        <?php endif; ?>
                        <option value="rollGroup" <?php if ($group == 'rollGroup') { echo 'selected'; } ?>><?php echo __($guid, 'Roll Group'); ?></option>
                    </select>
                </td>
            </tr>
            <script type="text/javascript">
                /* Show/Hide Control */
                $(document).ready(function(){
                     $("#group").change(function(){
                        if ($('#group').val()=='class' ) {
                            $("#groupByClass").slideDown("fast", $("#groupByClass").css("display","table-row")); 
                        } else {
                            $("#groupByClass").css("display","none");
                        }

                        if ($('#group').val()=='rollGroup' ) {
                            $("#groupByRollGroup").slideDown("fast", $("#groupByRollGroup").css("display","table-row")); 
                        } else {
                            $("#groupByRollGroup").css("display","none");
                        }

                     });
                });
            </script>
            <tr id="groupByClass" <?php if ($group != 'class') { echo "style='display: none'"; } ?>>
                <td> 
                    <b><?php echo __($guid, 'Class') ?> *</b><br/>
                    <span class="emphasis small"></span>
                </td>
                <td class="right">
                    <select style="width: 302px" name="gibbonCourseClassID">
                        <?php
                        echo "<option value=''>" . __($guid, 'Please select...') . "</option>" ;

                        try {
                            $dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
                            $sqlSelect="SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClass.attendance='Y' ORDER BY gibbonCourse.nameShort, gibbonCourseClass.nameShort" ;
                            $resultSelect=$connection2->prepare($sqlSelect);
                            $resultSelect->execute($dataSelect);
                        }
                        catch(PDOException $e) { 
                            print "<div class='error'>" . $e->getMessage() . "</div>" ; 
                        }
                        

                        while ($rowSelect=$resultSelect->fetch()) {
                            if ($gibbonCourseClassID==$rowSelect["gibbonCourseClassID"]) {
                                print "<option selected value='" . $rowSelect["gibbonCourseClassID"] . "'>" . htmlPrep($rowSelect["course"]) . "." . htmlPrep($rowSelect["class"]) . "</option>" ;
                            }
                            else {
                                print "<option value='" . $rowSelect["gibbonCourseClassID"] . "'>" . htmlPrep($rowSelect["course"]) . "." . htmlPrep($rowSelect["class"]) . "</option>" ;
                            }                       
                        }

                        ?>              
                    </select>
                </td>
            </tr>
            <tr id="groupByRollGroup" <?php if ($group != 'rollGroup') { echo "style='display: none'"; } ?>>
                <td> 
                    <b><?php echo __($guid, 'Roll Group') ?> *</b><br/>
                    <span class="emphasis small"></span>
                </td>
                <td class="right">
                    <select class="standardWidth" name="gibbonRollGroupID">
                        <?php
                        echo "<option value=''>" . __($guid, 'Please select...') . "</option>" ;
                        try {
                            $dataSelect = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                            $sqlSelect = "SELECT * FROM gibbonRollGroup WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonRollGroup.attendance = 'Y' ORDER BY LENGTH(name), name";
                            $resultSelect = $connection2->prepare($sqlSelect);
                            $resultSelect->execute($dataSelect);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }

                        while ($rowSelect = $resultSelect->fetch()) {
                            if ($gibbonRollGroupID == $rowSelect['gibbonRollGroupID']) {
                                echo "<option selected value='".$rowSelect['gibbonRollGroupID']."'>".htmlPrep($rowSelect['name']).'</option>';
                            } else {
                                echo "<option value='".$rowSelect['gibbonRollGroupID']."'>".htmlPrep($rowSelect['name']).'</option>';
                            }
                        }
                        ?>              
                    </select>
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
                    </select>
                </td>
            </tr>
			<tr>
				<td colspan=2 class="right">
					<input type="hidden" name="address" value="/modules/<?php echo $_SESSION[$guid]['module'] ?>/report_graph_byType.php">
					<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
				</td>
			</tr>
		</table>
	</form>
	<?php

    if ($dateStart != '' && $group != '') {
        echo '<h2>';
        echo __($guid, 'Report Data');
        echo '</h2>';

        try {
            $dataSchoolDays = array( 'dateStart' => $dateStart, 'dateEnd' => $dateEnd );
            $sqlSchoolDays = "SELECT COUNT(DISTINCT date) as total, COUNT(DISTINCT CASE WHEN date>=:dateStart AND date <=:dateEnd THEN date END) as dateRange FROM gibbonAttendanceLogPerson, gibbonSchoolYearTerm, gibbonSchoolYear WHERE date>=gibbonSchoolYearTerm.firstDay AND date <= gibbonSchoolYearTerm.lastDay AND date <= NOW() AND gibbonSchoolYearTerm.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID AND gibbonSchoolYear.gibbonSchoolYearID=(SELECT gibbonSchoolYearID FROM gibbonSchoolYear WHERE firstDay<=:dateEnd AND lastDay>=:dateStart)";

            $resultSchoolDays = $connection2->prepare($sqlSchoolDays);
            $resultSchoolDays->execute($dataSchoolDays);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }
        $schoolDayCounts = $resultSchoolDays->fetch();

        echo '<p style="color:#666;">';
            echo '<strong>' . __($guid, 'Total number of school days to date:').' '.$schoolDayCounts['total'].'</strong><br/>';
            echo __($guid, 'Total number of school days in date range:').' '.$schoolDayCounts['dateRange'];
        echo '</p>';

        //Produce array of attendance data
        try {
            $data = array('dateStart' => $dateStart, 'dateEnd' => $dateEnd);

            if ($group == 'all') {
                $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonRollGroup.nameShort AS rollGroup, surname, preferredName, COUNT(DISTINCT CASE WHEN gibbonAttendanceCode.direction='In' THEN date END) AS present, COUNT(CASE WHEN gibbonAttendanceCode.direction='Out' AND NOT reason='Unexcused' THEN date END) AS excused, COUNT(CASE WHEN gibbonAttendanceCode.direction='Out' AND reason='Unexcused' THEN date END) AS unexcused, COUNT(CASE WHEN gibbonAttendanceCode.direction='In' AND scope='Offsite' THEN date END) AS offsite, COUNT(CASE WHEN scope='Onsite - Late' THEN date END) AS late, COUNT(CASE WHEN scope='Offsite - Left' THEN date END) AS 'left' FROM gibbonAttendanceLogPerson JOIN gibbonAttendanceCode ON (gibbonAttendanceLogPerson.type=gibbonAttendanceCode.name) JOIN gibbonPerson ON (gibbonAttendanceLogPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE date>=:dateStart AND date<=:dateEnd AND gibbonStudentEnrolment.gibbonSchoolYearID=(SELECT gibbonSchoolYearID FROM gibbonSchoolYear WHERE firstDay<=:dateEnd AND lastDay>=:dateStart) GROUP BY gibbonAttendanceLogPerson.gibbonPersonID";
            }
            else if ($group == 'class') {
                $data['gibbonCourseClassID'] = $gibbonCourseClassID;
                $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonRollGroup.nameShort AS rollGroup, surname, preferredName, COUNT(DISTINCT CASE WHEN gibbonAttendanceCode.direction='In' THEN date END) AS present, COUNT(CASE WHEN gibbonAttendanceCode.direction='Out' AND NOT reason='Unexcused' THEN date END) AS excused, COUNT(CASE WHEN gibbonAttendanceCode.direction='Out' AND reason='Unexcused' THEN date END) AS unexcused, COUNT(CASE WHEN gibbonAttendanceCode.direction='In' AND scope='Offsite' THEN date END) AS offsite, COUNT(CASE WHEN scope='Onsite - Late' THEN date END) AS late, COUNT(CASE WHEN scope='Offsite - Left' THEN date END) AS 'left' FROM gibbonAttendanceLogPerson JOIN gibbonAttendanceCode ON (gibbonAttendanceLogPerson.type=gibbonAttendanceCode.name) JOIN gibbonPerson ON (gibbonAttendanceLogPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE date>=:dateStart AND date<=:dateEnd AND gibbonStudentEnrolment.gibbonSchoolYearID=(SELECT gibbonSchoolYearID FROM gibbonSchoolYear WHERE firstDay<=:dateEnd AND lastDay>=:dateStart) AND gibbonAttendanceLogPerson.gibbonCourseClassID=:gibbonCourseClassID GROUP BY gibbonAttendanceLogPerson.gibbonPersonID";
            }
            else if ($group == 'rollGroup') {
                $data['gibbonRollGroupID'] = $gibbonRollGroupID;
                $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonRollGroup.nameShort AS rollGroup, surname, preferredName, COUNT(DISTINCT CASE WHEN gibbonAttendanceCode.direction='In' THEN date END) AS present, COUNT(CASE WHEN gibbonAttendanceCode.direction='Out' AND NOT reason='Unexcused' THEN date END) AS excused, COUNT(CASE WHEN gibbonAttendanceCode.direction='Out' AND reason='Unexcused' THEN date END) AS unexcused, COUNT(CASE WHEN gibbonAttendanceCode.direction='In' AND scope='Offsite' THEN date END) AS offsite, COUNT(CASE WHEN scope='Onsite - Late' THEN date END) AS late, COUNT(CASE WHEN scope='Offsite - Left' THEN date END) AS 'left' FROM gibbonAttendanceLogPerson JOIN gibbonAttendanceCode ON (gibbonAttendanceLogPerson.type=gibbonAttendanceCode.name) JOIN gibbonPerson ON (gibbonAttendanceLogPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE date>=:dateStart AND date<=:dateEnd AND gibbonStudentEnrolment.gibbonSchoolYearID=(SELECT gibbonSchoolYearID FROM gibbonSchoolYear WHERE firstDay<=:dateEnd AND lastDay>=:dateStart) AND gibbonStudentEnrolment.gibbonRollGroupID=:gibbonRollGroupID AND gibbonAttendanceLogPerson.gibbonCourseClassID=0 GROUP BY gibbonAttendanceLogPerson.gibbonPersonID";
            }

            if ( empty($sort) ) {
                $sort = 'surname, preferredName';
            }
            
            if ($sort == 'rollGroup') {
                $sql .= ' ORDER BY LENGTH(rollGroup), rollGroup, surname, preferredName';
            } else {
                $sql .= ' ORDER BY ' . $sort;
            }

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

            echo "<div class='linkTop'>";
            echo "<a target='_blank' href='".$_SESSION[$guid]['absoluteURL'].'/report.php?q=/modules/'.$_SESSION[$guid]['module'].'/report_summary_byDate_print.php&dateStart='.dateConvertBack($guid, $dateStart)."&sort=" . $sort . "'>".__($guid, 'Print')."<img style='margin-left: 5px' title='".__($guid, 'Print')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/print.png'/></a>";
            echo '</div>';

            echo '<table cellspacing="0" class="fullWidth colorOddEven" >';

            echo "<tr class='head'>";
            echo '<th style="width:80px" rowspan=2>';
            echo __($guid, 'Roll Group');
            echo '</th>';
            echo '<th rowspan=2>';
            echo __($guid, 'Name');
            echo '</th>';
            echo '<th colspan=3 style="border-left: 1px solid #666;text-align:center;">';
            echo __($guid, 'IN');
            echo '</th>';
            echo '<th colspan=3 style="border-left: 1px solid #666;text-align:center;">';
            echo __($guid, 'OUT');
            echo '</th>';
            echo '</tr>';


            echo "<tr class='head'>";
            echo '<th class="verticalHeader" style="border-left: 1px solid #666;">';
                echo '<div class="verticalText">';
                echo __($guid, 'Present');
                echo '</div>';
            echo '</th>';
            echo '<th class="verticalHeader">';
                echo '<div class="verticalText">';
                echo __($guid, 'Late');
                echo '</div>';
            echo '</th>';
            echo '<th class="verticalHeader">';
                echo '<div class="verticalText">';
                echo __($guid, 'Offsite');
                echo '</div>';
            echo '</th>';
            echo '<th class="verticalHeader" style="border-left: 1px solid #666;">';
                echo '<div class="verticalText">';
                echo __($guid, 'Absent').'<br/><span class="small emphasis">'.__($guid, 'Excused').'</span>';
                echo '</div>';
            echo '</th>';
            echo '<th class="verticalHeader">';
                echo '<div class="verticalText">';
                echo __($guid, 'Absent').'<br/><span class="small emphasis">'.__($guid, 'Unexcused').'</span>';
                echo '</div>';
            echo '</th>';
            echo '<th class="verticalHeader">';
                echo '<div class="verticalText">';
                echo __($guid, 'Left');
                echo '</div>';
            echo '</th>';
            echo '</tr>';


            while ($row = $result->fetch()) {


                // ROW
                echo "<tr>";
                echo '<td>';
                    echo $row['rollGroup'];
                echo '</td>';
                echo '<td>';
                    echo '<a href="index.php?q=/modules/Attendance/report_studentHistory.php&gibbonPersonID='.$row['gibbonPersonID'].'" target="_blank">';
                    echo formatName('', $row['preferredName'], $row['surname'], 'Student', ($sort != 'preferredName') );
                    echo '</a>';
                echo '</td>';

                echo '<td class="center" style="border-left: 1px solid #666;">';
                    echo $row['present'];
                echo '</td>';
                echo '<td class="center">';
                    echo $row['late'];
                echo '</td>';
                echo '<td class="center">';
                    echo $row['offsite'];
                echo '</td>';
                echo '<td class="center" style="border-left: 1px solid #666;">';
                    echo $row['excused'];
                echo '</td>';
                echo '<td class="center">';
                    echo $row['unexcused'];
                echo '</td>';
                echo '<td class="center">';
                    echo $row['left'];
                echo '</td>';
                echo '</tr>';
                
            }
            if ($result->rowCount() == 0) {
                echo "<tr>";
                echo '<td colspan=5>';
                echo __($guid, 'All students are present.');
                echo '</td>';
                echo '</tr>';
            }
            echo '</table>';
            
        
        }
    }
}
?>