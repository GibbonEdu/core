<?php
 //Roll group table
$links = array();
$links['attendance'] = array('q' => '/modules/Attendance/attendance_take_byRollGroup.php', 'gibbonRollGroupID' => $el->gibbonRollGroupID, 'prompt' => 'Take Attendance');
$links['download'] = array('q' => '/modules/Roll Groups/indexExport.php', 'divert' => true, 'gibbonRollGroupID' => $el->gibbonRollGroupID, 'prompt' => 'Download Excel');

$this->linkTop($links);
$this->h4('Attendance');

echo $this->getRecord('studentEnrolment')->getRollGroupTable($el->gibbonRollGroupID, 5);
