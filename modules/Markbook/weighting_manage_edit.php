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

//New PDO DB connection
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();


//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Markbook/weighting_manage_edit.php') == false) {
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
        }

        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, null);
        }

        //Get class variable
        $gibbonCourseClassID = null;
        if (isset($_GET['gibbonCourseClassID'])) {
            $gibbonCourseClassID = $_GET['gibbonCourseClassID'];
        }

        if ($gibbonCourseClassID == '') {
            echo '<h1>';
            echo __($guid, 'Edit Markbook Weighting');
            echo '</h1>';
            echo "<div class='warning'>";
            echo __($guid, 'The selected record does not exist, or you do not have access to it.');
            echo '</div>';

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
                echo __($guid, 'Edit Markbook Weighting');
                echo '</h1>';
                echo "<div class='error'>";
                echo __($guid, 'The selected record does not exist, or you do not have access to it.');
                echo '</div>';
            } else {
                $gibbonMarkbookWeightID = (isset($_GET['gibbonMarkbookWeightID']))? $_GET['gibbonMarkbookWeightID'] : null;
                try {
                    $data2 = array('gibbonMarkbookWeightID' => $gibbonMarkbookWeightID);
                    $sql2 = 'SELECT * FROM gibbonMarkbookWeight WHERE gibbonMarkbookWeightID=:gibbonMarkbookWeightID';
                    $result2 = $connection2->prepare($sql2);
                    $result2->execute($data2);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                if ($result2->rowCount() != 1) {
                    echo '<h1>';
                    echo __($guid, 'Edit Markbook Weighting');
                    echo '</h1>';
                    echo "<div class='error'>";
                    echo __($guid, 'The selected record does not exist, or you do not have access to it.');
                    echo '</div>';
                } else {


                    $row = $result->fetch();
                    $row2 = $result2->fetch();

                    echo "<div class='trail'>";
                    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Edit').' '.$row['course'].'.'.$row['class'].' '.__($guid, ' Weighting').'</div>';
                    echo '</div>';

                    // Show add weighting form
                    ?>
                    <form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/weighting_manage_editProcess.php?gibbonCourseClassID=$gibbonCourseClassID&gibbonMarkbookWeightID=$gibbonMarkbookWeightID"; ?>">
                        <table class='smallIntBorder fullWidth' cellspacing='0'>    
                            <tr class='break'>
                                <td colspan=2> 
                                    <h3>
                                        <?php echo __($guid, 'Edit Markbook Weighting') ?>
                                    </h3>
                                </td>
                            </tr>
                            <?php
                            $types = getSettingByScope($connection2, 'Markbook', 'markbookType');
                            if ($types != false) :
                                $types = explode(',', $types);

                                // Reduce the available types by the array_diff of the used types (excluding our currently used type)
                                try {
                                    $dataTypes = array('gibbonCourseClassID' => $gibbonCourseClassID, 'type' => $row2['type'] );
                                    $sqlTypes = 'SELECT type FROM gibbonMarkbookWeight WHERE gibbonCourseClassID=:gibbonCourseClassID AND type<>:type GROUP BY type';
                                    $resultTypes = $connection2->prepare($sqlTypes);
                                    $resultTypes->execute($dataTypes);
                                } catch (PDOException $e) {}

                                if ($resultTypes->rowCount() > 0) {
                                    $usedTypes = $resultTypes->fetchAll(PDO::FETCH_COLUMN, 0);
                                    $types = array_diff($types, $usedTypes);
                                }

                            ?>
                                <tr>
                                    <td style='width: 275px'> 
                                        <b><?php echo __($guid, 'Type') ?> *</b><br/>
                                        <span class="emphasis small"></span>
                                    </td>
                                    <td class="right">
                                        <select name="type" id="type" class="standardWidth">
                                            <option value="Please select..."><?php echo __($guid, 'Please select...') ?></option>
                                            <?php
                                            foreach ($types as $type) {
                                            
                                                $selected = ($row2['type'] == $type)? 'selected' : '';
                                                printf ('<option value="%1$s" %2$s>%1$s</option>', trim($type), $selected );
                                            }
                                            ?>
                                        </select>
                                        <script type="text/javascript">
                                            var type=new LiveValidation('type');
                                            type.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
                                        </script>
                                    </td>
                                </tr>
                            <?php
                            endif;
                            ?>
                            <tr>
                                <td> 
                                    <b><?php echo __($guid, 'Description') ?> *</b><br/>
                                    <span class="emphasis small"></span>
                                </td>
                                <td> 
                                    <input name="description" id="description" maxlength=50 value="<?php echo $row2['description']; ?>" type="text" class='standardWidth'>
                                    <script type="text/javascript">
                                        var description=new LiveValidation('description');
                                        description.add(Validate.Presence);
                                    </script>
                                </td>
                            </tr>
                            <tr>
                                <td> 
                                    <b><?php echo __($guid, 'Weighting') ?> *</b><br/>
                                    <span class="emphasis small"><?php echo __($guid, 'Percent: 0 to 100'); ?></span>
                                </td>
                                <td> 
                                    <input name="weighting" id="weighting" maxlength=6 value="<?php echo floatval($row2['weighting']); ?>" type="text" class='standardWidth'>
                                    <script type="text/javascript">
                                        var weighting=new LiveValidation('weighting');
                                        weighting.add(Validate.Presence);
                                    </script>
                                </td>
                            </tr>
                            <tr>
                                <td> 
                                    <b><?php echo __($guid, 'Percent of') ?> *</b><br/>
                                    <span class="emphasis small"></span>
                                </td>
                                <td> 
                                    <select name="calculate" id="calculate" class='standardWidth'>
                                        <option value="term" <?php echo ($row2['calculate'] == 'term')? 'selected' : ''; ?>>
                                        <?php echo __($guid, 'Cumulative Average') ?></option>
                                        <option value="year" <?php echo ($row2['calculate'] == 'year')? 'selected' : ''; ?>>
                                        <?php echo __($guid, 'Final Grade') ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td> 
                                    <b><?php echo __($guid, 'Reportable?') ?> *</b><br/>
                                    <span class="emphasis small"></span>
                                </td>
                                <td> 
                                    <select name="reportable" id="reportable" class='standardWidth'>
                                        <option value="Y" <?php echo ($row2['reportable'] == 'Y')? 'selected' : ''; ?> >
                                        <?php echo __($guid, 'Yes') ?></option>
                                        <option value="N" <?php echo ($row2['reportable'] == 'N')? 'selected' : ''; ?>>
                                        <?php echo __($guid, 'No') ?></option>
                                    </select>
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
    }
}
