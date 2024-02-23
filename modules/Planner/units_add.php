<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Forms\Form;
use Gibbon\Domain\Timetable\CourseGateway;
use Gibbon\Module\Planner\Forms\PlannerFormFactory;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

// common variables
$gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
$gibbonCourseID = $_GET['gibbonCourseID'] ?? '';

$page->breadcrumbs
    ->add(__('Unit Planner'), 'units.php', [
        'gibbonSchoolYearID' => $gibbonSchoolYearID,
        'gibbonCourseID' => $gibbonCourseID,
    ])
    ->add(__('Add Unit'));

if (isActionAccessible($guid, $connection2, '/modules/Planner/units_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        //Proceed!
        if ($gibbonSchoolYearID == '') {
            $page->addError(__('You have not specified one or more required parameters.'));
        } else {

                $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
                $sql = 'SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID';
                $result = $connection2->prepare($sql);
                $result->execute($data);

            if ($result->rowCount() != 1) {
                $page->addError(__('The specified record does not exist.'));
            } else {
                $values = $result->fetch();

                if ($gibbonCourseID == '') {
                    $page->addError(__('You have not specified one or more required parameters.'));
                } else {
                    $courseGateway = $container->get(CourseGateway::class);

                    // Check access to specified course
                    if ($highestAction == 'Unit Planner_all') {
                        $resultCourse = $courseGateway->selectCourseDetailsByCourse($gibbonCourseID);
                    } elseif ($highestAction == 'Unit Planner_learningAreas') {
                        $resultCourse = $courseGateway->selectCourseDetailsByCourseAndPerson($gibbonCourseID, $session->get('gibbonPersonID'));
                    }

                    if ($resultCourse->rowCount() != 1) {
                        echo "<div class='error'>";
                        echo 'The selected record does not exist, or you do not have access to it.';
                        echo '</div>';
                    } else {
                        $rowCourse = $resultCourse->fetch();
                        $gibbonYearGroupIDList = $rowCourse['gibbonYearGroupIDList'];
                        $gibbonDepartmentID = $rowCourse['gibbonDepartmentID'];

                        $form = Form::create('action', $session->get('absoluteURL').'/modules/'.$session->get('module')."/units_addProcess.php?gibbonSchoolYearID=$gibbonSchoolYearID&gibbonCourseID=$gibbonCourseID&address=".$_GET['q']);
                        $form->setFactory(PlannerFormFactory::create($pdo));

                        $form->addHiddenValue('address', $session->get('address'));

                        //OVERVIEW
                        $form->addRow()->addHeading('Overview', __('Overview'));

                        $row = $form->addRow();
                            $row->addLabel('yearName', __('School Year'));
                            $row->addTextField('yearName')->readonly()->setValue($values['name'])->required();

                        $row = $form->addRow();
                            $row->addLabel('courseName', __('Course'));
                            $row->addTextField('courseName')->readonly()->setValue($rowCourse['nameShort'])->required();

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
                                $tagsOutput[$tag[1]] = $tag[1] . " (".$tag[2].")";
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
                        $form->addRow()->addHeading('Classes', __('Classes'))->append(__('Select classes which will have access to this unit.'));


                            $dataClass = array('gibbonCourseID' => $gibbonCourseID);
                            $sqlClass = "SELECT * FROM gibbonCourseClass WHERE gibbonCourseID=:gibbonCourseID ORDER BY name";
                            $resultClass = $connection2->prepare($sqlClass);
                            $resultClass->execute($dataClass);

                        if ($resultClass->rowCount() < 1) {
                            $form->addRow()->addAlert(__('There are no records to display.'), 'error');
                        } else {
                            $table = $form->addRow()->addTable()->addClass('colorOddEven');

                            $header = $table->addHeaderRow();
                            $header->addContent(__('Class'));
                            $header->addContent(__('Running'))->append("<br/><small>".__('Is class studying this unit?')."</small>");

                            $classCount = 0;
                            while ($rowClass = $resultClass->fetch()) {
                                $row = $table->addRow();
                                    $row->addContent($rowCourse['nameShort'].'.'.$rowClass['nameShort']);
                                    $row->addYesNo("running$classCount")->selected("N");
                                $form->addHiddenValue("gibbonCourseClassID$classCount", $rowClass['gibbonCourseClassID']);
                                ++$classCount;
                            }

                        }

                        $form->addHiddenValue('classCount', $classCount);

                        //UNIT OUTLINE
                        $form->addRow()->addHeading('Unit Outline', __('Unit Outline'));

                        $settingGateway = $container->get(SettingGateway::class);
                        $unitOutline = $settingGateway->getSettingByScope('Planner', 'unitOutlineTemplate');
                        $shareUnitOutline = $settingGateway->getSettingByScope('Planner', 'shareUnitOutline');
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


                            $dataExt = array();
                            $sqlExt = 'SELECT * FROM gibbonFileExtension';
                            $resultExt = $connection2->prepare($sqlExt);
                            $resultExt->execute($dataExt);
                        $ext = '';
                        while ($rowExt = $resultExt->fetch()) {
                            $ext .= "'.".$rowExt['extension']."',";
                        }
                        $row = $form->addRow();
                            $row->addLabel('file', __('Downloadable Unit Outline'))->description(__("Available to most users."));
                            $row->addFileUpload('file')
                                ->accepts(substr($ext, 0, -2));

                        //ADVANCED
                        $form->addRow()->addHeading('Advanced Options', __('Advanced Options'));

                        $form->toggleVisibilityByClass('advanced')->onCheckbox('advanced')->when('Y');
                        $row = $form->addRow();
                            $row->addCheckbox('advanced')->setValue('Y')->description(__('Show Advanced Options'));

                        //OUTCOMES
                        $form->addRow()->addHeading('Outcomes', __('Outcomes'))->append(__('Link this unit to outcomes (defined in the Manage Outcomes section of the Planner), and track which outcomes are being met in which units, classes and courses.'))->addClass('advanced');
                        $allowOutcomeEditing = $settingGateway->getSettingByScope('Planner', 'allowOutcomeEditing');
                        $row = $form->addRow()->addClass('advanced');
                            $row->addPlannerOutcomeBlocks('outcome', $session, $gibbonYearGroupIDList, $gibbonDepartmentID, $allowOutcomeEditing);

                        //SMART BLOCKS
                        $form->addRow()->addHeading('Smart Blocks', __('Smart Blocks'))->append(__('Smart Blocks aid unit planning by giving teachers help in creating and maintaining new units, splitting material into smaller units which can be deployed to lesson plans. As well as predefined fields to fill, Smart Units provide a visual view of the content blocks that make up a unit. Blocks may be any kind of content, such as discussion, assessments, group work, outcome etc.'))->addClass('advanced');
                        $blockCreator = $form->getFactory()
                            ->createButton('addNewFee')
                            ->setValue(__('Click to create a new block'))
                            ->addClass('advanced addBlock');

                        $row = $form->addRow()->addClass('advanced');
                            $customBlocks = $row->addPlannerSmartBlocks('smart', $session, $guid)
                                ->addToolInput($blockCreator);

                        for ($i=0 ; $i<5 ; $i++) {
                            $customBlocks->addBlock("block$i");
                        }

                        $form->addHiddenValue('blockCount', "5");

                        //MISCELLANEOUS SETTINGS
                        $form->addRow()->addHeading('Miscellaneous Settings', __('Miscellaneous Settings'))->addClass('advanced');

                        $licences = array(
                            "Copyright" => __("Copyright"),
                            "Creative Commons BY" => __("Creative Commons BY"),
                            "Creative Commons BY-SA" => __("Creative Commons BY-SA"),
                            "Creative Commons BY-SA-NC" => __("Creative Commons BY-SA-NC"),
                            "Public Domain" => __("Public Domain")
                        );
                        $row = $form->addRow()->addClass('advanced');
                            $row->addLabel('license', __('License'))->description(__('Under what conditions can this work be reused?'));
                            $row->addSelect('license')->fromArray($licences)->placeholder();

                        $makeUnitsPublic = $settingGateway->getSettingByScope('Planner', 'makeUnitsPublic');
                        if ($makeUnitsPublic == 'Y') {
                            $row = $form->addRow()->addClass('advanced');
                                $row->addLabel('sharedPublic', __('Shared Publicly'))->description(__('Share this unit via the public listing of units? Useful for building MOOCS.'));
                                $row->addYesNo('sharedPublic')->required();
                        }


                        $row = $form->addRow();
                            $row->addSubmit();

                        echo $form->getOutput();
                    }
                }
            }
        }
    }
    //Print sidebar
    $session->set('sidebarExtra', sidebarExtraUnits($guid, $connection2, $gibbonCourseID, $gibbonSchoolYearID));
}
?>
