<div <?php echo $el->title; ?> style='z-index: <?php echo $el->zCount; ?>; position: absolute; top: <?php echo $el->top; ?>; width: <?php echo $el->width; ?>; border: 1px solid rgba(136,136,136, <?php echo $el->ttAlpha; ?>); height: <?php echo $el->height; ?>; margin: 0px; padding: 0px; background-color: none; pointer-events: none'>
<?php
if (count($el->plan) == 1) {
    $rowPlan = reset($el->plan); 
	$this->getLink('view_details', array('class' => 'otherPlannerLink-'.$el->height,  'href' => array('q' => '/modules/Planner/planner_view_full.php', 'viewBy' => 'class', 'gibbonCourseClassID' => $el->gibbonCourseClassID, 'gibbonPlannerEntryID' => $rowPlan->getField('gibbonPlannerEntryID'), 'search' => $el->personID), 'title' => 'View Lesson'));
} elseif (count($el->plan) > 1) { 
	$this->getLink('error', array('class' => 'otherPlannerLink-'.$el->height,  'href' => array('q' => '/modules/Planner/planner.php', 'viewBy' => 'class', 'gibbonCourseClassID' => $el->gibbonCourseClassID, 'date' => $el->date, 'timeStart' => $el->timeStart, 'timeEnd' => $el->effectiveEnd)));
} ?>
</div>
