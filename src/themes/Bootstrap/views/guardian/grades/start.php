<?php
$attainmentAlternativeName = $this->config->getSettingByScope('Markbook', 'attainmentAlternativeName');
$effortAlternativeName = $this->config->getSettingByScope('Markbook', 'effortAlternativeName');
?>
<table cellspacing='0' style='margin: 3px 0px; width: 100%' class='table-striped'>
    <thead>
        <tr class='head'>
            <th style='width: 120px'>
            <?php echo $this->view->__('Assessment'); ?><br/>
            </th>
            <th style='width: 75px'>
            <?php if ($attainmentAlternativeName != '') {
				echo  $attainmentAlternativeName;
			} else {
				echo $this->view->__('Attainment');
			} ?>
            </th>
            <th  style='width: 75px'>
            <?php 			if ($effortAlternativeName != '') {
				echo  $effortAlternativeName;
			} else {
				echo $this->view->__('Effort');
			} ?>
            </th>
            <th>
            <?php echo $this->__('Comment');?>
            </th>
            <th style='width: 75px'>
            <?php echo $this->__('Submission');?>
            </th>
        </tr>
    </thead>
    <tbody>

