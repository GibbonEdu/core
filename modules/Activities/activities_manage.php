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

if (isActionAccessible($guid, $connection2, '/modules/Activities/activities_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Set returnTo point for upcoming pages
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Manage Activities').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    echo '<h2>';
    echo __($guid, 'Search');
    echo '</h2>';

    $search = null;
    if (isset($_GET['search'])) {
        $search = $_GET['search'];
    }

    $paymentOn = true;
    if (getSettingByScope($connection2, 'Activities', 'payment') == 'None' or getSettingByScope($connection2, 'Activities', 'payment') == 'Single') {
        $paymentOn = false;
    }

    ?>
	<form method="get" action="<?php echo $_SESSION[$guid]['absoluteURL']?>/index.php">
		<table class='noIntBorder' cellspacing='0' style="width: 100%">
			<tr><td style="width: 30%"></td><td></td></tr>
			<tr>
				<td>
					<b><?php echo __($guid, 'Search For Activity') ?></b><br/>
					<span class="emphasis small"><?php echo __($guid, 'Activity name.') ?></span>
				</td>
				<td class="right">
					<input name="search" id="search" maxlength=20 value="<?php echo $search ?>" type="text" class="standardWidth">
				</td>
			</tr>
			<tr>
				<td colspan=2 class="right">
					<input type="hidden" name="q" value="/modules/<?php echo $_SESSION[$guid]['module'] ?>/activities_manage.php">
					<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
					<?php
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/activities_manage.php'>".__($guid, 'Clear Search').'</a>';?>
					<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
				</td>
			</tr>
		</table>
	</form>
	<?php

    echo '<h2>';
    echo __($guid, 'Activities');
    echo '</h2>';

    //Set pagination variable
    $page = 1;
    if (isset($_GET['page'])) {
        $page = $_GET['page'];
    }
    if ((!is_numeric($page)) or $page < 1) {
        $page = 1;
    }

    //Should we show date as term or date?
    $dateType = getSettingByScope($connection2, 'Activities', 'dateType');
    if ($dateType != 'Date') {
        if ($search == '') {
            $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
            $sql = "SELECT gibbonActivity.*, (SELECT COUNT(*) FROM gibbonActivityStudent JOIN gibbonPerson ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID AND gibbonActivityStudent.status='Waiting List' AND gibbonPerson.status='Full') AS waiting FROM gibbonActivity WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY gibbonSchoolYearTermIDList, name";
        } else {
            $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'search' => "%$search%");
            $sql = "SELECT gibbonActivity.*, (SELECT COUNT(*) FROM gibbonActivityStudent JOIN gibbonPerson ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID AND gibbonActivityStudent.status='Waiting List' AND gibbonPerson.status='Full') AS waiting FROM gibbonActivity WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND name LIKE :search ORDER BY gibbonSchoolYearTermIDList, name";
        }
    } else {
        if ($search == '') {
            $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
            $sql = "SELECT gibbonActivity.*, (SELECT COUNT(*) FROM gibbonActivityStudent JOIN gibbonPerson ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID AND gibbonActivityStudent.status='Waiting List' AND gibbonPerson.status='Full') AS waiting FROM gibbonActivity WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY programStart DESC, name";
        } else {
            $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'search' => "%$search%");
            $sql = "SELECT gibbonActivity.*, (SELECT COUNT(*) FROM gibbonActivityStudent JOIN gibbonPerson ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID AND gibbonActivityStudent.status='Waiting List' AND gibbonPerson.status='Full') AS waiting FROM gibbonActivity WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND name LIKE :search ORDER BY programStart DESC, name";
        }
    }
    $sqlPage = $sql.' LIMIT '.$_SESSION[$guid]['pagination'].' OFFSET '.(($page - 1) * $_SESSION[$guid]['pagination']);
    try {
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>";
        echo __($guid, 'Your request failed due to a database error.');
        echo '</div>';
    }

    if ($result) {
        echo "<div class='linkTop'>";
        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/activities_manage_add.php&search='.$search."'>".__($guid, 'Add')."<img style='margin-left: 5px' title='".__($guid, 'Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
        echo '</div>';

        if ($result->rowCount() < 1) {
            echo "<div class='error'>";
            echo __($guid, 'There are no records to display.');
            echo '</div>';
        } else {
            if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
                printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'top');
            }

            echo "<form onsubmit='return confirm(\"".__($guid, 'Are you sure you wish to process this action? It cannot be undone.')."\")' method='post' action='".$_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/activities_manageProcessBulk.php'>";
            echo "<fieldset style='border: none'>";
            echo "<div class='linkTop' style='height: 27px'>"; ?>
                    <input style='margin-top: 0px; float: right' type='submit' value='<?php echo __($guid, 'Go') ?>'>

                    <div id="optionRow" style="display: none;">
                        <select style="width: 182px" name="gibbonSchoolYearIDCopyTo" id="gibbonSchoolYearIDCopyTo">
                            <?php
                            print "<option value='Please select...'>" . _('Please select...') . "</option>" ;

                            try {
                                $dataSelect = array();
                                $sqlSelect = "SELECT gibbonSchoolYear.name AS year, gibbonSchoolYearID FROM gibbonSchoolYear WHERE (status='Upcoming' OR status='Current') ORDER BY sequenceNumber LIMIT 0, 2";
                                $resultSelect=$connection2->prepare($sqlSelect);
                                $resultSelect->execute($dataSelect);
                            }
                            catch(PDOException $e) {
                                print "<div class='error'>" . $e->getMessage() . "</div>" ;
                            }
                            $yearCurrent = '';
                            $yearLast = '';
                            while ($rowSelect=$resultSelect->fetch()) {
                                print "<option value='" . $rowSelect["gibbonSchoolYearID"] . "'>" . htmlPrep($rowSelect["year"]) . "</option>" ;
                            }
                            ?>
                        </select>
                        <script type="text/javascript">
                            var gibbonSchoolYearIDCopyTo=new LiveValidation('gibbonSchoolYearIDCopyTo');
                            gibbonSchoolYearIDCopyTo.add(Validate.Exclusion, { within: ['<?php echo __($guid, 'Please select...') ?>'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
                        </script>
                    </div>

                    <select name="action" id="action" style='width:220px; float: right; margin-right: 1px;'>
                        <option value="Select action"><?php echo __($guid, 'Select action') ?></option>
                        <option value="Duplicate"><?php echo __($guid, 'Duplicate') ?></option>
                        <option value="DuplicateParticipants"><?php echo __($guid, 'Duplicate With Participants') ?></option>
                        <option value="Delete"><?php echo __($guid, 'Delete') ?></option>
                    </select>
                    <script type="text/javascript">
                        var action=new LiveValidation('action');
                        action.add(Validate.Exclusion, { within: ['<?php echo __($guid, 'Select action') ?>'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});

                        $(document).ready(function(){
                            $('#action').change(function () {
                                if ($(this).val() == 'Duplicate' || $(this).val() == 'DuplicateParticipants') {
                                    $("#optionRow").slideDown("fast", $("#optionRow").css("display","block"));
                                    gibbonSchoolYearIDCopyTo.enable();
                                } else {
                                    $("#optionRow").css("display","none");
                                    gibbonSchoolYearIDCopyTo.disable();
                                }
                            });
                        });

                    </script>
                    <?php
            echo '</div>';

            echo "<table cellspacing='0' style='width: 100%'>";
            echo "<tr class='head'>";
            echo '<th>';
            echo __($guid, 'Activity');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Days');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Years');
            echo '</th>';
            echo '<th>';
            if ($dateType != 'Date') {
                echo __($guid, 'Term');
            } else {
                echo __($guid, 'Dates');
            }
            echo '</th>';
            if ($paymentOn) {
                echo '<th>';
                echo __($guid, 'Cost').'<br/>';
                echo "<span style='font-style: italic; font-size: 85%'>".$_SESSION[$guid]['currency'].'</span>';
                echo '</th>';
            }
            echo '<th>';
            echo __($guid, 'Provider');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Waiting');
            echo '</th>';
            echo "<th style='width: 100px'>";
            echo __($guid, 'Actions');
            echo '</th>';
            echo '<th style=\'text-align: center\'>'; ?>
            <script type="text/javascript">
                $(function () {
                    $('.checkall').click(function () {
                        $(this).parents('fieldset:eq(0)').find(':checkbox').attr('checked', this.checked);
                    });
                });
            </script>
            <?php
            echo "<input type='checkbox' class='checkall'>";
            echo '</th>';
            echo '</tr>';

            $count = 0;
            $rowNum = 'odd';

            try {
                $resultPage = $connection2->prepare($sqlPage);
                $resultPage->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>";
                echo __($guid, 'Your request failed due to a database error.');
                echo '</div>';
            }

            if ($result) {
                while ($row = $resultPage->fetch()) {
                    if ($count % 2 == 0) {
                        $rowNum = 'even';
                    } else {
                        $rowNum = 'odd';
                    }

                    if ($row['active'] == 'N') {
                        $rowNum = 'error';
                    }

                    //COLOR ROW BY STATUS!
                    echo "<tr class=$rowNum>";
                    echo '<td>';
                    echo $row['name'].'<br/>';
                    echo '<i>'.trim($row['type']).'</i>';
                    echo '</td>';
                    echo '<td>';
                    try {
                        $dataSlots = array('gibbonActivityID' => $row['gibbonActivityID']);
                        $sqlSlots = 'SELECT DISTINCT nameShort, sequenceNumber FROM gibbonActivitySlot JOIN gibbonDaysOfWeek ON (gibbonActivitySlot.gibbonDaysOfWeekID=gibbonDaysOfWeek.gibbonDaysOfWeekID) WHERE gibbonActivityID=:gibbonActivityID ORDER BY sequenceNumber';
                        $resultSlots = $connection2->prepare($sqlSlots);
                        $resultSlots->execute($dataSlots);
                    } catch (PDOException $e) {
                    }

                    $count2 = 0;
                    while ($rowSlots = $resultSlots->fetch()) {
                        if ($count2 > 0) {
                            echo ', ';
                        }
                        echo __($guid, $rowSlots['nameShort']);
                        ++$count2;
                    }
                    if ($count2 == 0) {
                        echo '<i>'.__($guid, 'None').'</i>';
                    }
                    echo '</td>';
                    echo '<td>';
                    echo getYearGroupsFromIDList($guid, $connection2, $row['gibbonYearGroupIDList']);
                    echo '</td>';
                    echo '<td>';
                    if ($dateType != 'Date') {
                        $terms = getTerms($connection2, $_SESSION[$guid]['gibbonSchoolYearID'], true);
                        $termList = '';
                        for ($i = 0; $i < count($terms); $i = $i + 2) {
                            if (is_numeric(strpos($row['gibbonSchoolYearTermIDList'], $terms[$i]))) {
                                $termList .= $terms[($i + 1)].'<br/>';
                            }
                        }
                        echo $termList;
                    } else {
                        if (substr($row['programStart'], 0, 4) == substr($row['programEnd'], 0, 4)) {
                            if (substr($row['programStart'], 5, 2) == substr($row['programEnd'], 5, 2)) {
                                echo date('F', mktime(0, 0, 0, substr($row['programStart'], 5, 2))).' '.substr($row['programStart'], 0, 4);
                            } else {
                                echo date('F', mktime(0, 0, 0, substr($row['programStart'], 5, 2))).' - '.date('F', mktime(0, 0, 0, substr($row['programEnd'], 5, 2))).' '.substr($row['programStart'], 0, 4);
                            }
                        } else {
                            echo date('F', mktime(0, 0, 0, substr($row['programStart'], 5, 2))).' '.substr($row['programStart'], 0, 4).' - '.date('F', mktime(0, 0, 0, substr($row['programEnd'], 5, 2))).' '.substr($row['programEnd'], 0, 4);
                        }
                    }
                    echo '</td>';
                    if ($paymentOn) {
                        echo '<td>';
                        if ($row['payment'] == 0) {
                            echo '<i>'.__($guid, 'None').'</i>';
                        } else {
                            if (substr($_SESSION[$guid]['currency'], 4) != '') {
                                echo substr($_SESSION[$guid]['currency'], 4);
                            }
                            echo number_format($row['payment'], 2);
                        }
                        echo '</td>';
                    }
                    echo '<td>';
                    if ($row['provider'] == 'School') {
                        echo $_SESSION[$guid]['organisationNameShort'];
                    } else {
                        echo __($guid, 'External');
                    }
                    echo '</td>';
                    echo '<td>';
                    echo $row['waiting'];
                    echo '</td>';
                    echo '<td>';
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/activities_manage_edit.php&gibbonActivityID='.$row['gibbonActivityID'].'&search='.$search."'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/activities_manage_delete.php&gibbonActivityID='.$row['gibbonActivityID'].'&search='.$search."'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a> ";
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/activities_manage_enrolment.php&gibbonActivityID='.$row['gibbonActivityID'].'&search='.$search."'><img title='".__($guid, 'Enrolment')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/attendance.png'/></a> ";
                    echo '</td>';
                    echo '<td>';
                    echo "<input name='gibbonActivityID-$count' value='".$row['gibbonActivityID']."' type='hidden'>";
                    echo "<input type='checkbox' name='check-$count' id='check-$count'>";
                    echo '</td>';
                    echo '</tr>';

                    ++$count;
                }
            }
            echo '</table>';
            echo '</fieldset>';

            echo "<input name='count' value='$count' type='hidden'>";
            echo "<input name='address' value='".$_GET['q']."' type='hidden'>";
            echo '</form>';

            if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
                printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'bottom');
            }
        }
    }
}
?>
