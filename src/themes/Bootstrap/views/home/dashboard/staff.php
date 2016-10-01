<?php
$staff = new Gibbon\People\employee($this);
$smartWorkflowHelp = $staff->getSmartWorkflowHelp() ;

$this->h2('Staff Dashboard');
?>                                
<div style='margin-bottom: 30px; margin-left: 1%; float: left; width: 100%'>
	<?php
	$dashboardContents = $staff->getStaffDashboardContents($this->session->get("gibbonPersonID")) ;
	if (! $dashboardContents ) 
		$this->displayMessage("There are no records to display."); 
	else 
		print $dashboardContents ;
?>
</div>
