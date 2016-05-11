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

if (isActionAccessible($guid, $connection2, '/modules/Planner/units.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'Your request failed because you do not have access to this action.');
    echo '</div>';
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __($guid, 'The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        echo "<div class='trail'>";
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Unit Planner').'</div>';
        echo '</div>';

        //Get Smart Workflow help message
        $category = getRoleCategory($_SESSION[$guid]['gibbonRoleIDCurrent'], $connection2);
        if ($category == 'Staff') {
            $smartWorkflowHelp = getSmartWorkflowHelp($connection2, $guid, 2);
            if ($smartWorkflowHelp != false) {
                echo $smartWorkflowHelp;
            }
        }

        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, null);
        }

        $gibbonSchoolYearID = '';
        if (isset($_GET['gibbonSchoolYearID'])) {
            $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
        }
        if ($gibbonSchoolYearID == '' or $gibbonSchoolYearID == $_SESSION[$guid]['gibbonSchoolYearID']) {
            $gibbonSchoolYearID = $_SESSION[$guid]['gibbonSchoolYearID'];
            $gibbonSchoolYearName = $_SESSION[$guid]['gibbonSchoolYearName'];
        }

        if ($gibbonSchoolYearID != $_SESSION[$guid]['gibbonSchoolYearID']) {
            try {
                $data = array('gibbonSchoolYearID' => $_GET['gibbonSchoolYearID']);
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
            } else {
                $row = $result->fetch();
                $gibbonSchoolYearID = $row['gibbonSchoolYearID'];
                $gibbonSchoolYearName = $row['name'];
            }
        }

        if ($gibbonSchoolYearID != '') {
            $gibbonCourseID = null;
            if (isset($_GET['gibbonCourseID'])) {
                $gibbonCourseID = $_GET['gibbonCourseID'];
            }
            if ($gibbonCourseID == '') {
                try {
                    if ($highestAction == 'Unit Planner_all') {
                        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
                        $sql = 'SELECT * FROM gibbonCourse WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY nameShort';
                    } elseif ($highestAction == 'Unit Planner_learningAreas') {
                        $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonSchoolYearID' => $gibbonSchoolYearID);
                        $sql = "SELECT gibbonCourseID, gibbonCourse.name, gibbonCourse.nameShort FROM gibbonCourse JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)') AND gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY gibbonCourse.nameShort";
                    }
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
                if ($result->rowCount() > 0) {
                    $row = $result->fetch();
                    $gibbonCourseID = $row['gibbonCourseID'];
                }
            }
            if ($gibbonCourseID != '') {
                try {
                    $data = array('gibbonCourseID' => $gibbonCourseID);
                    $sql = 'SELECT * FROM gibbonCourse WHERE gibbonCourseID=:gibbonCourseID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
                if ($result->rowCount() == 1) {
                    $row = $result->fetch();
                }
            }

            //Work out previous and next course with same name
            $gibbonCourseIDPrevious = '';
            $gibbonSchoolYearIDPrevious = getPreviousSchoolYearID($gibbonSchoolYearID, $connection2);
            if ($gibbonSchoolYearIDPrevious != false and isset($row['nameShort'])) {
                try {
                    $dataPrevious = array('gibbonSchoolYearID' => $gibbonSchoolYearIDPrevious, 'nameShort' => $row['nameShort']);
                    $sqlPrevious = 'SELECT * FROM gibbonCourse WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND nameShort=:nameShort';
                    $resultPrevious = $connection2->prepare($sqlPrevious);
                    $resultPrevious->execute($dataPrevious);
                } catch (PDOException $e) {
                }
                if ($resultPrevious->rowCount() == 1) {
                    $rowPrevious = $resultPrevious->fetch();
                    $gibbonCourseIDPrevious = $rowPrevious['gibbonCourseID'];
                }
            }
            $gibbonCourseIDNext = '';
            $gibbonSchoolYearIDNext = getNextSchoolYearID($gibbonSchoolYearID, $connection2);
            if ($gibbonSchoolYearIDNext != false) {
                try {
                    $dataNext = array('gibbonSchoolYearID' => $gibbonSchoolYearIDNext, 'nameShort' => $row['nameShort']);
                    $sqlNext = 'SELECT * FROM gibbonCourse WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND nameShort=:nameShort';
                    $resultNext = $connection2->prepare($sqlNext);
                    $resultNext->execute($dataNext);
                } catch (PDOException $e) {
                }
                if ($resultNext->rowCount() == 1) {
                    $rowNext = $resultNext->fetch();
                    $gibbonCourseIDNext = $rowNext['gibbonCourseID'];
                }
            }

            echo '<h2>';
            echo $gibbonSchoolYearName;
            echo '</h2>';

            echo "<div class='linkTop'>";
                //Print year picker
                if (getPreviousSchoolYearID($gibbonSchoolYearID, $connection2) != false) {
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/units.php&gibbonSchoolYearID='.getPreviousSchoolYearID($gibbonSchoolYearID, $connection2)."&gibbonCourseID=$gibbonCourseIDPrevious'>".__($guid, 'Previous Year').'</a> ';
                } else {
                    echo __($guid, 'Previous Year').' ';
                }
				echo ' | ';
				if (getNextSchoolYearID($gibbonSchoolYearID, $connection2) != false) {
					echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/units.php&gibbonSchoolYearID='.getNextSchoolYearID($gibbonSchoolYearID, $connection2)."&gibbonCourseID=$gibbonCourseIDNext'>".__($guid, 'Next Year').'</a> ';
				} else {
					echo __($guid, 'Next Year').' ';
				}
            echo '</div>';

            if ($gibbonCourseID != '') {
                try {
                    if ($highestAction == 'Unit Planner_all') {
                        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonCourseID' => $gibbonCourseID);
                        $sql = 'SELECT * FROM gibbonCourse WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseID=:gibbonCourseID';
                    } elseif ($highestAction == 'Unit Planner_learningAreas') {
                        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonCourseID' => $gibbonCourseID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                        $sql = "SELECT gibbonCourseID, gibbonCourse.name, gibbonCourse.nameShort FROM gibbonCourse JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)') AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseID=:gibbonCourseID ORDER BY gibbonCourse.nameShort";
                    }
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                if ($result->rowCount() < 1) {
                    echo "<div class='error'>";
                    echo __($guid, 'The selected record does not exist, or you do not have access to it.');
                    echo '</div>';
                } else {
                    $row = $result->fetch();

                    echo '<h4>';
                    echo $row['name'];
                    echo '</h4>';

                    //Fetch units
                    try {
                        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonCourseID' => $gibbonCourseID);
                        $sql = 'SELECT gibbonUnitID, gibbonUnit.gibbonCourseID, nameShort, gibbonUnit.name, gibbonUnit.description, active FROM gibbonUnit JOIN gibbonCourse ON gibbonUnit.gibbonCourseID=gibbonCourse.gibbonCourseID WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonUnit.gibbonCourseID=:gibbonCourseID ORDER BY ordering, name';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }

                    echo "<div class='linkTop'>";
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/units_add.php&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonCourseID=$gibbonCourseID'>".__($guid, 'Add')."<img style='margin-left: 5px' title='".__($guid, 'Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
                    echo '</div>';

                    if ($result->rowCount() < 1) {
                        echo "<div class='error'>";
                        echo __($guid, 'There are no records to display.');
                        echo '</div>';
                    } else {
                        echo "<table cellspacing='0' style='width: 100%'>";
                        echo "<tr class='head'>";
                        echo "<th style='width: 150px'>";
                        echo __($guid, 'Name');
                        echo '</th>';
                        echo "<th style='width: 450px'>";
                        echo __($guid, 'Description');
                        echo '</th>';
                        echo '<th>';
                        echo __($guid, 'Active');
                        echo '</th>';
                        echo "<th style='width: 140px'>";
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

                            if ($row['active'] != 'Y') {
                                $rowNum = 'error';
                            }

                            //COLOR ROW BY STATUS!
                            echo "<tr class=$rowNum>";
                            echo '<td>';
                            echo $row['name'];
                            echo '</td>';
                            echo "<td style='max-width: 270px'>";
                            echo $row['description'];
                            echo '</td>';
                            echo '<td>';
                            echo ynExpander($guid, $row['active']);
                            echo '</td>';
                            echo '<td>';
                            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/units_edit.php&gibbonUnitID='.$row['gibbonUnitID']."&gibbonCourseID=$gibbonCourseID&gibbonSchoolYearID=$gibbonSchoolYearID'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/units_delete.php&gibbonUnitID='.$row['gibbonUnitID']."&gibbonCourseID=$gibbonCourseID&gibbonSchoolYearID=$gibbonSchoolYearID'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a> ";
                            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/units_duplicate.php&gibbonCourseID=$gibbonCourseID&gibbonUnitID=".$row['gibbonUnitID']."&gibbonSchoolYearID=$gibbonSchoolYearID'><img title='".__($guid, 'Duplicate')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/copy.png'/></a> ";
                            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/units_dump.php&gibbonCourseID=$gibbonCourseID&gibbonUnitID=".$row['gibbonUnitID']."&gibbonSchoolYearID=$gibbonSchoolYearID&sidebar=false'><img title='".__($guid, 'Export')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/download.png'/></a>";
                            echo '</td>';
                            echo '</tr>';

                            ++$count;
                        }
                        echo '</table>';
                    }

                    //List any hooked units
                    try {
                        $dataHooks = array();
                        $sqlHooks = "SELECT * FROM gibbonHook WHERE type='Unit' ORDER BY name";
                        $resultHooks = $connection2->prepare($sqlHooks);
                        $resultHooks->execute($dataHooks);
                    } catch (PDOException $e) {
                    }
                    while ($rowHooks = $resultHooks->fetch()) {
                        $hookOptions = unserialize($rowHooks['options']);
                        if ($hookOptions['unitTable'] != '' and $hookOptions['unitIDField'] != '' and $hookOptions['unitCourseIDField'] != '' and $hookOptions['unitNameField'] != '' and $hookOptions['unitDescriptionField'] != '' and $hookOptions['classLinkTable'] != '' and $hookOptions['classLinkJoinFieldUnit'] != '' and $hookOptions['classLinkJoinFieldClass'] != '' and $hookOptions['classLinkIDField'] != '') {
                            try {
                                $dataHookUnits = array('unitCourseIDField' => $gibbonCourseID);
                                $sqlHookUnits = 'SELECT * FROM '.$hookOptions['unitTable'].' WHERE '.$hookOptions['unitCourseIDField'].'=:unitCourseIDField ORDER BY '.$hookOptions['unitNameField'];
                                $resultHookUnits = $connection2->prepare($sqlHookUnits);
                                $resultHookUnits->execute($dataHookUnits);
                            } catch (PDOException $e) {
                            }
                            if ($resultHookUnits->rowCount() > 0) {
                                echo '<h4>'.$rowHooks['name'].' Units</h4>';
                                echo "<table cellspacing='0' style='width: 100%'>";
                                echo "<tr class='head'>";
                                echo "<th style='width: 150px'>";
                                echo __($guid, 'Name');
                                echo '</th>';
                                echo "<th style='width: 450px'>";
                                echo 'Description';
                                echo '</th>';
                                echo '<th>';
                                echo __($guid, 'Actions');
                                echo '</th>';
                                echo '</tr>';

                                $count = 0;

                                while ($rowHookUnits = $resultHookUnits->fetch()) {
                                    if ($count % 2 == 0) {
                                        $rowNum = 'even';
                                    } else {
                                        $rowNum = 'odd';
                                    }

                                    //COLOR ROW BY STATUS!
                                    echo "<tr class=$rowNum>";
                                    echo '<td>';
                                    echo $rowHookUnits[$hookOptions['unitNameField']];
                                    echo '</td>';
                                    echo "<td style='max-width: 270px'>";
                                    echo strip_tags($rowHookUnits[$hookOptions['unitDescriptionField']]);
                                    echo '</td>';
                                    echo '<td>';
                                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/units_edit.php&gibbonUnitID='.$rowHookUnits[$hookOptions['unitIDField']].'-'.$rowHooks['gibbonHookID']."&gibbonCourseID=$gibbonCourseID&gibbonSchoolYearID=$gibbonSchoolYearID'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                                    echo '</td>';
                                    echo '</tr>';

                                    ++$count;
                                }
                                echo '</table>';
                            }
                        }
                    }
                }
            }
        }
    }
    //Print sidebar
    $_SESSION[$guid]['sidebarExtra'] = sidebarExtraUnits($guid, $connection2, $gibbonCourseID, $gibbonSchoolYearID);
}
