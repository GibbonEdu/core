<?php 

if ($this->session->isEmpty('gibbonPersonID')) {
    echo ' . 0 x ' . $this->render('default.minorLinks.notification_off') ;
} else {

    //CHECK FOR SYSTEM ALARM
    if ($this->session->notEmpty('gibbonRoleIDCurrentCategory')) {
        if ($this->session->get('gibbonRoleIDCurrentCategory') == 'Staff') {
            $alarm = $this->config->getSettingByScope('System', 'alarm');
            if ($alarm == 'General' || $alarm == 'Lockdown' || $alarm == 'Custom') {
                $el = new stdClass();
				$el->type = mb_strtolower($alarm);
				$this->render('default.minorLinks.alarm_on', $el);
            } else {
				$this->render('default.minorLinks.alarm_off');
            }
        }
    }

	$obj = new Gibbon\Record\notification($this);
	$notifications = $obj->getCurrentUserNotifications();
	if (count($notifications) > 0) { ?>
 . <a title="<?php echo $this->__('Notifications'); ?>" href="<?php echo GIBBON_URL; ?>index.php?q=/modules/Notifications/notifications.php"><?php echo count($notifications); ?> x <?php echo $this->renderReturn('default.minorLinks.notification_on'); ?></a><?php
	}
	else { ?>
 . 0 x <?php echo $this->renderReturn('default.minorLinks.notification_off');
	} 
}
