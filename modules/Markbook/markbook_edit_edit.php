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

//Get settings
$enableEffort = getSettingByScope($connection2, 'Markbook', 'enableEffort');
$enableRubrics = getSettingByScope($connection2, 'Markbook', 'enableRubrics');
$enableColumnWeighting = getSettingByScope($connection2, 'Markbook', 'enableColumnWeighting');
$enableRawAttainment = getSettingByScope($connection2, 'Markbook', 'enableRawAttainment');
$enableGroupByTerm = getSettingByScope($connection2, 'Markbook', 'enableGroupByTerm');
$attainmentAlternativeName = getSettingByScope($connection2, 'Markbook', 'attainmentAlternativeName');
$attainmentAlternativeNameAbrev = getSettingByScope($connection2, 'Markbook', 'attainmentAlternativeNameAbrev');
$effortAlternativeName = getSettingByScope($connection2, 'Markbook', 'effortAlternativeName');
$effortAlternativeNameAbrev = getSettingByScope($connection2, 'Markbook', 'effortAlternativeNameAbrev');

if (isActionAccessible($guid, $connection2, '/modules/Markbook/markbook_edit_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __($guid, 'The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        //Check if school year specified
        $gibbonCourseClassID = $_GET['gibbonCourseClassID'];
        $gibbonMarkbookColumnID = $_GET['gibbonMarkbookColumnID'];
        if ($gibbonCourseClassID == '' or $gibbonMarkbookColumnID == '') {
            echo "<div class='error'>";
            echo __($guid, 'You have not specified one or more required parameters.');
            echo '</div>';
        } else {
            try {
                if ($highestAction == 'Edit Markbook_everything') {
                    $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                    $sql = 'SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.gibbonDepartmentID, gibbonYearGroupIDList FROM gibbonCourse, gibbonCourseClass WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class';
                } else {
                    $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPersonID2' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonCourseClassID2' => $gibbonCourseClassID, 'gibbonMarkbookColumnID' => $gibbonMarkbookColumnID);
                    $sql = "(SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.gibbonDepartmentID, gibbonYearGroupIDList FROM gibbonCourse, gibbonCourseClass, gibbonCourseClassPerson WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND role='Teacher' AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID)
					UNION
					(SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.gibbonDepartmentID, gibbonYearGroupIDList FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonMarkbookColumn ON (gibbonMarkbookColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonMarkbookColumn.gibbonPersonIDCreator=:gibbonPersonID2 AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID2 AND gibbonMarkbookColumnID=:gibbonMarkbookColumnID)
					ORDER BY course, class";
                }
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($result->rowCount() != 1) {
                echo "<div class='error'>";
                echo __($guid, 'The selected record does not exist, or you do not have access to it.');
                echo '</div>';
            } else {
                try {
                    $data2 = array('gibbonMarkbookColumnID' => $gibbonMarkbookColumnID);
                    $sql2 = 'SELECT * FROM gibbonMarkbookColumn WHERE gibbonMarkbookColumnID=:gibbonMarkbookColumnID';
                    $result2 = $connection2->prepare($sql2);
                    $result2->execute($data2);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                if ($result2->rowCount() != 1) {
                    echo "<div class='error'>";
                    echo __($guid, 'The selected record does not exist, or you do not have access to it.');
                    echo '</div>';
                } else {
                    //Let's go!
                    $course = $result->fetch();
                    $values = $result2->fetch();

                    echo "<div class='trail'>";
                    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/markbook_view.php&gibbonCourseClassID='.$_GET['gibbonCourseClassID']."'>".__($guid, 'View').' '.$course['course'].'.'.$course['class'].' '.__($guid, 'Markbook')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Column').'</div>';
                    echo '</div>';

                    if ($values['groupingID'] != '' and $values['gibbonPersonIDCreator'] != $_SESSION[$guid]['gibbonPersonID']) {
                        echo "<div class='error'>";
                        echo __($guid, 'This column is part of a set of columns, which you did not create, and so cannot be individually edited.');
                        echo '</div>';
                    } else {
                        $returns = array();
                        $returns['error6'] = __($guid, 'Your request failed because you already have one "End of Year" column for this class.');
                        $returns['success1'] = __($guid, 'Planner was successfully added: you opted to add a linked Markbook column, and you can now do so below.');
                        if (isset($_GET['return'])) {
                            returnProcess($guid, $_GET['return'], null, $returns);
                        }

                        echo "<div class='linkTop'>";
                        if ($values['gibbonPlannerEntryID'] != '') {
                        echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/planner_view_full.php&viewBy=class&gibbonCourseClassID=$gibbonCourseClassID&gibbonPlannerEntryID=".$values['gibbonPlannerEntryID']."'>".__($guid, 'View Linked Lesson')."<img style='margin: 0 0 -4px 5px' title='".__($guid, 'View Linked Lesson')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/planner.png'/></a> | ";
                        }
                        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/markbook_edit_data.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonMarkbookColumnID=$gibbonMarkbookColumnID'>".__($guid, 'Enter Data')."<img style='margin: 0 0 0px 5px' title='".__($guid, 'Enter Data')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/markbook.png'/></a> ";
                        echo '</div>';

                        $form = Form::create('markbook', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/markbook_edit_editProcess.php?gibbonMarkbookColumnID='.$gibbonMarkbookColumnID.'&gibbonCourseClassID='.$gibbonCourseClassID.'&address='.$_SESSION[$guid]['address']);
                        $form->setFactory(DatabaseFormFactory::create($pdo));
                        $form->addHiddenValue('address', $_SESSION[$guid]['address']);

                        $form->addRow()->addHeading(__('Basic Information'));

                        $row = $form->addRow();
                            $row->addLabel('courseName', __('Class'));
                            $row->addTextField('courseName')->isRequired()->readOnly()->setValue($course['course'].'.'.$course['class']);

                        $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                        $sql = "SELECT gibbonUnit.gibbonUnitID as value, gibbonUnit.name FROM gibbonUnit JOIN gibbonUnitClass ON (gibbonUnit.gibbonUnitID=gibbonUnitClass.gibbonUnitID) WHERE running='Y' AND gibbonCourseClassID=:gibbonCourseClassID ORDER BY name";
                        $hookedUnits = getHookedUnits($pdo, $gibbonCourseClassID);

                        $row = $form->addRow();
                            $row->addLabel('gibbonUnitID', __('Unit'));
                            $units = $row->addSelect('gibbonUnitID')->fromQuery($pdo, $sql, $data)->fromArray($hookedUnits)->placeholder();

                        $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                        $sql = "SELECT (CASE WHEN gibbonHookID IS NOT NULL THEN CONCAT(gibbonHookID, '-', gibbonUnitID) ELSE gibbonUnitID END) as chainedTo, gibbonPlannerEntryID as value, name FROM gibbonPlannerEntry WHERE gibbonCourseClassID=:gibbonCourseClassID ORDER BY name";
                        $row = $form->addRow();
                            $row->addLabel('gibbonPlannerEntryID', __('Lesson'));
                            $row->addSelect('gibbonPlannerEntryID')->fromQueryChained($pdo, $sql, $data, 'gibbonUnitID')->placeholder();

                        $row = $form->addRow();
                            $row->addLabel('name', __('Name'));
                            $row->addTextField('name')->isRequired()->maxLength(20);

                        $row = $form->addRow();
                            $row->addLabel('description', __('Description'));
                            $row->addTextField('description')->isRequired()->maxLength(1000);

                        // TYPE
                        $types = getSettingByScope($connection2, 'Markbook', 'markbookType');
                        if (!empty($types)) {
                            $row = $form->addRow();
                                $row->addLabel('type', __('Type'));
                                $typesSelect = $row->addSelect('type')->isRequired()->placeholder();

                            if ($enableColumnWeighting == 'Y') {
                                $data = array('gibbonCourseClassID' => $gibbonCourseClassID, 'perTerm' => __('Per Term'), 'wholeYear' => __('Whole Year'));
                                $sql = "SELECT (CASE WHEN calculate='term' THEN :perTerm ELSE :wholeYear END) as groupBy, type as value, description as name FROM gibbonMarkbookWeight WHERE gibbonCourseClassID=:gibbonCourseClassID ORDER BY calculate, type";
                                $typesSelect->fromQuery($pdo, $sql, $data, 'groupBy');
                            }

                            if ($typesSelect->getOptionCount() == 0) {
                                $typesSelect->fromString($types);
                            }
                        }

                        $row = $form->addRow();
                            $row->addLabel('file', __('Attachment'));
                            $row->addFileUpload('file')->setAttachment('attachment', $_SESSION[$guid]['absoluteURL'], $values['attachment']);

                        // DATE
                        if ($enableGroupByTerm == 'Y') {
                            $form->addRow()->addHeading(__('Term Date'));
        
                            $row = $form->addRow();
                                $row->addLabel('gibbonSchoolYearTermID', __('Term'));
                                $row->addSelectSchoolYearTerm('gibbonSchoolYearTermID', $_SESSION[$guid]['gibbonSchoolYearID']);
        
                            $row = $form->addRow();
                                $row->addLabel('date', __('Date'));
                                $row->addDate('date')->setValue(dateConvertBack($guid, $values['date']))->isRequired();
                        } else {
                            $form->addHiddenValue('gibbonSchoolYearTermID',$values['gibbonSchoolYearTermID']);
                            $form->addHiddenValue('date', dateConvertBack($guid, $values['date']));
                        }

                        $form->addRow()->addHeading(__('Assessment'));

                        // ATTAINMENT
                        $attainmentLabel = !empty($attainmentAltName)? sprintf(__('Assess %1$s?'), $attainmentAltName) : __('Assess Attainment?');
                        $attainmentScaleLabel = !empty($attainmentAltName)? $attainmentAltName.' '.__('Scale') : __('Attainment Scale');
                        $attainmentRawMaxLabel = !empty($attainmentAltName)? $attainmentAltName.' '.__('Total Mark') : __('Attainment Total Mark');
                        $attainmentWeightingLabel = !empty($attainmentAltName)? $attainmentAltName.' '.__('Weighting') : __('Attainment Weighting');
                        $attainmentRubricLabel = !empty($attainmentAltName)? $attainmentAltName.' '.__('Rubric') : __('Attainment Rubric'); 

                        $row = $form->addRow();
                            $row->addLabel('attainment', $attainmentLabel);
                            $row->addYesNoRadio('attainment')->isRequired();

                        $form->toggleVisibilityByClass('attainmentRow')->onRadio('attainment')->when('Y');

                        $row = $form->addRow()->addClass('attainmentRow');
                            $row->addLabel('gibbonScaleIDAttainment', $attainmentScaleLabel);
                            $row->addSelectGradeScale('gibbonScaleIDAttainment')->isRequired();
                            
                        if ($enableRawAttainment == 'Y') {
                            $row = $form->addRow()->addClass('attainmentRow');
                                $row->addLabel('attainmentRawMax', $attainmentRawMaxLabel)->description(__('Leave blank to omit raw marks.'));
                                $row->addNumber('attainmentRawMax')->maxLength(8)->onlyInteger(false);
                        }

                        if ($enableColumnWeighting == 'Y') {
                            $row = $form->addRow()->addClass('attainmentRow');
                                $row->addLabel('attainmentWeighting', $attainmentWeightingLabel);
                                $row->addNumber('attainmentWeighting')->maxLength(5)->onlyInteger(false)->setValue(1);
                        }

                        if ($enableRubrics == 'Y') {
                            $row = $form->addRow()->addClass('attainmentRow');
                                $row->addLabel('gibbonRubricIDAttainment', $attainmentRubricLabel)->description(__('Choose predefined rubric, if desired.'));
                                $row->addSelectRubric('gibbonRubricIDAttainment', $course['gibbonYearGroupIDList'], $course['gibbonDepartmentID'])->placeholder();
                        }

                        // EFFORT
                        if ($enableEffort == 'Y') {
                            $effortLabel = !empty($effortAltName)? sprintf(__('Assess %1$s?'), $effortAltName) : __('Assess Effort?');
                            $effortScaleLabel = !empty($effortAltName)? $effortAltName.' '.__('Scale') : __('Effort Scale');
                            $effortRubricLabel = !empty($effortAltName)? $effortAltName.' '.__('Rubric') : __('Effort Rubric'); 

                            $row = $form->addRow();
                                $row->addLabel('effort', $effortLabel);
                                $row->addYesNoRadio('effort')->isRequired();

                            $form->toggleVisibilityByClass('effortRow')->onRadio('effort')->when('Y');

                            $row = $form->addRow()->addClass('effortRow');
                                $row->addLabel('gibbonScaleIDEffort', $effortScaleLabel);
                                $row->addSelectGradeScale('gibbonScaleIDEffort')->isRequired();

                            if ($enableRubrics == 'Y') {
                                $row = $form->addRow()->addClass('effortRow');
                                    $row->addLabel('gibbonRubricIDEffort', $effortRubricLabel)->description(__('Choose predefined rubric, if desired.'));
                                    $row->addSelectRubric('gibbonRubricIDEffort', $course['gibbonYearGroupIDList'], $course['gibbonDepartmentID'])->placeholder();
                            } 
                        }

                        $row = $form->addRow();
                            $row->addLabel('comment', __('Include Comment?'));
                            $row->addYesNoRadio('comment')->isRequired();

                        $row = $form->addRow();
                            $row->addLabel('uploadedResponse', __('Include Uploaded Response?'));
                            $row->addYesNoRadio('uploadedResponse')->isRequired();

                        $form->addRow()->addHeading(__('Access'));

                        $row = $form->addRow();
                            $row->addLabel('viewableStudents', __('Viewable to Students'));
                            $row->addYesNo('viewableStudents')->isRequired();

                        $row = $form->addRow();
                            $row->addLabel('viewableParents', __('Viewable to Parents'));
                            $row->addYesNo('viewableParents')->isRequired();

                        $row = $form->addRow();
                            $row->addLabel('completeDate', __('Go Live Date'))->prepend('1. ')->append('<br/>'.__('2. Column is hidden until date is reached.'));
                            $row->addDate('completeDate');

                        $row = $form->addRow();
                            $row->addFooter();
                            $row->addSubmit();

                        $form->loadAllValuesFrom($values);

                        echo $form->getOutput();
                    }
                }
            }
        }
    }

    // Print the sidebar
    $_SESSION[$guid]['sidebarExtra'] = sidebarExtra($guid, $pdo, $_SESSION[$guid]['gibbonPersonID'], $gibbonCourseClassID, 'markbook_view.php');
}
