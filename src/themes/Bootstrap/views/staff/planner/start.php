<?php
$links['view'] = array('q' => '/modules/Planner/planner.php', 'prompt'=>'View Planner');
$this->linkTop($links, 'linkTop'); ?>
	
<table cellspacing='0' style='width: 100%' class='table-striped'>
    <thead>
        <tr class='head'>
            <th>
            <?php echo $this->__('Class'); ?><br/>
            </th>
            <th>
            <?php echo $this->__('Lesson'); ?></br>
            <span style='font-size: 85%; font-style: italic'>".<?php echo $this->__('Unit'); ?></span>
            </th>
            <th>
            <?php echo $this->__('Homework'); ?>
            </th>
            <th>
            <?php echo $this->__('Summary');?>
            </th>
            <th>
            <?php echo $this->__('Like');?>
            </th>
            <th>
            <?php echo $this->__('Action');?>
            </th>
        </tr>
    </thead>
    <tbody>
