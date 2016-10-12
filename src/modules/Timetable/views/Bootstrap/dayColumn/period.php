<div class='<?php echo $el->class2; ?>' <?php echo $el->title; ?> style='z-index: <?php echo $el->zCount; ?>; position: absolute; top: <?php echo $el->top; ?>; width: <?php echo $el->width; ?>; height: <?php echo $el->height; ?>; margin: 0px; padding: 0px; opacity: <?php echo $el->ttAlpha;?>;'><?php
	if ($el->height >= 45) {
		echo $el->name.'<br/>';
		echo '<em>'.substr($el->effectiveStart, 0, 5).' - '.substr($el->effectiveEnd, 0, 5).'</em><br/>';
	}
	
	if ($this->getSecurity()->isActionAccessible('/modules/Departments/department_course_class.php') && ! $el->edit) { ?>
		<a style='text-decoration: none; font-weight: bold; font-size: 120%' href='<?php echo GIBBON_URL; ?>index.php?q=/modules/Departments/department_course_class.php&gibbonCourseClassID=<?php echo $el->gibbonCourseClassID; ?>'><?php echo $el->course.'.'.$el->class; ?></a><br/><?php
	} elseif ($this->getSecurity()->isActionAccessible('/modules/Timetable Admin/courseEnrolment_manage_class_edit.php') && $el->edit) { ?>
		<a style='text-decoration: none; font-weight: bold; font-size: 120%' href='<?php echo GIBBON_URL; ?>index.php?q=/modules/Timetable Admin/courseEnrolment_manage_class_edit.php&gibbonCourseClassID=<?php echo $el->gibbonCourseClassID; ?>&gibbonSchoolYearID=<?php echo $this->view->session->get('gibbonSchoolYearID'); ?>&gibbonCourseID=<?php echo $el->gibbonCourseID; ?>'><?php echo $el->course.'.'.$el->class; ?></a><br/><?php
	} else { ?>
		<span style='font-size: 120%'><strong><?php echo $el->course.'.'.$el->class; ?></strong></span><br/><?php
	}

	if ($el->height >= 60) {
		if (! $el->edit) {
			if (empty($el->spaceChanges[$el->gibbonTTDayRowClassID])) {
				echo $el->roomName;
			} else {
				if (! empty($el->spaceChanges[$el->gibbonTTDayRowClassID][0])) { ?>
					<span style='border: 1px solid #c00; padding: 0 2px'><?php echo $el->spaceChanges[$el->gibbonTTDayRowClassID][0]; ?></span><?php
				} else { ?>
					<span style='border: 1px solid #c00; padding: 0 2px'><em><?php echo $this->__('No Space Allocated'); ?></em></span><?php
				}
			}
		} else { ?>
			<a href='<?php echo GIBBON_URL; ?>index.php?q=/modules/Timetable Admin/tt_edit_day_edit_class_edit.php&gibbonTTDayID=<?php echo $el->gibbonTTDayID; ?>&gibbonTTID=<?php echo $el->gibbonTTID; ?>&gibbonSchoolYearID=<?php echo $this->view->session->get('gibbonSchoolYearID'); ?>&gibbonTTColumnRowID=<?php echo $el->gibbonTTColumnRowID; ?>&gibbonTTDayRowClass=<?php echo $el->gibbonTTDayRowClassID; ?>&gibbonCourseClassID=<?php echo $el->gibbonCourseClassID; ?>'><?php echo $el->roomName; ?></a><?php
		}
	} ?>
</div>
