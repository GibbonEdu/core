<!-- //Roll group table -->
<div class='linkTop' style='margin-top: 0px'>
	<?php
	$this->getLink('attendance', array('q' => '/modules/Attendance/attendance_take_byRollGroup.php', 'gibbonRollGroupID'=>$el->gibbonRollGroupID), 'Take Attendance');
	$this->getLink('download', array('q' => '/indexExport.php', 'gibbonRollGroupID'=>$el->gibbonRollGroupID), 'Export to Excel');
	?>
</div>
<?php $this->getRecord('studentEnrolment')->getRollGroupTable($el->gibbonRollGroupID, 5);
