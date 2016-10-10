<tr<?php echo ! empty($el->rowNum) ? ' class="'.$el->rowNum.'"' : '' ; ?>>
    <td>
		<?php echo $el->course.'.'.$el->class; ?><br/>
        <span style='font-style: italic; font-size: 75%'><?php echo substr($el->timeStart, 0, 5).'-'.substr($el->timeEnd, 0, 5)?></span>
    </td>
    <td>
        <strong><?php echo $el->name; ?>'</strong><br/>
        <div style='font-size: 85%; font-style: italic'>
        <?php if (isset($el->unit[0])) {
             echo $unit[0];
            if (! empty($unit[1])) { ?>
                <br/><em><?php echo $unit[1].' '.$this->__('Unit'); ?></em><?php
            }
        } ?>
        </div>
    </td>
    <td>
        <?php if ($el->homework == 'N' && empty($el->myHomeworkDueDateTime)) {
             $this->__('N');
        } else {
            if ($el->homework == 'Y') { 
                $this->__('Yes')?>: <?php echo $this->__('Teacher Recorded')?><br /><?php
                if ($el->homeworkSubmission == 'Y') { ?>
                    <span style='font-size: 85%; font-style: italic'>+<?php echo $this->__('Submission'); ?></span><br /><?php 
                    if ($el->homeworkCrowdAssess == 'Y') { ?>
                        <span style='font-size: 85%; font-style: italic'>+<?php echo $this->__('Crowd Assessment'); ?></span><br/><?php
                    }
                }
            }
            if (! empty($el->myHomeworkDueDateTime)) {
                 echo $this->view->__('Y').': '.$this->view->__('Student Recorded').'<br />';
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
		}?>
    </td>
    <td>
		<?php echo $this->getLink('view details', array('q'=>'/modules/Planner/planner_view_full.php', 'gibbonPlannerEntryID' => $el->gibbonPlannerEntryID, 'viewBy'=>'Class', 'gibbonCourseClassID'=> $el->gibbonCourseClassID)); ?> 
    </td>
</tr>
