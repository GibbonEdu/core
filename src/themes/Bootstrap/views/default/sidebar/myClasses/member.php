<tr>
    <td style='word-wrap: break-word'>
        <a href='<?php echo $this->session->get("absoluteURL"); ?>/index.php?q=/modules/Departments/department_course_class.php&gibbonCourseClassID=<?php echo $el->gibbonCourseClassID; ?>'><?php echo $el->course; ?>.<?php echo $el->class; ?></a>
    </td>
    <?php if ($this->getSecurity()->isActionAccessible("/modules/Planner/planner.php", null, '')) { ?>
        <td class='center'>
        	<?php 
			$this->getLink('planner', array('q'=>'/modules/Planner/planner.php', 'gibbonCourseClassID'=>$el->gibbonCourseClassID, 'viewBy'=>'class', 'title'=>'View Planner', 'class'=>'noDecoration'));
			//$el->link = $this->convertGetArraytoURL(array('q'=>'/modules/Planner/planner.php', 'gibbonCourseClassID'=>$el->gibbonCourseClassID, 'viewBy'=>'class'));
			?>
        </td>
    <?php }
    if ($this->getSecurity()->getHighestGroupedAction("/modules/Markbook/markbook_view.php")=="View Markbook_allClassesAllData") { ?>
        <td class='center'>
        	<?php 
			$this->getLink('markbook', array('q'=>'/modules/Markbook/markbook_view.php', 'gibbonCourseClassID'=>$el->gibbonCourseClassID, 'title'=>'View Markbook', 'class'=>'noDecoration'));
			?>
        </td>
    <?php } ?>
    <td class='center'>
		<?php 
        $this->getLink('attendance', array('q'=>'/modules/Departments/department_course_class.php', 'gibbonCourseClassID'=>$el->gibbonCourseClassID, 'subpage'=>'Participants', 'title'=>'Participants', 'class'=>'noDecoration'));
        ?>
    </td>
    <?php if ($this->getSecurity()->isActionAccessible("/modules/Planner/planner.php", null, '')) { ?>
        <td class='center'>
			<?php 
            $this->getLink('homework', array('q'=>'/modules/Planner/planner_deadlines.php', 'gibbonCourseClassID'=>$el->gibbonCourseClassID,  'title'=>'View Homework', 'class'=>'noDecoration'));
            ?>
        </td>
    <?php } ?>
</tr>
