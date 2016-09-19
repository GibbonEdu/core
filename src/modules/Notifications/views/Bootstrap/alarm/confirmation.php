<div style='padding: 0 20px; font-family: arial, sans; text-align: center'>
    <p>
    <?php if ($el->rowCount() == 0) { ?>
        <a target='_parent' style='font-size: 300%; font-weight: bold; color: #ff3' href='<?php echo GIBBON_URL; ?>index.php?q=/modules/Notifications/index_notification_ajax_alarmProcess.php&divert=true&gibbonAlarmID=<?php echo $el->gibbonAlarmID; ?>'><?php echo  Gibbon\core\trans::__('Click here to confirm that you have received this alarm.');?></a><br/>
        <em><?php echo Gibbon\core\trans::__('After confirming receipt, the alarm will continue to be displayed until an administrator has cancelled the alarm.'); ?> </em><?php
    } else { ?>
        <em><?php echo Gibbon\core\trans::__('You have successfully confirmed receipt of this alarm, which will continue to be displayed until an administrator has cancelled the alarm.');?></em><?php
    } ?>
    </p> 
</div>
