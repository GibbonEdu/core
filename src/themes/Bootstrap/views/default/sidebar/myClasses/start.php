<?php 
use Gibbon\core\trans ;
?>
<h2 style='margin-bottom: 10px'  class='sidebar'><?php echo trans::__("My Classes") ; ?></h2>
<table class='mini' cellspacing='0' style='width: 100%; table-layout: fixed;'>
    <tr class='head'>
        <th style='width: 36%; font-size: 85%; text-transform: uppercase'><?php echo trans::__("Class") ; ?></th>
        <?php if ($this->getSecurity()->isActionAccessible("/modules/Planner/planner.php", null, '')) { ?>
            <th style='width: 16%; font-size: 60%; text-align: center; text-transform: uppercase'><?php echo trans::__("Plan") ;?></th>
        <?php }
        if ($this->getSecurity()->getHighestGroupedAction("/modules/Markbook/markbook_view.php")=="View Markbook_allClassesAllData") { ?>
            <th style='width: 16%; font-size: 60%; text-align: center; text-transform: uppercase'>
                <?php print trans::__("Mark") ; ?>
            </th>
        <?php } ?>
        <th style='width: 16%; font-size: 60%; text-align: center; text-transform: uppercase'>
            <?php print trans::__("People") ; ?>
        </th>
        <?php if ($this->getSecurity()->isActionAccessible("/modules/Planner/planner.php", null, '')) { ?>
            <th style='width: 16%; font-size: 60%; text-align: center; text-transform: uppercase'>
                <?php print trans::__("Tasks") ; ?>
            </th>
        <?php } ?>
	</tr>