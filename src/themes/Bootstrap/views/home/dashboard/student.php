<?php
use Gibbon\People\student ;
$student = new student($this, $this->session->get("gibbonPersonID"));
$this->h2("Student Dashboard") ; ?>
<div style='margin-bottom: 30px; margin-left: 1%; float: left; width: 100%'>
	<?php echo $student->getStudentDashboardContents($this->session->get("gibbonPersonID")) ; ?>
</div>
