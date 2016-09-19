<div style='padding-top: 10px; font-size: 120px; font-weight: bold; font-family: arial, sans; text-align: center'>
    <div style='height: 20px; margin-bottom: 120px; width: 100%; text-align: right; font-size: 14px'>
    <?php if ($el->getField('gibbonPersonID') == $this->session->get('gibbonPersonID')) { ?>
        <p style='padding-right: 20px'><a style='color: #ff3' target='_parent' href='<?php echo GIBBON_URL; ?>index.php?q=/modules/System Admin/alarm_cancelProcess.php&divert=true&gibbonAlarmID=<?php echo $el->getField('gibbonAlarmID'); ?>'><?php echo Gibbon\core\trans::__('Turn Alarm Off'); ?></a></p> <?php
    } ?>
    </div>
    <?php if ($el->type == 'general') { ?>
        <?php echo Gibbon\core\trans::__('General Alarm!'); ?>
        <audio loop autoplay volume="3">
            <source src="./audio/alarm_general.mp3" type="audio/mpeg">
        </audio> <?php
    } elseif ($el->type == 'lockdown') {
        echo Gibbon\core\trans::__('Lockdown!'); ?>
        <audio loop autoplay volume="3">
            <source src="./audio/alarm_lockdown.mp3" type="audio/mpeg">
        </audio><?php
    } elseif ($el->type == 'custom') {
        echo Gibbon\core\trans::__('Alarm!');
        $rowCustom = $this->config->getSetting('customAlarmSound', 'System Admin'); ?>
        <audio loop autoplay volume=3>
            <source src="<?php echo $rowCustom['value']; ?>" type="audio/mpeg">
        </audio><?php
    } ?>
</div>