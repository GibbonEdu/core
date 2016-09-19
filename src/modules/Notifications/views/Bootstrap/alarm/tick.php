<td style='color: #ff3'>
	<?php echo $el->formatName(true, true);?><br/>
</td>
<td style='color: #ff3'> <?php
	if ($el->getField('sounder') == $el->getField('confirmer')) 
		echo Gibbon\trans::__('NA');
	else {
		if (! empty($el->getField('gibbonAlarmConfirmID'))) { ?>
			<span class="glyphicons glyphicons-check"></span>  <?php
		}
	} ?>
</td>
<td style='color: #ff3'> <?php
	if ($el->getField('sounder') != $el->getField('confirmer')) {
		if (empty($el->getField('gibbonAlarmConfirmID'))) { ?>
			<a target='_parent' href='<?php echo GIBBON_URL; ?>index.php?q=/plugins/index_notification_ajax_alarmConfirmProcess.php&divert=true&gibbonPersonID=<?php echo $el->getField('confirmer'); ?>&gibbonAlarmID=<?php echo $el->getField('gibbonAlarmID'); ?>' style="color: rgba(255,255,0,0.75); "><span class="glyphicons glyphicons-check"></span></a> <?php
		}
	} ?>
</td>