<?php
//Highlight class in progress
$rowNum = '';
if ((date('H:i:s') > $el->timeStart) && (date('H:i:s') < $el->timeEnd) && ($el->date) == date('Y-m-d')) {
	$rowNum =  ' class="current"';
}

//COLOR ROW BY STATUS! ?>
<tr<?php echo $rowNum; ?>>
    <td>
        <?php echo $el->course.'.'.$el->class; ?><br/>
        <span style='font-style: italic; font-size: 75%'><?php echo mb_substr($el->timeStart, 0, 5).'-'.mb_substr($el->timeEnd, 0, 5); ?></span>
    </td>
    <td>
        <strong><?php echo $el->name; ?></strong><br/>
        <span style='font-size: 85%; font-style: italic'>
        <?php if (isset($el->unit[0])) {
            $el->unit[0];
            if ($el->unit[1] != '') { ?>
                <br/><em><?php echo $el->unit[1].' '.$this->view->__('Unit'); ?></em><?php
            }
        } ?>
        </span>
    </td>
    <td>
		<?php if ($el->homework == 'N' && empty($el->myHomeworkDueDateTime)) {
            $this->view->__('No');
        } else {
            if ($el->homework == 'Y') {
                echo $this->view->__('Yes').': '.$this->view->__('Teacher Recorded').'<br/>';
                if ($el->homeworkSubmission == 'Y') { ?>
                    <span style='font-size: 85%; font-style: italic'>+<?php echo $this->view->__('Submission'); ?></span><br/><?php
                    if ($el->homeworkCrowdAssess == 'Y') { ?>
                        <span style='font-size: 85%; font-style: italic'>+<?php echo $this->view->__('Crowd Assessment'); ?></span><br/><?php
                    }
                }
            }
            if (! empty($el->myHomeworkDueDateTime)) {
                echo $this->view->__('Yes').': '.$this->view->__('Student Recorded').'</br>';
            }
        } ?>
    </td>
    <td>
    	<?php echo $el->summary; ?>
    </td>

    <td>
		<?php
		if ($row->role == 'Teacher')
			echo $el->likesGiven ;
		else 
		{
			if ($el->likesGiven != 1) {
				echo $this->getLink('like off', array('q'=>'/modules/Planner/plannerProcess.php', 'gibbonPlannerEntryID' => $el->gibbonPlannerEntryID, 'address'=>'/modules/Planner/planner.php', 'viewBy'=>'Class', 'gibbonCourseClassID'=> $el->gibbonCourseClassID, 'date'=>'', 'returnToIndex'=>'Y')); 
			} else {
				echo $this->getLink('like on', array('q'=>'/modules/Planner/plannerProcess.php', 'gibbonPlannerEntryID' => $el->gibbonPlannerEntryID, 'address'=>'/modules/Planner/planner.php', 'viewBy'=>'Class', 'gibbonCourseClassID'=> $el->gibbonCourseClassID, 'date'=>'', 'returnToIndex'=>'Y')); 
			}
		} ?>
    </td>
    <td>
		<?php echo $this->getLink('view details', array('q'=>'/modules/Planner/planner_view_full.php', 'gibbonPlannerEntryID' => $el->gibbonPlannerEntryID, 'viewBy'=>'Class', 'gibbonCourseClassID'=> $el->gibbonCourseClassID)); ?> 
    </td>
</tr>
