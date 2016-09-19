<table cellspacing='0' style='width: 400px; margin: 0 auto'>
    <tr class='head'>
        <th style='color: #ff3; text-align: left'>
    		<?php echo Gibbon\trans::__('Name');?><br/>Bootstrap
		</th>
        <th style='color: #ff3; text-align: left'>
            <?php echo Gibbon\trans::__('Confirmed'); ?>
        </th>
        <th style='color: #ff3; text-align: left'>
    		<?php echo Gibbon\trans::__('Actions'); ?>
        </th>
    </tr>

	<?php
    $rowCount = 0;
    foreach($el->staff as $rowConfirm) { ?>
        <script type="text/javascript">
			$(document).ready(function(){
				setInterval(function() {
					$("#row<?php echo $rowCount; ?>").load("index.php", {"q": "index_notification_ajax_alarm_tickUpdate.php", "divert": "true", "gibbonAlarmID": "<?php echo $el->gibbonAlarmID; ?>", "gibbonPersonID": "<?php echo $rowConfirm->getField('gibbonPersonID'); ?>"});
				}, 5000);
			});
		</script>
        <tr id='row<?php echo $rowCount; ?>'>
       		<td style='color: #ff3'>
       			<?php echo $rowConfirm->formatName(true, true); ?><br/>
        	</td>
            <td style='color: #ff3'><?php
        		if ($el->gibbonPersonID == $rowConfirm->getField('gibbonPersonID')) {
            		echo  Gibbon\trans::__('NA');
				} else {
            		if (! empty($rowConfirm->getField('gibbonAlarmConfirmID'))) { ?>
                		<span class="glyphicons glyphicons-check"></span><?php
					}
				} ?>
        	</td>
            <td style='color: #ff3'> <?php
				if ($el->gibbonPersonID != $rowConfirm->getField('gibbonPersonID')) {
					if (empty($rowConfirm->getField('gibbonAlarmConfirmID'))) { ?>
						<a target='_parent' href='<?php echo GIBBON_URL; ?>index.php?q=/modules/Notifications/index_notification_ajax_alarmConfirmProcess.php&divert=true&gibbonPersonID=<?php echo $rowConfirm->getField('gibbonPersonID'); ?>&gibbonAlarmID=<?php echo $el->gibbonAlarmID; ?>' style="color: #ff3"><span class="glyphicons glyphicons-check"></span></a><?php
					}
				} ?>
        	</td>
        </tr><?php
        ++$rowCount;
    } ?>
</table>