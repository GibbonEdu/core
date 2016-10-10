<?php
$staff = new Gibbon\People\employee($this);
$smartWorkflowHelp = $staff->getSmartWorkflowHelp() ;
if (! $smartWorkflowHelp) 
	echo $smartWorkflowHelp ;
?>                                
<h2><?php print $this->__( "Staff Dashboard") ;?></h2>
<div style='margin-bottom: 30px; margin-left: 1%; float: left; width: 100%'>
<?php
$dashboardContents = $staff->getStaffDashboardContents($this->session->get("gibbonPersonID")) ;
if (! $dashboardContents ) 
$this->displayMessage("There are no records to display."); 
else 
print $dashboardContents ;
?>
</div>
