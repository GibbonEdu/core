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
use Gibbon\Module\Planner\Forms\PlannerFormFactory;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

// common variables
$gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
$gibbonCourseID = $_GET['gibbonCourseID'] ?? '';
$gibbonUnitID = $_GET['gibbonUnitID'] ?? '';

$page->breadcrumbs
    ->add(__('Unit Planner'), 'units.php', [
        'gibbonSchoolYearID' => $gibbonSchoolYearID,
        'gibbonCourseID' => $gibbonCourseID,
    ])
    ->add(__('Edit Unit'));

if (isActionAccessible($guid, $connection2, '/modules/Planner/units_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __('The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        //Proceed!
        $returns = array();
        $returns['success1'] = __('Smart Blockify was successful.');
        $returns['success2'] = __('Copy was successful. The blocks from the selected working unit have replaced those in the master unit (see below for the new block listing).');
        $returns['success3'] = __('Your unit was successfully created: you can now edit and deploy it using the form below.');
        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, $returns);
        }

        //Check if courseschool year specified
        if ($gibbonCourseID == '' or $gibbonSchoolYearID == '') {
            echo "<div class='error'>";
            echo __('You have not specified one or more required parameters.');
            echo '</div>';
        } else {
            try {
                if ($highestAction == 'Unit Planner_all') {
                    $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonCourseID' => $gibbonCourseID);
                    $sql = 'SELECT gibbonCourse.*, gibbonSchoolYear.name as schoolYearName
                            FROM gibbonCourse
                            JOIN gibbonSchoolYear ON (gibbonSchoolYear.gibbonSchoolYearID=gibbonCourse.gibbonSchoolYearID)
                            WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourse.gibbonCourseID=:gibbonCourseID';
                } elseif ($highestAction == 'Unit Planner_learningAreas') {
                    $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonCourseID' => $gibbonCourseID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                    $sql = "SELECT gibbonCourseID, gibbonCourse.name, gibbonCourse.nameShort, gibbonYearGroupIDList, gibbonSchoolYear.name as schoolYearName
                    FROM gibbonCourse
                    JOIN gibbonSchoolYear ON (gibbonSchoolYear.gibbonSchoolYearID=gibbonCourse.gibbonSchoolYearID)
                    JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID)
                    JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID)
                    WHERE gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)') AND gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourse.gibbonCourseID=:gibbonCourseID ORDER BY gibbonCourse.nameShort";
                }
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($result->rowCount() != 1) {
                echo "<div class='error'>";
                echo __('The selected record does not exist, or you do not have access to it.');
                echo '</div>';
            } else {
                $values = $result->fetch();
                $yearName = $values['schoolYearName'];
                $courseName = $values['name'];
                $courseNameShort = $values['nameShort'];
                $gibbonYearGroupIDList = $values['gibbonYearGroupIDList'];

                //Check if unit specified
                if ($gibbonUnitID == '') {
                    echo "<div class='error'>";
                    echo __('You have not specified one or more required parameters.');
                    echo '</div>';
                } else {
                    try {
                        $data = array('gibbonUnitID' => $gibbonUnitID, 'gibbonCourseID' => $gibbonCourseID);
                        $sql = 'SELECT gibbonCourse.nameShort AS courseName, gibbonCourse.gibbonDepartmentID, gibbonUnit.* FROM gibbonUnit JOIN gibbonCourse ON (gibbonUnit.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonUnitID=:gibbonUnitID AND gibbonUnit.gibbonCourseID=:gibbonCourseID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }
                    if ($result->rowCount() != 1) {
                        echo "<div class='error'>";
                        echo __('The specified record cannot be found.');
                        echo '</div>';
                    } else {
                        //Let's go!
                        $values = $result->fetch();
                        $gibbonDepartmentID = $values['gibbonDepartmentID'];

                        $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/units_editProcess.php?gibbonSchoolYearID=$gibbonSchoolYearID&gibbonCourseID=$gibbonCourseID&gibbonUnitID=$gibbonUnitID&address=".$_GET['q']);
                        $form->setFactory(PlannerFormFactory::create($pdo));

                        $form->addHiddenValue('address', $_SESSION[$guid]['address']);

                        //OVERVIEW
                        $form->addRow()->addHeading(__('Overview'));

                        $row = $form->addRow();
                            $row->addLabel('yearName', __('School Year'));
                            $row->addTextField('yearName')->readonly()->setValue($yearName)->required();

                        $row = $form->addRow();
                            $row->addLabel('courseName', __('Course'));
                            $row->addTextField('courseName')->readonly()->setValue($courseName)->required();

                        $row = $form->addRow();
                            $row->addLabel('name', __('Name'));
                            $row->addTextField('name')->required()->maxLength(40);

                        $row = $form->addRow();
                            $row->addLabel('description', __('Description'));
                            $row->addTextArea('description')->setRows(5)->required();

                        $row = $form->addRow();
                            $row->addLabel('active', __('Active'));
                            $row->addYesNo('active')->required();

                        $row = $form->addRow();
                            $row->addLabel('map', __('Include In Curriculum Map'));
                            $row->addYesNo('map')->required();

                        $row = $form->addRow();
                            $row->addLabel('ordering', __('Ordering'))->description(__('Units are arranged form lowest to highest ordering value, then alphabetically.'));
                            $row->addNumber('ordering')->maxLength(4)->decimalPlaces(0)->setValue("0")->required();

                        $tags = getTagList($connection2);
                        $tagsOutput = array();
                        foreach ($tags as $tag) {
                            if ($tag[0] > 0) {
                                $tagsOutput[$tag[1]] = $tag[1] . " (".$tag[0].")";
                            }
                        }
                        $row = $form->addRow()->addClass('tags');
                            $column = $row->addColumn();
                            $column->addLabel('tags', __('Concepts & Keywords'))->description(__('Use tags to describe unit and its contents.'));
                            $column->addFinder('tags')
                                ->fromArray($tagsOutput)
                                ->setParameter('hintText', __('Type a tag...'))
                                ->setParameter('allowFreeTagging', true);

                        //CLASSES
                        $form->addRow()->addHeading(__('Classes'))->append(__('Select classes which will have access to this unit.'));

                        if ($_SESSION[$guid]['gibbonSchoolYearIDCurrent'] == $gibbonSchoolYearID && $_SESSION[$guid]['gibbonSchoolYearIDCurrent'] == $_SESSION[$guid]['gibbonSchoolYearID']) {
                            try {
                                $dataClass = array('gibbonUnitID' => $gibbonUnitID);
                                $sqlClass = "SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourseClass.nameShort, running, gibbonUnitClassID
                                        FROM gibbonCourseClass
                                        LEFT JOIN gibbonUnitClass ON (gibbonUnitClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID AND gibbonUnitID=:gibbonUnitID)
                                        WHERE gibbonCourseID=$gibbonCourseID
                                        ORDER BY name";
                                $resultClass = $connection2->prepare($sqlClass);
                                $resultClass->execute($dataClass);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }

                            if ($resultClass->rowCount() < 1) {
                                $form->addRow()->addAlert(__('There are no records to display.'), 'error');
                            } else {
                                $table = $form->addRow()->addTable()->addClass('colorOddEven');

                                $header = $table->addHeaderRow();
                                $header->addContent(__('Class'));
                                $header->addContent(__('Running'))->append("<br/><small>".__('Is class studying this unit?')."</small>");
                                $header->addContent(__('First Lesson'))->append("<br/><small>".$_SESSION[$guid]['i18n']['dateFormat'] ?? 'dd/mm/yyyy'."</small>");
                                $header->addContent(__('Actions'));


                                $classCount = 0;
                                while ($rowClass = $resultClass->fetch()) {
                                    $row = $table->addRow();
                                        $row->addContent($courseNameShort.'.'.$rowClass['nameShort']);
                                        $row->addYesNo("running$classCount")->selected(!is_null($rowClass['running']) ? $rowClass['running'] : 'N');
                                        $firstLesson = null;
                                        if ($rowClass['running'] == 'Y') {
                                            try {
                                                $dataDate = array('gibbonCourseClassID' => $rowClass['gibbonCourseClassID'], 'gibbonUnitID' => $gibbonUnitID);
                                                $sqlDate = 'SELECT date FROM gibbonPlannerEntry WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonUnitID=:gibbonUnitID ORDER BY date, timeStart';
                                                $resultDate = $connection2->prepare($sqlDate);
                                                $resultDate->execute($dataDate);
                                            } catch (PDOException $e) {}
                                            if ($resultDate->rowCount() > 0) {
                                                $rowDate = $resultDate->fetch();
                                                $firstLesson = dateConvertBack($guid, $rowDate['date']);
                                            }
                                        }
                                        $row->addContent($firstLesson);
                                        if ($rowClass['running'] == 'Y') {
                                            $column = $row->addColumn();
                                                if ($resultDate->rowCount() < 1) {
                                                    $column->addWebLink('<img title="'.__('Edit Unit').'" src="./themes/'.$_SESSION[$guid]['gibbonThemeName'].'/img/config.png"/></a>')
                                                        ->setURL($_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/units_edit_deploy.php&gibbonCourseClassID='.$rowClass['gibbonCourseClassID']."&gibbonCourseID=$gibbonCourseID&gibbonUnitID=$gibbonUnitID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonUnitClassID=".$rowClass['gibbonUnitClassID']);
                                                }
                                                else {
                                                    $column->addWebLink('<img title="'.__('Edit Unit').'" src="./themes/'.$_SESSION[$guid]['gibbonThemeName'].'/img/config.png"/></a>')
                                                        ->setURL($_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/units_edit_working.php&gibbonCourseClassID='.$rowClass['gibbonCourseClassID']."&gibbonCourseID=$gibbonCourseID&gibbonUnitID=$gibbonUnitID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonUnitClassID=".$rowClass['gibbonUnitClassID']);
                                                }
                                                $column->addWebLink('<img title="'.__('View Planner').'" src="./themes/'.$_SESSION[$guid]['gibbonThemeName'].'/img/planner.png"/></a>')
                                                    ->setURL($_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/planner.php&gibbonCourseClassID='.$rowClass['gibbonCourseClassID']."&viewBy=class");
                                                $column->addWebLink('<img title="'.__('Copy Back').'" src="./themes/'.$_SESSION[$guid]['gibbonThemeName'].'/img/copyback.png"/></a>')
                                                    ->setURL($_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/units_edit_copyBack.php&gibbonCourseClassID='.$rowClass['gibbonCourseClassID']."&gibbonCourseID=$gibbonCourseID&gibbonUnitID=$gibbonUnitID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonUnitClassID=".$rowClass['gibbonUnitClassID']);
                                                $column->addWebLink('<img title="'.__('Copy Forward').'" src="./themes/'.$_SESSION[$guid]['gibbonThemeName'].'/img/copyforward.png"/></a>')
                                                    ->setURL($_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/units_edit_copyForward.php&gibbonCourseClassID='.$rowClass['gibbonCourseClassID']."&gibbonCourseID=$gibbonCourseID&gibbonUnitID=$gibbonUnitID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonUnitClassID=".$rowClass['gibbonUnitClassID']);
                                                $column->addWebLink('<img title="'.__('Smart Blockify').'" src="./themes/'.$_SESSION[$guid]['gibbonThemeName'].'/img/run.png"/></a>')
                                                    ->setURL($_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/units_edit_smartBlockify.php&gibbonCourseClassID='.$rowClass['gibbonCourseClassID']."&gibbonCourseID=$gibbonCourseID&gibbonUnitID=$gibbonUnitID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonUnitClassID=".$rowClass['gibbonUnitClassID']);
                                        }
                                    $form->addHiddenValue("gibbonCourseClassID$classCount", $rowClass['gibbonCourseClassID']);
                                    ++$classCount;
                                }

                            }
                        }
                        else {
                            $row = $form->addRow();
                                $row->addAlert(__('You are currently not logged into the current year and/or are looking at units in another year, and so you cannot access your classes. Please log back into the current school year, and look at units in the current year.'), 'warning');
                        }

                        $form->addHiddenValue('classCount', $classCount);

                        //UNIT OUTLINE
                        $form->addRow()->addHeading(__('Unit Outline'));

                        $unitOutline = getSettingByScope($connection2, 'Planner', 'unitOutlineTemplate');
                        $shareUnitOutline = getSettingByScope($connection2, 'Planner', 'shareUnitOutline');
                        if ($shareUnitOutline == 'Y') {
                            $content = __('The contents of both the Unit Outline field and the Downloadable Unit Outline are available to all users who can access this unit via the Lesson Planner (possibly include parents and students).');
                        }
                        else {
                            $content = __('The contents of the Unit Outline field are viewable only to those with full access to the Planner (usually teachers and administrators, but not students and parents), whereas the downloadable version (below) is available to more users (usually parents).');
                        }
                        $row = $form->addRow();
                            $column = $row->addColumn();
                            $column->addAlert($content, 'message');
                            $column->addEditor('details', $guid)->setRows(30)->showMedia()->setValue($unitOutline);

                        try {
                            $dataExt = array();
                            $sqlExt = 'SELECT * FROM gibbonFileExtension';
                            $resultExt = $connection2->prepare($sqlExt);
                            $resultExt->execute($dataExt);
                        } catch (PDOException $e) {}
                        $ext = '';
                        while ($rowExt = $resultExt->fetch()) {
                            $ext .= "'.".$rowExt['extension']."',";
                        }
                        $row = $form->addRow();
                            $row->addLabel('file', __('Downloadable Unit Outline'))->description("Available to most users.");
                            $row->addFileUpload('file')
                                ->accepts(substr($ext, 0, -2))
                                ->setAttachment('attachment', $_SESSION[$guid]['absoluteURL'], $values['attachment']);

                        //OUTCOMES
                        $form->addRow()->addHeading(__('Outcomes'))->append(__('Link this unit to outcomes (defined in the Manage Outcomes section of the Planner), and track which outcomes are being met in which units, classes and courses.'));
                        $allowOutcomeEditing = getSettingByScope($connection2, 'Planner', 'allowOutcomeEditing');
                        $row = $form->addRow();
                            $customBlocks = $row->addPlannerOutcomeBlocks('outcome', $gibbon->session, $gibbonYearGroupIDList, $gibbonDepartmentID, $allowOutcomeEditing);

                        $dataBlocks = array('gibbonUnitID' => $gibbonUnitID);
                        $sqlBlocks = "SELECT gibbonUnitOutcome.*, scope, name, category FROM gibbonUnitOutcome JOIN gibbonOutcome ON (gibbonUnitOutcome.gibbonOutcomeID=gibbonOutcome.gibbonOutcomeID) WHERE gibbonUnitID=:gibbonUnitID AND active='Y' ORDER BY sequenceNumber";
                        $resultBlocks = $pdo->select($sqlBlocks, $dataBlocks);

                        while ($rowBlocks = $resultBlocks->fetch()) {
                            $outcome = array(
                                'outcometitle' => $rowBlocks['name'],
                                'outcomegibbonOutcomeID' => $rowBlocks['gibbonOutcomeID'],
                                'outcomecategory' => $rowBlocks['category'],
                                'outcomecontents' => $rowBlocks['content']
                            );
                            $customBlocks->addBlock($rowBlocks['gibbonOutcomeID'], $outcome);
                        }

                        //SMART BLOCKS
                        $form->addRow()->addHeading(__('Smart Blocks'))->append(__('Smart Blocks aid unit planning by giving teachers help in creating and maintaining new units, splitting material into smaller units which can be deployed to lesson plans. As well as predefined fields to fill, Smart Units provide a visual view of the content blocks that make up a unit. Blocks may be any kind of content, such as discussion, assessments, group work, outcome etc.'));
                        $blockCreator = $form->getFactory()
                            ->createButton('addNewFee')
                            ->setValue(__('Click to create a new block'))
                            ->addClass('addBlock');

                        $row = $form->addRow();
                            $customBlocks = $row->addPlannerSmartBlocks('smart', $gibbon->session, $guid)
                                ->addToolInput($blockCreator);

                        $dataBlocks = array('gibbonUnitID' => $gibbonUnitID);
                        $sqlBlocks = 'SELECT * FROM gibbonUnitBlock WHERE gibbonUnitID=:gibbonUnitID ORDER BY sequenceNumber';
                        $resultBlocks = $pdo->select($sqlBlocks, $dataBlocks);

                        while ($rowBlocks = $resultBlocks->fetch()) {
                            $smart = array(
                                'title' => $rowBlocks['title'],
                                'type' => $rowBlocks['type'],
                                'length' => $rowBlocks['length'],
                                'contents' => $rowBlocks['contents'],
                                'teachersNotes' => $rowBlocks['teachersNotes'],
                                'gibbonUnitBlockID' => $rowBlocks['gibbonUnitBlockID']
                            );
                            $customBlocks->addBlock($rowBlocks['gibbonUnitBlockID'], $smart);
                        }

                        //MISCELLANEOUS SETTINGS
                        $form->addRow()->addHeading(__('Miscellaneous Settings'));

                        $licences = array(
                            "Copyright" => __("Copyright"),
                            "Creative Commons BY" => __("Creative Commons BY"),
                            "Creative Commons BY-SA" => __("Creative Commons BY-SA"),
                            "Creative Commons BY-SA-NC" => __("Creative Commons BY-SA-NC"),
                            "Public Domain" => __("Public Domain")
                        );
                        $row = $form->addRow();
                            $row->addLabel('license', 'License')->description(__('Under what conditions can this work be reused?'));
                            $row->addSelect('license')->fromArray($licences)->placeholder();

                        $makeUnitsPublic = getSettingByScope($connection2, 'Planner', 'makeUnitsPublic');
                        if ($makeUnitsPublic == 'Y') {
                            $row = $form->addRow();
                                $row->addLabel('sharedPublic', __('Shared Publically'))->description(__('Share this unit via the public listing of units? Useful for building MOOCS.'));
                                $row->addYesNo('sharedPublic')->required();
                        }

                        $row = $form->addRow();
                            $row->addSubmit();

                        $form->loadAllValuesFrom($values);

                        echo $form->getOutput();
                    }
                }
            }
        }
    }
    //Print sidebar
    $_SESSION[$guid]['sidebarExtra'] = sidebarExtraUnits($guid, $connection2, $gibbonCourseID, $gibbonSchoolYearID);
}
?>
