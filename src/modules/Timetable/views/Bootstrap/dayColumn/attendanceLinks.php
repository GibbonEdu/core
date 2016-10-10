<div <?php echo $el->title; ?> style='z-index: <?php echo $el->zCount; ?>; position: absolute; top: <?php echo $el->top; ?>; width: <?php echo $el->width; ?>; border: 1px solid rgba(136,136,136, <?php echo $el->ttAlpha; ?>); height: <?php echo $el->height; ?>; margin: 0px; padding: 0px; background-color: none; pointer-events: none'>
<?php
$this->getLink('attendance', array('class' => 'attendanceLink-'.$el->height,  'href' => array('q' => '/modules/Timetable Admin/tt_edit_day_edit_class_exception.php', 'gibbonTTDayID' => $el->gibbonTTDayID, 'gibbonTTID' => $el->TTID, 'gibbonSchoolYearID' => $this->view->session->get('gibbonSchoolYearID'), 'gibbonTTColumnRowID' => $el->gibbonTTColumnRowID, 'gibbonTTDayRowClass' =>$el->gibbonTTDayRowClassID, 'gibbonCourseClassID' => $el->gibbonCourseClassID, 'title' => 'Manage Exceptions')));
?>
</div>
<!--
//Check for lesson plan
$bgImg = 'none';
$output .= "<a style='pointer-events: auto' href='".GIBBON_URL.'/index.php?q=/modules/Timetable Admin/tt_edit_day_edit_class_exception.php&gibbonTTDayID='.$rowPeriods['gibbonTTDayID']."&gibbonTTID=$TTID&gibbonSchoolYearID=".$this->view->session->get('gibbonSchoolYearID').'&gibbonTTColumnRowID='.$rowPeriods['gibbonTTColumnRowID'].'&gibbonTTDayRowClass='.$rowPeriods->getField('gibbonTTDayRowClassID').'&gibbonCourseClassID='.$rowPeriods->getField('gibbonCourseClassID')."'><img style='float: right; margin: ".(substr($this->td->height, 0, -2) - 27)."px 2px 0 0' title='".$this->view->__('Manage Exceptions')."' src='".GIBBON_URL.'/themes/'.$this->view->session->get('theme.Name')."/img/attendance.png'/></a>";
$output .= '</div>'; -->
