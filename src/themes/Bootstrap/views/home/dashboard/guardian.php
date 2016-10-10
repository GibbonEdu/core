<?php
$this->h2('Parent Dashboard');

$students = $el->students;
$count = count($el);

for ($i=0; $i<$count; $i++) {
    ?>
    <h4><?php print $students[$i][1] . " " . $students[$i][0] ; ?></h4>
    <div style='margin-right: 1%; float:left; width: 15%; text-align: center'>
    <?php $el->getUserPhoto($students[$i][5], 75) ; ?>
    	<div style='height: 5px'></div>
       		<span style='font-size: 70%'>
            	<?php
				$this->getLink(null, array('q'=>'/modules/Students/student_view_details.php', 'gibbonPersonID'=>$students[$i][4]), 'Student Profile');
				echo '<br />';
                if ($this->getSecurity()->isActionAccessible("/modules/Roll Groups/rollGroups_details.php")) { 
					$this->getLink(null, array('q'=>'/modules/Roll Groups/rollGroups_details.php', 'gibbonRollGroupID'=>$students[$i][7]), array('Roll Group (%s)', array($students[$i][3])));
				?>
            	<?php } 
				if ($students[$i][8]!="") {
					print "<a target='_blank' href='" . $students[$i][8] . "'>" . $students[$i][3] . " " . Gibbon\core\trans::__( 'Website') . "</a>" ;
				} ?>
			</span>
		</div>
		<div style='margin-bottom: 30px; margin-left: 1%; float: left; width: 83%'>
        <?php $dashboardContents = $el->getParentDashboardContents($students[$i][4]) ;
        if (! $dashboardContents ) {
            $this->displayMessage("There are no records to display. " . __LINE__) ;
        }
        else {
            print $dashboardContents ;
        } ?>
    </div>
<?php } ?>
