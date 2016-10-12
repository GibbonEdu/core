<?php
$this->h2('Parent Dashboard');

$students = $el->students;
$count = count($el);

foreach($students as $i=>$student) {
    ?>
    <h4><?php print $student[1] . " " . $student[0] ; ?></h4>
    <div style='margin-right: 1%; float:left; width: 15%; text-align: center'>
    <?php 
	$stu = new Gibbon\People\student($this);
	$stu->find($student[4]);
	echo $stu->getUserPhoto($student[5], 75) ; ?>
    	<div style='height: 5px'></div>
       		<span style='font-size: 70%'>
            	<?php
				$this->getLink(null, array('q'=>'/modules/Students/student_view_details.php', 'gibbonPersonID' => $student[4]), 'Student Profile');
				echo '<br />';
                if ($this->getSecurity()->isActionAccessible("/modules/Roll Groups/rollGroups_details.php")) { 
					$this->getLink(null, array('q'=>'/modules/Roll Groups/rollGroups_details.php', 'gibbonRollGroupID'=>$student[7]), array('Roll Group (%s)', array($student[3])));
				?>
            	<?php } 
				if (! empty($student[8])) 
					echo "<a target='_blank' href='" . $student[8] . "'>" . $student[3] . " " . $this->__('Website') . "</a>" ;
				?>
			</span>
		</div>
		<div style='margin-bottom: 30px; margin-left: 1%; float: left; width: 83%; '>
        <?php $dashboardContents = $el->getParentDashboardContents($student[4]) ;
        if ($dashboardContents === false) {
            $this->displayMessage("There are no records to display.") ;
        }
        else {
            echo $dashboardContents ;
        } ?>
    </div>
<?php } ?>
