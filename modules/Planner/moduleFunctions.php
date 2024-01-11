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
use Gibbon\Services\Format;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\Timetable\CourseGateway;
use Gibbon\Domain\Planner\PlannerEntryGateway;
use Gibbon\Forms\Input\Editor;
use Gibbon\Data\Validator;

//Make the display for a block, according to the input provided, where $i is a unique number appended to the block's field ids.
//Mode can be masterAdd, masterEdit, workingDeploy, workingEdit, plannerEdit, embed
//Outcomes is the result set of a mysql query of all outcomes from the unit the class belongs to
function makeBlock($guid, $connection2, $i, $mode = 'masterAdd', $title = '', $type = '', $length = '', $contents = '', $complete = 'N', $gibbonUnitBlockID = '', $gibbonUnitClassBlockID = '', $teachersNotes = '', $outerBlock = true)
{
    global $session, $container;

    if ($outerBlock) {
        echo "<div id='blockOuter$i' class='blockOuter'>";
    }
    if ($mode != 'embed') {
        ?>
		<style>
			.sortable { list-style-type: none; margin: 0; padding: 0; width: 100%; }
			.sortable div.ui-state-default { margin: 0 0px 5px 0px; padding: 5px; font-size: 100%; min-height: 82px; }
			div.ui-state-default_dud { margin: 5px 0px 5px 0px; padding: 5px; font-size: 100%; min-height: 82px; }
			html>body .sortable li { min-height: 58px; line-height: 1.2em; }
			.sortable .ui-state-highlight { margin-bottom: 5px; min-height: 82px; line-height: 1.2em; width: 100%; }
		</style>

		<script type='text/javascript'>
			$(function() {
				$( ".sortable" ).sortable({
					placeholder: "ui-state-highlight"
				});

				$( ".sortable" ).bind( "sortstart", function(event, ui) {
					$("#blockInner<?php echo $i ?>").css("display","none") ;
					$("#block<?php echo $i ?>").css("height","82px") ;
					$('#show<?php echo $i ?>').css("background-image", "<?php echo "url(\'".$session->get('absoluteURL').'/themes/'.$session->get('gibbonThemeName')."/img/plus.png\'"?>)");
					tinyMCE.execCommand('mceRemoveEditor', false, 'contents<?php echo $i ?>') ;
					tinyMCE.execCommand('mceRemoveEditor', false, 'teachersNotes<?php echo $i ?>') ;
					$(".sortable").sortable( "refresh" ) ;
					$(".sortable").sortable( "refreshPositions" ) ;
				});
			});

		</script>
		<script type='text/javascript'>
			$(document).ready(function(){
				$("#blockInner<?php echo $i ?>").css("display","none");
				$("#block<?php echo $i ?>").css("height","82px")

				//Block contents control
				$('#show<?php echo $i ?>').unbind('click').click(function() {
					if ($("#blockInner<?php echo $i ?>").is(":visible")) {
						$("#blockInner<?php echo $i ?>").css("display","none");
						$("#block<?php echo $i ?>").css("height","82px")
						$('#show<?php echo $i ?>').css("background-image", "<?php echo "url(\'".$session->get('absoluteURL').'/themes/'.$session->get('gibbonThemeName')."/img/plus.png\'"?>)");
						tinyMCE.execCommand('mceRemoveEditor', false, 'contents<?php echo $i ?>') ;
						tinyMCE.execCommand('mceRemoveEditor', false, 'teachersNotes<?php echo $i ?>') ;
					} else {
						$("#blockInner<?php echo $i ?>").slideDown("fast", $("#blockInner<?php echo $i ?>").css("display","table-row"));
						$("#block<?php echo $i ?>").css("height","auto")
						$('#show<?php echo $i ?>').css("background-image", "<?php echo "url(\'".$session->get('absoluteURL').'/themes/'.$session->get('gibbonThemeName')."/img/minus.png\'"?>)");
						tinyMCE.execCommand('mceRemoveEditor', false, 'contents<?php echo $i ?>') ;
						tinyMCE.execCommand('mceAddEditor', false, 'contents<?php echo $i ?>') ;
						tinyMCE.execCommand('mceRemoveEditor', false, 'teachersNotes<?php echo $i ?>') ;
						tinyMCE.execCommand('mceAddEditor', false, 'teachersNotes<?php echo $i ?>') ;
					}
				});

				$('#delete<?php echo $i ?>').unbind('click').click(function() {
					if (confirm("<?php echo __('Are you sure you want to delete this record?') ?>")) {
						$('#block<?php echo $i ?>').fadeOut(600, function(){ $('#block<?php echo $i ?>').remove(); });
					}
				});
			});
		</script>
		<?php

    }
    ?>
	<div class='hiddenReveal' style='border: 1px solid #d8dcdf; margin: 0 0 5px' id="block<?php echo $i ?>" style='padding: 0px'>
		<table class='blank' cellspacing='0' style='width: 100%'>
			<tr>
				<td style='width: 50%'>
					<input name='order[]' type='hidden' value='<?php echo $i ?>'>
					<input <?php if ($mode == 'embed') { echo 'readonly'; } ?> maxlength=100 id='title<?php echo $i ?>' name='title<?php echo $i ?>' type='text' style='float: left; border: 1px dotted #aaa; background: none; margin-left: 3px; margin-top: 0px; font-size: 140%; font-weight: bold; width: 350px' value='<?php if ($mode != 'masterAdd') { echo htmlPrep($title); }?>' placeholder='<?php echo __('Title'); ?>'><br/>
					<input <?php if ($mode == 'embed') { echo 'readonly'; } ?> maxlength=50 id='type<?php echo $i ?>' name='type<?php echo $i ?>' type='text' style='float: left; border: 1px dotted #aaa; background: none; margin-left: 3px; margin-top: 2px; font-size: 110%; font-style: italic; width: 250px' value='<?php if ($mode != 'masterAdd') { echo htmlPrep($type); }?>' placeholder='<?php echo __('Type (e.g. discussion, outcome)'); ?>'>
					<input <?php if ($mode == 'embed') { echo 'readonly'; } ?> maxlength=3 id='length<?php echo $i ?>' name='length<?php echo $i ?>' type='text' style='float: left; border: 1px dotted #aaa; background: none; margin-left: 3px; margin-top: 2px; font-size: 110%; font-style: italic; width: 95px' value='<?php if ($mode != 'masterAdd') { echo htmlPrep($length); }?>' placeholder='<?php echo __('Length (min.)'); ?>'>
				</td>
				<td style='text-align: right; width: 50%'>
					<div style='margin-bottom: 5px'>
						<?php
                        if ($mode != 'plannerEdit' and $mode != 'embed') {
							echo "<img style='margin-top: 2px' id='delete$i' title='".__('Delete')."' src='./themes/".$session->get('gibbonThemeName')."/img/garbage.png'/> ";
						}
						if ($mode == 'workingEdit') {
                            $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
                            $gibbonCourseID = $_GET['gibbonCourseID'] ?? '';
                            $gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';
                            $gibbonUnitID = $_GET['gibbonUnitID'] ?? '';
                            $gibbonUnitClassID = $_GET['gibbonUnitClassID'] ?? '';
                            echo "<a onclick='return confirm(\"".__('Are you sure you want to leave this page? Any unsaved changes will be lost.')."\")' style='margin-right: 2px; font-weight: normal; font-style: normal; color: #fff' href='".$session->get('absoluteURL').'/index.php?q=/modules/Planner/units_edit_working_copyback.php&gibbonSchoolYearID='.$gibbonSchoolYearID.'&gibbonCourseID='.$gibbonCourseID.'&gibbonCourseClassID='.$gibbonCourseClassID.'&gibbonUnitID='.$gibbonUnitID."&gibbonUnitBlockID=$gibbonUnitBlockID&gibbonUnitClassBlockID=$gibbonUnitClassBlockID&gibbonUnitClassID=".$gibbonUnitClassID."'><img id='copyback$i' title='Copy Back' src='./themes/".$session->get('gibbonThemeName')."/img/copyback.png'/></a>";
						}
						if ($mode != 'embed') {
							echo "<div title='".__('Show/Hide Details')."' id='show$i' style='margin-right: 3px; margin-top: 3px; margin-left: 3px; padding-right: 1px; float: right; width: 25px; height: 25px; background-image: url(\"".$session->get('absoluteURL').'/themes/'.$session->get('gibbonThemeName')."/img/plus.png\"); background-repeat: no-repeat'></div></br>";
						}
						?>
					</div>
					<?php
                    if ($mode == 'plannerEdit') {
                        echo '</br>';
                    }
					if ($mode != 'embed') {
						?>
						<div style='margin-right: 5px'>Complete? <input id='complete<?php echo $i ?>' name='complete<?php echo $i ?>' style='margin-right: 2px' type="checkbox" <?php if ($mode == 'masterAdd' or $mode == 'masterEdit') { echo 'disabled';
					} else {
						if ($complete == 'Y') {
							echo 'checked';
						}
					}
						?>></div>
					<?php

					}
					?>
					<input type='hidden' name='gibbonUnitBlockID<?php echo $i ?>' value='<?php echo $gibbonUnitBlockID ?>'>
					<input type='hidden' name='gibbonUnitClassBlockID<?php echo $i ?>' value='<?php echo $gibbonUnitClassBlockID ?>'>
				</td>
			</tr>
			<tr id="blockInner<?php echo $i ?>">
				<td colspan=2 style='vertical-align: top'>
					<?php
                    if ($mode == 'masterAdd') {
                        $contents = $container->get(SettingGateway::class)->getSettingByScope('Planner', 'smartBlockTemplate');
                    }
    				echo "<div style='text-align: left; font-weight: bold; margin-top: 15px'>".__('Block Contents').'</div>';
                    //Block Contents
                    if ($mode != 'embed') {
                        $editor = (new Editor("contents$i"))
                            ->tinymceInit(false)
                            ->setValue($contents)
                            ->setRows(20)
                            ->showMedia(true)
                            ->setRequired(false)
                            ->initiallyHidden(false)
                            ->allowUpload(true);
                        echo $editor->getOutput();
                    } else {
                        echo "<div style='max-width: 595px; margin-right: 0!important; padding: 5px!important'><p>$contents</p></div>";
                    }

                    //Teacher's Notes
                    if ($mode != 'embed') {
                        echo "<div style='text-align: left; font-weight: bold; margin-top: 15px'>".__('Teacher\'s Notes').'</div>';
                        $editor = (new Editor("teachersNotes$i"))
                            ->tinymceInit(false)
                            ->setValue($teachersNotes)
                            ->setRows(20)
                            ->showMedia(true)
                            ->setRequired(false)
                            ->initiallyHidden(false)
                            ->allowUpload(true);
                        echo $editor->getOutput();
                    } elseif ($teachersNotes != '') {
                        echo "<div style='text-align: left; font-weight: bold; margin-top: 15px'>".__('Teacher\'s Notes').'</div>';
                        echo "<div style='max-width: 595px; margin-right: 0!important; padding: 5px!important; background-color: #F6CECB'><p>$teachersNotes</p></div>";
                    }
   				 	?>
				</td>
			</tr>
		</table>
	</div>
	<?php
    if ($outerBlock) {
        echo '</div>';
    }
}

function getThread($guid, $connection2, $gibbonPlannerEntryID, $parent, $level, $self, $viewBy, $subView, $date, $class, $gibbonCourseClassID, $search, $role, $links = true, $narrow = false)
{
    global $session, $container;

    $validator = $container->get(Validator::class);

    $output = '';

    try {
        if ($parent == null) {
            $dataDiscuss = array('gibbonPlannerEntryID' => $gibbonPlannerEntryID);
            $sqlDiscuss = 'SELECT gibbonPlannerEntryDiscuss.*, title, surname, preferredName, category FROM gibbonPlannerEntryDiscuss JOIN gibbonPerson ON (gibbonPlannerEntryDiscuss.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPlannerEntryDiscussIDReplyTo IS NULL ORDER BY timestamp';
        } else {
            $dataDiscuss = array('gibbonPlannerEntryID' => $gibbonPlannerEntryID, 'gibbonPlannerEntryDiscussIDReplyTo' => $parent, 'gibbonPlannerEntryDiscussID' => $self);
            $sqlDiscuss = 'SELECT gibbonPlannerEntryDiscuss.*, title, surname, preferredName, category FROM gibbonPlannerEntryDiscuss JOIN gibbonPerson ON (gibbonPlannerEntryDiscuss.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPlannerEntryDiscussIDReplyTo=:gibbonPlannerEntryDiscussIDReplyTo AND gibbonPlannerEntryDiscussID=:gibbonPlannerEntryDiscussID ORDER BY timestamp';
        }
        $resultDiscuss = $connection2->prepare($sqlDiscuss);
        $resultDiscuss->execute($dataDiscuss);
    } catch (PDOException $e) {
    }

    if ($level == 0 and $resultDiscuss->rowCount() == 0) {
        $output .= "<div class='message'>";
        $output .= __('There are no records to display.');
        $output .= '</div>';
    } else {
        while ($rowDiscuss = $resultDiscuss->fetch()) {
            $classExtra = '';
            $namePerson = __('{name} said', [
                'name' => Format::name($rowDiscuss['title'], $rowDiscuss['preferredName'], $rowDiscuss['surname'], $rowDiscuss['category'])
            ]);
            $datetimePosted = __('Posted at {hourPosted} on {datePosted}', [
                'hourPosted' => '<b>'.substr($rowDiscuss['timestamp'], 11, 5).'</b>',
                'datePosted' => '<b>'.Format::date(substr($rowDiscuss['timestamp'], 0, 10)).'</b>'
            ]);
            if ($level == 0) {
                $classExtra = 'chatBoxFirst';
            }
            $output .= "<a name='".$rowDiscuss['gibbonPlannerEntryDiscussID']."'></a>";
            $width = (752 - ($level * 15));
            if ($narrow) {
                $width = (705 - ($level * 15));
            }
            $output .= "<table class='noIntBorder chatBox $classExtra' cellspacing='0' style='width: ".$width.'px; margin-left: '.($level * 15)."px'>";
            $output .= "<tr>";
            $output .= "<td><i>".$namePerson.'</i>:</td>';
            $output .= "<td style='text-align: right'><i>".$datetimePosted."</i></td>";
            $output .= "</tr>";
            $output .= "<tr>";
            $output .= "<td style='max-width: ".(700 - ($level * 15))."px;' colspan=2>".$validator->sanitizeRichText($rowDiscuss['comment']).'</td>';
            $output .= "</tr>";
            $output .= "<tr>";
            if ($links == true) {
                $output .= "<td style='text-align: right' colspan=2><a href='".$session->get('absoluteURL')."/index.php?q=/modules/Planner/planner_view_full_post.php&gibbonPlannerEntryID=$gibbonPlannerEntryID&viewBy=$viewBy&subView=$subView&gibbonCourseClassID=$gibbonCourseClassID&date=$date&width=1000&height=550&replyTo=".$rowDiscuss['gibbonPlannerEntryDiscussID']."&search=$search'>Reply</a> ";
                if ($role == 'Teacher') {
                    $output .= " | <a href='".$session->get('absoluteURL')."/modules/Planner/planner_view_full_post_deleteProcess.php?gibbonPlannerEntryID=$gibbonPlannerEntryID&viewBy=$viewBy&subView=$subView&gibbonCourseClassID=$gibbonCourseClassID&date=$date&width=1000&height=550&search=$search&gibbonPlannerEntryDiscussID=".$rowDiscuss['gibbonPlannerEntryDiscussID']."'>Delete</a>";
                }
                $output .= "</td>";
            }
            $output .= "</tr>";
            $output .= "</table>";

            //Get any replies
            $replies = true;
            try {
                $dataReplies = array('gibbonPlannerEntryID' => $gibbonPlannerEntryID, 'gibbonPlannerEntryDiscussIDReplyTo' => $rowDiscuss['gibbonPlannerEntryDiscussID']);
                $sqlReplies = 'SELECT gibbonPlannerEntryDiscuss.*, title, surname, preferredName FROM gibbonPlannerEntryDiscuss JOIN gibbonPerson ON (gibbonPlannerEntryDiscuss.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPlannerEntryDiscussIDReplyTo=:gibbonPlannerEntryDiscussIDReplyTo ORDER BY timestamp';
                $resultReplies = $connection2->prepare($sqlReplies);
                $resultReplies->execute($dataReplies);
            } catch (PDOException $e) {
                $replies = false;
            }

            if ($replies) {
                while ($rowReplies = $resultReplies->fetch()) {
                    $output .= getThread($guid, $connection2, $gibbonPlannerEntryID, $rowDiscuss['gibbonPlannerEntryDiscussID'], ($level + 1), $rowReplies['gibbonPlannerEntryDiscussID'], $viewBy, $subView, $date, $class, $gibbonCourseClassID, $search, $role, $links);
                }
            }
        }
    }

    return $output;
}

function sidebarExtra($guid, $connection2, $todayStamp, $gibbonPersonID, $dateStamp = '', $gibbonCourseClassID = '')
{
    global $pdo, $session, $container;

    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $output = "<div class='error'>";
        $output .= __('The highest grouped action cannot be determined.');
        $output .= '</div>';
    } else {
        //Show date picker in sidebar
        $output = '<div class="column-no-break">';

        // Date Chooser
        $form = Form::create('dateChooser', $session->get('absoluteURL').'/index.php', 'get');
        $form->setTitle(__('Choose A Date'));
        $form->setClass('smallIntBorder w-full');

        $form->addHiddenValue('q', '/modules/Planner/planner.php');
        $form->addHiddenValue('search', $gibbonPersonID);

        $row = $form->addRow();
            $row->addDate('dateHuman', $session->get('gibbonSchoolYearID'), $gibbonPersonID)
                ->setValue(Format::date($dateStamp ? date('Y-m-d', $dateStamp) : ''))
                ->setID('dateHuman')
                ->setClass('float-none w-full');
            $row->addSubmit(__('Go'));

        $output .= $form->getOutput();

        // Class Chooser
        $form = Form::create('classChooser', $session->get('absoluteURL').'/index.php', 'get');
        $form->setFactory(DatabaseFormFactory::create($pdo));
        $form->setTitle(__('Choose A Class'));
        $form->setClass('smallIntBorder w-full');

        $form->addHiddenValue('q', '/modules/Planner/planner.php');
        $form->addHiddenValue('search', $gibbonPersonID);
        $form->addHiddenValue('viewBy', 'class');

        $classes = [];

        $dataSelect = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $gibbonPersonID);
        $sqlSelect = "SELECT gibbonCourseClass.gibbonCourseClassID as value, CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) as name FROM gibbonCourseClassPerson JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND gibbonCourseClassPerson.role NOT LIKE '% - Left' ORDER BY gibbonCourse.nameShort, gibbonCourseClass.nameShort";
        $resultSelect = $connection2->prepare($sqlSelect);
        $resultSelect->execute($dataSelect);

        if ($resultSelect->rowCount() > 0) {
            $classes[__('My Classes')] = $resultSelect->fetchAll(PDO::FETCH_KEY_PAIR);
        }

        if ($highestAction == 'Lesson Planner_viewEditAllClasses' or $highestAction == 'Lesson Planner_viewAllEditMyClasses' or $highestAction == 'Lesson Planner_viewOnly') {
            $dataSelect = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
            $sqlSelect = "SELECT gibbonCourseClass.gibbonCourseClassID, CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) as name FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY gibbonCourse.nameShort, gibbonCourseClass.nameShort";
            $resultSelect = $connection2->prepare($sqlSelect);
            $resultSelect->execute($dataSelect);

            if ($resultSelect->rowCount() > 0) {
                $classes[__('All Classes')] = $resultSelect->fetchAll(PDO::FETCH_KEY_PAIR);
            }
        }

        $row = $form->addRow();
            $row->addSelect('gibbonCourseClassID', $session->get('gibbonSchoolYearID'), $gibbonPersonID)
                ->setID('gibbonCourseClassIDSidebar')
                ->fromArray($classes)
                ->selected($gibbonCourseClassID)
                ->placeholder()
                ->setClass('float-none w-full');
            $row->addSubmit(__('Go'));

        $output .= $form->getOutput();
        $output .= '</div>';


        if ($_GET['q'] != '/modules/Planner/planner_deadlines.php') {
            $homeworkNamePlural = $container->get(SettingGateway::class)->getSettingByScope('Planner', 'homeworkNamePlural');

            //Show upcoming deadlines
            $output .= '<div class="column-no-break">';
            $output .= '<h2>';
            $output .= __('{homeworkName} + Due Dates', ['homeworkName' => __($homeworkNamePlural)]);
            $output .= '</h2>';

            global $container, $page, $gibbon;

            $plannerGateway = $container->get(PlannerEntryGateway::class);

            if ($highestAction == 'Lesson Planner_viewMyChildrensClasses') {
                $deadlines = $plannerGateway->selectUpcomingHomeworkByStudent($session->get('gibbonSchoolYearID'), $gibbonPersonID, 'viewableParents')->fetchAll();
            } elseif ($highestAction == 'Lesson Planner_viewEditAllClasses' or $highestAction == 'Lesson Planner_viewAllEditMyClasses' or $highestAction == 'Lesson Planner_viewMyClasses' or $highestAction == 'Lesson Planner_viewOnly') {
                $deadlines = $plannerGateway->selectUpcomingHomeworkByStudent($session->get('gibbonSchoolYearID'), $gibbonPersonID, 'viewableStudents')->fetchAll();
            }

            $output .= $page->fetchFromTemplate('ui/upcomingDeadlines.twig.html', [
                'gibbonPersonID' => $gibbonPersonID,
                'deadlines' => $deadlines,
                'hideLessonName' => true,
                'heading' => 'h4'
            ]);


            $output .= "<p style='padding-top: 15px; text-align: right'>";
            $output .= "<a href='".$session->get('absoluteURL')."/index.php?q=/modules/Planner/planner_deadlines.php&search=$gibbonPersonID'>".__('View {homeworkName}', ['homeworkName' => __($homeworkNamePlural)])."</a>";
            $output .= '</p>';
            $output .= '</div>';
        }
    }

    return $output;
}

function sidebarExtraUnits($guid, $connection2, $gibbonCourseID, $gibbonSchoolYearID)
{
    global $container, $session;

    $output = '';
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $output = Format::error(__('The highest grouped action cannot be determined.'));
    } else {
        // Show class picker in sidebar
        $courseGateway = $container->get(CourseGateway::class);

        if ($highestAction == 'Unit Planner_all') {
            $courses = $courseGateway->selectCoursesBySchoolYear($gibbonSchoolYearID)->fetchKeyPair();
        } elseif ($highestAction == 'Unit Planner_learningAreas') {
            $courses = $courseGateway->selectCoursesByPerson($gibbonSchoolYearID, $session->get('gibbonPersonID'))->fetchKeyPair();
        }

        $form = Form::create('courseChooser', $session->get('absoluteURL').'/index.php', 'get');
        $form->setTitle(__('Choose A Course'));
        $form->setClass('smallIntBorder w-full');

        $form->addHiddenValue('q', '/modules/Planner/units.php');
        $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);
        $form->addHiddenValue('viewBy', 'class');

        $row = $form->addRow();
            $row->addSelect('gibbonCourseID')
                ->setID('gibbonCourseIDSidebar')
                ->fromArray($courses)
                ->selected($gibbonCourseID)
                ->placeholder()
                ->setClass('float-none w-full');
            $row->addSubmit(__('Go'));

        $output .= $form->getOutput();
    }

    return $output;
}

//Make the display for a block, according to the input provided, where $i is a unique number appended to the block's field ids.
function makeBlockOutcome($guid,  $i, $type = '', $gibbonOutcomeID = '', $title = '', $category = '', $contents = '', $id = '', $outerBlock = true, $allowOutcomeEditing = 'Y')
{
    global $session;

    if ($outerBlock) {
        echo "<div id='".$type."blockOuter$i' class='blockOuter'>";
    }
    ?>
		<script>
			$(function() {
				$( "#<?php echo $type ?>" ).sortable({
					placeholder: "<?php echo $type ?>-ui-state-highlight"
				});

				$( "#<?php echo $type ?>" ).bind( "sortstart", function(event, ui) {
					$("#<?php echo $type ?>BlockInner<?php echo $i ?>").css("display","none");
					$("#<?php echo $type ?>Block<?php echo $i ?>").css("height","82px") ;
					$('#<?php echo $type ?>show<?php echo $i ?>').css("background-image", "<?php echo "url(\'".$session->get('absoluteURL').'/themes/'.$session->get('gibbonThemeName')."/img/plus.png\'"?>)");
					tinyMCE.execCommand('mceRemoveEditor', false, '<?php echo $type ?>contents<?php echo $i ?>') ;
					$("#<?php echo $type ?>").sortable( "refreshPositions" ) ;
				});

				$( "#<?php echo $type ?>" ).bind( "sortstop", function(event, ui) {
					//This line has been removed to improve performance with long lists
					//tinyMCE.execCommand('mceAddEditor', false, '<?php echo $type ?>contents<?php echo $i ?>') ;
					$("#<?php echo $type ?>Block<?php echo $i ?>").css("height","82px") ;
				});
			});
		</script>
		<script type="text/javascript">
			$(document).ready(function(){
				$("#<?php echo $type ?>BlockInner<?php echo $i ?>").css("display","none");
				$("#<?php echo $type ?>Block<?php echo $i ?>").css("height","82px") ;

				//Block contents control
				$('#<?php echo $type ?>show<?php echo $i ?>').unbind('click').click(function() {
					if ($("#<?php echo $type ?>BlockInner<?php echo $i ?>").is(":visible")) {
						$("#<?php echo $type ?>BlockInner<?php echo $i ?>").css("display","none");
						$("#<?php echo $type ?>Block<?php echo $i ?>").css("height","82px") ;
						$('#<?php echo $type ?>show<?php echo $i ?>').css("background-image", "<?php echo "url(\'".$session->get('absoluteURL').'/themes/'.$session->get('gibbonThemeName')."/img/plus.png\'"?>)");
						tinyMCE.execCommand('mceRemoveEditor', false, '<?php echo $type ?>contents<?php echo $i ?>') ;
					} else {
						$("#<?php echo $type ?>BlockInner<?php echo $i ?>").slideDown("fast", $("#<?php echo $type ?>BlockInner<?php echo $i ?>").css("display","table-row"));
						$("#<?php echo $type ?>Block<?php echo $i ?>").css("height","auto")
						$('#<?php echo $type ?>show<?php echo $i ?>').css("background-image", "<?php echo "url(\'".$session->get('absoluteURL').'/themes/'.$session->get('gibbonThemeName')."/img/minus.png\'"?>)");
						tinyMCE.execCommand('mceRemoveEditor', false, '<?php echo $type ?>contents<?php echo $i ?>') ;
						tinyMCE.execCommand('mceAddEditor', false, '<?php echo $type ?>contents<?php echo $i ?>') ;
					}
				});

				$('#<?php echo $type ?>delete<?php echo $i ?>').unbind('click').click(function() {
					if (confirm("Are you sure you want to delete this record?")) {
						$('#<?php echo $type ?>blockOuter<?php echo $i ?>').fadeOut(600, function(){ $('#<?php echo $type ?><?php echo $i ?>'); });
						$('#<?php echo $type ?>blockOuter<?php echo $i ?>').remove();
						<?php echo $type ?>Used[<?php echo $type ?>Used.indexOf("<?php echo $gibbonOutcomeID ?>")]="x" ;
					}
				});

			});
		</script>
		<div class='hiddenReveal' style='border: 1px solid #d8dcdf; margin: 0 0 5px;' id="<?php echo $type ?>Block<?php echo $i ?>" style='padding: 0px'>
			<table class='blank' cellspacing='0' style='width: 100%'>
				<tr>
					<td style='width: 50%'>
						<input name='<?php echo $type ?>order[]' type='hidden' value='<?php echo $i ?>'>
						<input name='<?php echo $type ?>gibbonOutcomeID<?php echo $i ?>' type='hidden' value='<?php echo $gibbonOutcomeID ?>'>
						<input readonly maxlength=100 id='<?php echo $type ?>title<?php echo $i ?>' name='<?php echo $type ?>title<?php echo $i ?>' type='text' style='float: none; border: 1px dotted #aaa; background: none; margin-left: 3px; margin-top: 0px; font-size: 140%; font-weight: bold; width: 350px' value='<?php echo $title; ?>'><br/>
						<input readonly maxlength=100 id='<?php echo $type ?>category<?php echo $i ?>' name='<?php echo $type ?>category<?php echo $i ?>' type='text' style='float: left; border: 1px dotted #aaa; background: none; margin-left: 3px; margin-top: 2px; font-size: 110%; font-style: italic; width: 250px' value='<?php echo $category; ?>'>
						<script type="text/javascript">
							if($('#<?php echo $type ?>category<?php echo $i ?>').val()=="") {
								$('#<?php echo $type ?>category<?php echo $i ?>').css("border","none") ;
							}
						</script>
					</td>
					<td style='text-align: right; width: 50%'>
						<div style='margin-bottom: 25px'>
							<?php
                            echo "<img id='".$type."delete$i' title='".__('Delete')."' src='./themes/".$session->get('gibbonThemeName')."/img/garbage.png'/> ";
   	 						echo "<div id='".$type."show$i' title='".__('Show/Hide Details')."' style='margin-right: 3px; margin-left: 3px; padding-right: 1px; float: right; width: 25px; height: 25px; background-image: url(\"".$session->get('absoluteURL').'/themes/'.$session->get('gibbonThemeName')."/img/plus.png\"); background-repeat: no-repeat'></div>"; ?>
						</div>
						<input type='hidden' name='id<?php echo $i ?>' value='<?php echo $id ?>'>
					</td>
				</tr>
				<tr id="<?php echo $type ?>BlockInner<?php echo $i ?>">
					<td colspan=2 style='vertical-align: top'>
						<?php
                            if ($allowOutcomeEditing == 'Y') {
                                $editor = (new Editor($type.'contents'.$i))
                                    ->tinymceInit(false)
                                    ->setValue($contents)
                                    ->setRows(20)
                                    ->showMedia(false)
                                    ->setRequired(false)
                                    ->initiallyHidden(false)
                                    ->allowUpload(true);
                                echo $editor->getOutput();
                            } else {
                                echo "<div style='padding: 5px'>$contents</div>";
                                echo "<input type='hidden' name='".$type.'contents'.$i."' value='".htmlPrep($contents)."'/>";
                            }
   				 			?>
					</td>
				</tr>
			</table>
		</div>
	<?php
    if ($outerBlock) {
        echo '</div>';
    }
}

//Returns all tags, in the specified school year if one is specified
function getTagList($connection2, $gibbonSchoolYearID = null) {
    $tags = [];

    //Get all tags
    try {
        if (is_null($gibbonSchoolYearID)) {
            $dataList = array();
            $sqlList = 'SELECT tags FROM gibbonUnit';
        }
        else {
            $dataList = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
            $sqlList = "SELECT tags FROM gibbonUnit JOIN gibbonCourse ON (gibbonUnit.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' AND gibbonUnit.map='Y' AND gibbonCourse.map='Y' AND NOT tags=''";
        }
        $resultList = $connection2->prepare($sqlList);
        $resultList->execute($dataList);
    } catch (PDOException $e) {}

    //First pass through of tags to create a raw array, ordered alphabetically
    while ($rowList = $resultList->fetch()) {
        $tagsInner = explode(',', $rowList['tags']);
        foreach ($tagsInner AS $tagInner) {
            $tags[] = mb_strtolower(trim($tagInner));
        }
    }
    sort($tags, SORT_STRING) ;

    $tagCounts = array_count_values($tags);
    $tags = array_unique($tags);

    $tags = array_map(function($item, $key) use ($tagCounts) {
        return [$key, $item, $tagCounts[$item] ?? 0];
    }, $tags, array_keys($tags));

    return $tags;
}

function getTagCloud($guid, $connection2, $gibbonSchoolYearID = null) {
    global $session;

    $output = '';

    $tags = getTagList($connection2, $gibbonSchoolYearID);

    if (count($tags) > 0) {
        $max_count = 0;
        $min_count = null;
        foreach ($tags as $tag) {
            if (is_null($min_count)) {
                $min_count = $tag[2];
            }
            else {
                if ($tag[2] < $min_count) {
                    $min_count = $tag[2];
                }
            }

            if ($tag[2] > $max_count) {
                $max_count = $tag[2];
            }
        }


        $min_font_size = 16;
        $max_font_size = 30;

        $spread = $max_count - $min_count;
        if ($spread == 0) {
            $spread = 1;
        }

        $cloud_html = '';
        $cloud_tags = array();
        for ($i = 0; $i < count($tags); ++$i) {
            $tag = $tags[$i][1];
            $count = $tags[$i][2];
            $size = $min_font_size + ($count - $min_count) * ($max_font_size - $min_font_size) / $spread;
            $cloud_tags[] = "<a style='font-size: ".floor($size)."px' class='tag_cloud' href='".$session->get('absoluteURL').'/index.php?q=/modules/Planner/conceptExplorer.php&tag='.str_replace('&', '%26', $tag)."' title='$count units'>".htmlspecialchars(stripslashes($tag)).'</a>';
        }
        $output .= "<p style='margin-top: 10px; line-height: 130%'>";
        $output .= implode("\n", $cloud_tags)."\n";
        $output .= '</p>';
    } else {
        $output .= "<div class='warning'>";
        $output .= __('There are no concepts in the system.');
        $output .= '</div>';
    }

    return $output;
}

function getResourcesTagCloud($guid, $connection2, $tagCount = 50) {
    global $session;

    $output = '';

    //Get array of top $tagCount tags
    $tags = array();
    $count = 0;
    $max_count = 0;
    $min_count = 0;
    $tagCount = intval($tagCount);

    $sql = "SELECT * FROM gibbonResourceTag ORDER BY count DESC LIMIT $tagCount";
    $data = array();
    $result = $connection2->prepare($sql);
    $result->execute($data);

    if ($result->rowCount() > 0) {
        while ($row = $result->fetch()) {
            if ($count == 0) {
                $max_count = $row['count'];
                $min_count = $row['count'];
            } else {
                if ($row['count'] < $min_count) {
                    $min_count = $row['count'];
                }
            }
            $tags[$count][0] = $row['tag'];
            $tags[$count][1] = $row['count'];

            ++$count;
        }

        // Sort tags by the value of their tag name (i.e. key=0) asecendingly.
        usort($tags, fn($a, $b) => $a[0] <=> $b[0]);

        $min_font_size = 16;
        $max_font_size = 30;

        $spread = $max_count - $min_count;
        if ($spread == 0) {
            $spread = 1;
        }

        $cloud_html = '';
        $cloud_tags = array();
        for ($i = 0; $i < count($tags); ++$i) {
            $tag = $tags[$i][0];
            $count = $tags[$i][1];
            $size = $min_font_size + ($count - $min_count) * ($max_font_size - $min_font_size) / $spread;
            $cloud_tags[] = "<a style='font-size: ".floor($size)."px' class='tag_cloud' href='".$session->get('absoluteURL').'/index.php?q=/modules/Planner/resources_view.php&tag='.str_replace('&', '%26', $tag)."' title='$count resources'>".htmlspecialchars(stripslashes($tag)).'</a>';
        }
        $output .= "<p style='margin-top: 10px; line-height: 220%'>";
        $output .= implode("\n", $cloud_tags)."\n";
        $output .= '</p>';
    } else {
        $output .= "<div class='warning'>";
        $output .= __('There are no resources in the system.');
        $output .= '</div>';
    }

    return $output;
}

function sidebarExtraResources($guid, $connection2)
{
    $output = '';
    $output .= '<h2>';
    $output .= __('Resource Tags');
    $output .= '</h2>';
    $output .= getResourcesTagCloud($guid, $connection2);

    return $output;
}

function getResourceLink($guid, $gibbonResourceID, $type, $name, $content)
{
    global $session;

    $output = false;

    if ($type == 'Link') {
        $output = "<a target='_blank' style='font-weight: bold' href='".$content."'>".$name.'</a><br/>';
    } elseif ($type == 'File') {
        $output = "<a target='_blank' style='font-weight: bold' href='".$session->get('absoluteURL').'/'.$content."'>".$name.'</a><br/>';
    } elseif ($type == 'HTML') {
        $output = "<a style='font-weight: bold' class='thickbox' href='".$session->get('absoluteURL').'/fullscreen.php?q=/modules/Planner/resources_view_full.php&gibbonResourceID='.$gibbonResourceID."&width=1000&height=550'>".$name.'</a><br/>';
    }

    return $output;
}

?>
