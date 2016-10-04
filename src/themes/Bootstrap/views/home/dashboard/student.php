<?php
use Gibbon\Person\student ;
$student = new student($this, $this->session->get("gibbonPersonID"));
$this->injectModuleCSS('Timetable');
?>
<h2>
	<?php print Gibbon\core\trans::__( "Student Dashboard") ; ?>
</h2>
<div style='margin-bottom: 30px; margin-left: 1%; float: left; width: 100%'>
	<?php $dashboardContents = $student->getStudentDashboardContents($this->session->get("gibbonPersonID")) ;
	if (! $dashboardContents) { ?>
    <div class='error'>
		<?php print Gibbon\core\trans::__( "There are no records to display.") ; ?>
	</div>
	<?php
	} else {
		print $dashboardContents ;
	} ?>
</div>
