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

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Markbook/weighting_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'Your request failed because you do not have access to this action.');
    echo '</div>';
} else {
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __($guid, 'The highest grouped action cannot be determined.');
        echo '</div>';
    } else {

        if (getSettingByScope($connection2, 'Markbook', 'enableColumnWeighting') != 'Y') {
            //Acess denied
            echo "<div class='error'>";
            echo __($guid, 'Your request failed because you do not have access to this action.');
            echo '</div>';
            return;
        }

        //Get class variable
        $gibbonCourseClassID = null;
        if (isset($_GET['gibbonCourseClassID'])) {
            $gibbonCourseClassID = $_GET['gibbonCourseClassID'];
        }

        if ($gibbonCourseClassID == '') {
            $gibbonCourseClassID = (isset($_SESSION[$guid]['markbookClass']))? $_SESSION[$guid]['markbookClass'] : '';
        }

        if ($gibbonCourseClassID == '') {
            echo '<h1>';
            echo __($guid, 'Manage Weighting');
            echo '</h1>';
            echo "<div class='warning'>";
            echo __($guid, 'The selected record does not exist, or you do not have access to it.');
            echo '</div>';

            //Get class chooser
            echo classChooser($guid, $pdo, $gibbonCourseClassID);
            return;
        }
        //Check existence of and access to this class.
        else {
            try {
                if ($highestAction == 'Manage Weightings_everything') {
                    $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                    $sql = 'SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.gibbonDepartmentID, gibbonYearGroupIDList FROM gibbonCourse, gibbonCourseClass WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class';
                } else {
                    $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonCourseClassID' => $gibbonCourseClassID);
                    $sql = "SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.gibbonDepartmentID, gibbonYearGroupIDList FROM gibbonCourse, gibbonCourseClass, gibbonCourseClassPerson WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND role='Teacher' AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class";
                }
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($result->rowCount() != 1) {
                echo '<h1>';
                echo __($guid, 'Manage Weightings');
                echo '</h1>';
                echo "<div class='error'>";
                echo __($guid, 'The selected record does not exist, or you do not have access to it.');
                echo '</div>';
            } else {
                $row = $result->fetch();
                echo "<div class='trail'>";
                echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Manage').' '.$row['course'].'.'.$row['class'].' '.__($guid, ' Weightings').'</div>';
                echo '</div>';

                if (isset($_GET['return'])) {
                    returnProcess($guid, $_GET['return'], null, null);
                }

                //Get teacher list
                $teacherList = getTeacherList( $pdo, $gibbonCourseClassID );
                $teaching = (isset($teacherList[ $_SESSION[$guid]['gibbonPersonID'] ]) );

                //Print mark
                echo '<h3>';
                echo __($guid, 'Markbook Weightings');
                echo '</h3>';

                try {
                    $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                    $sql = 'SELECT * FROM gibbonMarkbookWeight WHERE gibbonCourseClassID=:gibbonCourseClassID ORDER BY calculate, weighting DESC';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                if ($teaching || $highestAction == 'Manage Weightings_everything') {
                    echo "<div class='linkTop'>";
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/weighting_manage_add.php&gibbonCourseClassID=$gibbonCourseClassID'>".__($guid, 'Add')."<img style='margin-left: 5px' title='".__($guid, 'Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
                    echo '</div>';
                }

                if ($result->rowCount() < 1) {
                    echo "<div class='error'>";
                    echo __($guid, 'There are no records to display.');
                    echo '</div>';
                } else {
                    echo "<table class='colorOddEven' cellspacing='0' style='width: 100%'>";
                    echo "<tr class='head'>";
                    echo '<th>';
                    echo __($guid, 'Type');
                    echo '</th>';
                    echo '<th width="200px">';
                    echo __($guid, 'Description');
                    echo '</th>';
                    echo '<th>';
                    echo __($guid, 'Weighting');
                    echo '</th>';
                    echo '<th>';
                    echo __($guid, 'Percent of');
                    echo '</th>';
                    echo '<th>';
                    echo __($guid, 'Reportable?');
                    echo '</th>';
                    echo '<th style="width:80px">';
                    echo __($guid, 'Actions');
                    echo '</th>';
                    echo '</tr>';

                    $count = 0;
                    $totalTermWeight = 0;
                    $totalYearWeight = 0;

                    $weightings = $result->fetchAll();
                    foreach ($weightings as $row) {

                        if ($row['calculate'] == 'term' && $row['reportable'] == 'Y') {
                            $totalTermWeight += floatval($row['weighting']);
                        } else if ($row['calculate'] == 'year' && $row['reportable'] == 'Y') {
                            $totalYearWeight += floatval($row['weighting']);
                        }

                        echo "<tr>";
                        echo '<td>';
                        echo $row['type'];
                        echo '</td>';
                        echo '<td>';
                        echo $row['description'];
                        echo '</td>';
                        echo '<td>';
                        echo floatval($row['weighting']).'%';
                        echo '</td>';
                        echo '<td>';
                        echo ($row['calculate'] == 'term')? __($guid, 'Cumulative Average') : __($guid, 'Final Grade');
                        echo '</td>';
                        echo '<td>';
                        echo ($row['reportable'] == 'Y')? __($guid, 'Yes') : __($guid, 'No');
                        echo '</td>';
                        echo '<td>';
                        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/weighting_manage_edit.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonMarkbookWeightID=".$row['gibbonMarkbookWeightID']."'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                        echo "<a class='thickbox' href='".$_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/'.$_SESSION[$guid]['module']."/weighting_manage_delete.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonMarkbookWeightID=".$row['gibbonMarkbookWeightID']."&width=650&height=135'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a> ";
                        echo '</td>';
                        echo '</tr>';

                        ++$count;
                    }
                    echo '</table><br/>';


                    // Sample term calculation
                    if ($totalTermWeight > 0) {

                        echo '<h4>';
                        echo __($guid, 'Sample Calculation') .': '. __($guid, 'Cumulative Average');
                        echo '</h4>';

                        if ($totalTermWeight != 100) {
                            echo "<div class='warning'>";
                            printf ( __($guid, 'Total cumulative weighting is %s. Calculated averages may not be accurate if the total weighting does not add up to 100%%.'), floatval($totalTermWeight).'%' );
                            echo '</div>';
                        }

                        echo '<table class="blank" style="font-size:100%;min-width: 250px;">';
                        $count = 0;
                        foreach ($weightings as $row) {
                            if ($row['calculate'] != 'term') continue;
                            if ($row['reportable'] == 'N') continue;
                            echo '<tr>';
                                echo '<td style="width:20px;text-align:right;">'. (($count != 0)? '+' : '') .'</td>';
                                printf( '<td style="text-align:left">%s%% of %s</td>', floatval($row['weighting']), $row['description'] );
                            echo '</tr>';

                            $count++;
                        }
                        echo '<tr>';
                            echo '<td colspan=2 style="border-top: 2px solid #999999 !important;height: 5px !important;"></td>';
                        echo '</tr>';
                        echo '<tr>';
                            echo '<td style="text-align:right;">=</td>';
                            echo '<td style="text-align:left">'. __($guid, 'Cumulative Average') .'</td>';
                        echo '</tr>';
                        echo '</table><br/>';
                    }

                    if ($totalYearWeight > 0) {
                        echo '<h4>';
                        echo __($guid, 'Sample Calculation') .': '. __($guid, 'Final Grade');
                        echo '</h4>';

                        if ($totalYearWeight >= 100 || (100 - $totalYearWeight) <= 0) {
                            echo "<div class='warning'>";
                            printf ( __($guid, 'Total final grade weighting is %s. Calculated averages may not be accurate if the total weighting  exceeds 100%%.'), floatval($totalYearWeight).'%' );
                            echo '</div>';
                        }

                        // Sample whole year calculation
                        echo '<table class="blank" style="font-size:100%;min-width: 250px;">';

                        echo '<tr>';
                            echo '<td style="width:20px;"></td>';
                            printf( '<td style="text-align:left">%s%% of %s</td>', floatval( max(0, 100 - $totalYearWeight) ), __($guid, 'Cumulative Average') );
                        echo '</tr>';

                        foreach ($weightings as $row) {
                            if ($row['calculate'] != 'year') continue;
                            if ($row['reportable'] == 'N') continue;
                            echo '<tr>';
                                echo '<td style="width:20px;text-align:right;">'. '+' .'</td>';
                                printf( '<td style="text-align:left">%s%% of %s</td>', floatval($row['weighting']), $row['description'] );
                            echo '</tr>';

                            $count++;
                        }
                        echo '<tr>';
                            echo '<td colspan=2 style="border-top: 2px solid #999999 !important;height: 5px !important;"></td>';
                        echo '</tr>';
                        echo '<tr>';
                            echo '<td style="text-align:right;">=</td>';
                            echo '<td style="text-align:left">'. __($guid, 'Final Grade') .'</td>';
                        echo '</tr>';
                        echo '</table>';
                    }


                }

                echo '<br/>&nbsp;<br/>';

                echo '<h3>';
                echo __($guid, 'Copy Weightings');
                echo '</h3>';

                $form = Form::create('search', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/weighting_manage_copyProcess.php?gibbonCourseClassID='.$gibbonCourseClassID);
                $form->setFactory(DatabaseFormFactory::create($pdo));
                $form->setClass('noIntBorder fullWidth');

                $col = $form->addRow()->addColumn()->addClass('inline right');
                    $col->addContent(__('Copy from').' '.__('Class').':');
                    $col->addSelectClass('gibbonWeightingCopyClassID', $_SESSION[$guid]['gibbonSchoolYearID'], $_SESSION[$guid]['gibbonPersonID'])->setClass('mediumWidth');
                    $col->addSubmit(__('Go'));

                echo $form->getOutput();
            }
        }
    }

    // Print the sidebar
    $_SESSION[$guid]['sidebarExtra'] = sidebarExtra($guid, $pdo, $_SESSION[$guid]['gibbonPersonID'], $gibbonCourseClassID, 'weighting_manage.php');
}
