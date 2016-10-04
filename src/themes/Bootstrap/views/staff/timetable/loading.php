<?php $this->h2('My Timetable'); ?>
<div id='tt' name='tt' style='width: 100%; min-height: 40px; text-align: center'>
	<?php $this->displayImage('loading.gif', 'Loading', null, null, 'loadTimetable', 'return false;'); ?>
    <br/><p style='text-align: center'><?php echo $this->__('Loading');?></p>
</div>
