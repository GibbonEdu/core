<?php
//Highlight class in progress
$rowNum = '';
if ((date('H:i:s') > $el->getField('timeStart')) and (date('H:i:s') < $el->getField('timeEnd')) and ($$el->date) == date('Y-m-d'))     $rowNum = ' class="current"';

?>
<tr<?php echo $rowNum; ?>>
    <td>
        <strong><?php echo $el->getField('course').$el->getField('class'); ?></strong><br/>
    </td>
	<td>
		<?php echo $el->getField('name'); ?><br/><?php
        if (isset($el->unit[0])) {
            echo $el->unit[0];
            if ($el->unit[1] != '') { ?>
                <br/><em><?php echo $el->unit[1]; ?> <?php echo $this->view->__('Unit'); ?></em><br/><?php
            }
        } ?>
		<span style='font-size: 85%; font-weight: normal; font-style: italic'>
			<?php echo $el->getField('summary'); ?>
		</span>
    </td>
    <td>
        <?php if ($el->getField('homework') == 'N' && $el->getField('myHomeworkDueDateTime') == '') {
            echo $this->__('No');
        } else {
            if ($el->getField('homework') == 'Y') {
                echo $this->__('Yes'); ?>: <?php echo $this->__('Teacher Recorded'); ?><br/><?php
                if ($el->getField('homeworkSubmission') == 'Y') { ?>
                    <span style='font-size: 85%; font-style: italic'>+<?php echo $this->__('Submission'); ?></span><br/><?php
                    if ($el->getField('homeworkCrowdAssess') == 'Y') { ?>
                        <span style='font-size: 85%; font-style: italic'>+<?php echo $this->__('Crowd Assessment'); ?></span><br/><?php
                    }
                }
            }
            if ($el->getField('myHomeworkDueDateTime') != '') {
                echo $this->__('Yes'); ?>: <?php echo $this->__('Student Recorded'); ?></br><?php
            }
        } ?>
    </td>
    <td>
        <?php
        if ($el->likesGiven != 1) { 
				echo $this->getLink('like off', array('q'=>'/modules/Planner/plannerProcess.php', 'gibbonPlannerEntryID' => $el->getField('gibbonPlannerEntryID'), 'address'=>'/modules/Planner/planner.php', 'viewBy'=>'Date', 'date' => $el->date, 'returnToIndex'=>'Y', 'gibbonPersonID' => $el->personID)); 
			} else {
				echo $this->getLink('like on', array('q'=>'/modules/Planner/plannerProcess.php', 'gibbonPlannerEntryID' => $el->gibbonPlannerEntryID, 'address'=>'/modules/Planner/planner.php', 'viewBy'=>'Class', 'date' => $el->date, 'returnToIndex'=>'Y', 'gibbonPersonID' => $el->personID)); 
			} ?>
    </td>
    <td>
		<?php echo $this->getLink('view details', array('q'=>'/modules/Planner/planner_view_full.php', 'search' => $el->personID, 'gibbonPlannerEntryID' => $el->gibbonPlannerEntryID, 'viewBy'=>'Date', 'date' => $el->date, 'width'> 1000, ' height' => 550, 'title' => 'View')); ?> 
    </td>
</tr>
