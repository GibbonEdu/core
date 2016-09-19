<?php 
use Gibbon\core\trans ;
?>
<script type="text/javascript">
	$(document).ready(function(){
		setInterval(function() {
			$("#notifications").load("index.php?q=/modules/Notifications/index_notifications_ajax.php", {
					"action": "<?php echo $el->action; ?>", 
					"divert": "true", 
					"_token": "<?php echo $el->token; ?>"
				});
		}, "<?php echo $el->interval; ?>");
	});
</script>

<div id='notifications' style='display: inline'><?php
	//CHECK FOR SYSTEM ALARM
	if (! $this->session->isEmpty("gibbonRoleIDCurrentCategory") && $this->session->get("gibbonRoleIDCurrentCategory")=="Staff") {
		$alarm = $this->config->getSettingByScope("System", "alarm") ;
		if (in_array($alarm, array("General", "Lockdown", "Custom"))) {
			$type = strtolower($alarm); ?>
<script>
	if ($('div#TB_window').is(':visible')===false) {
		var url = '<?php echo $this->convertGetArraytoURL(array('q' => '/modules/Notifications/index_notification_ajax_alarm.php', 'divert' => 'true', 'type' => $type, 'KeepThis' => 'true', 'TB_iframe'=> 'true', 'width'=>1000, 'height' => 500)); ?>';
		$(document).ready(function() {
			tb_show('', url);
			$('div#TB_window').addClass('alarm') ;
		}) ;
	}
</script><?php
		}
	}

	if (count($el->notifications) > 0) { ?>
 . <a title="<?php echo trans::__('Notifications'); ?>" href="<?php echo GIBBON_URL; ?>index.php?q=/modules/Notifications/notifications.php"><?php echo count($notifications); ?> x <?php echo $this->renderReturn('default.minorLinks.notification_on'); ?></a><?php
	}
	else { ?>
 . 0 x <?php echo $this->renderReturn('default.minorLinks.notification_off');
	} ?>
</div>
