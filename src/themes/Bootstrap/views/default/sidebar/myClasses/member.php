<tr>
    <td style='word-wrap: break-word'>
        <a href='<?php echo $this->session->get("absoluteURL"); ?>/index.php?q=/modules/Departments/department_course_class.php&gibbonCourseClassID=<?php echo $params->gibbonCourseClassID; ?>'><?php echo $params->course; ?>.<?php echo $params->class; ?></a>
    </td>
    <?php if ($this->getSecurity()->isActionAccessible("/modules/Planner/planner.php", null, '')) { ?>
        <td style='text-align: center'>
        	<?php $params->link = $this->convertGetArraytoURL(array('q'=>'/modules/Planner/planner.php', 'gibbonCourseClassID'=>$params->gibbonCourseClassID, 'viewBy'=>'class'));
			$params->name = '';
			$params->title = 'View Planner';
			$params->linkClass = 'noDecoration';
			$this->render('button.planner', $params); ?>
        </td>
    <?php }
    if ($this->getSecurity()->getHighestGroupedAction("/modules/Markbook/markbook_view.php")=="View Markbook_allClassesAllData") { ?>
        <td style='text-align: center'>
        	<?php $params->link = $this->convertGetArraytoURL(array('q'=>'/modules/Markbook/markbook_view.php', 'gibbonCourseClassID'=>$params->gibbonCourseClassID));
			$params->name = '';
			$params->title = 'View Markbook';
			$params->linkClass = 'noDecoration';
			$this->render('button.markbook', $params); ?>
        </td>
    <?php } ?>
    <td style='text-align: center'>
        	<?php $params->link = $this->convertGetArraytoURL(array('q'=>'/modules/Departments/department_course_class.php', 'gibbonCourseClassID'=>$params->gibbonCourseClassID, 'subpage'=>'Participants'));
			$params->name = '';
			$params->title = 'Participants';
			$params->linkClass = 'noDecoration';
			$this->render('button.attendance', $params); ?>
    </td>
    <?php if ($this->getSecurity()->isActionAccessible("/modules/Planner/planner.php", null, '')) { ?>
        <td style='text-align: center'>
        	<?php $params->link = $this->convertGetArraytoURL(array('q'=>'/modules/Planner/planner_deadlines.php', 'gibbonCourseClassIDFilter'=>$params->gibbonCourseClassID));
			$params->name = '';
			$params->title = 'View Homework';
			$params->linkClass = 'noDecoration';
			$this->render('button.homework', $params); ?>
        </td>
    <?php } ?>
</tr>
