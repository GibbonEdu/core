<?php 
$el = new stdClass();
$el->interval = $this->session->get("gibbonRoleIDCurrentCategory") == "Staff" ? 10000 : 120000 ;
$action = '/modules/Notifications/index_notification_ajax.php';
$tObj = new Gibbon\Form\token($action, null, $this);
$el->token = $tObj->generateToken($action);
$el->action = $tObj->generateAction($action);

$this->addScript('
<script type="text/javascript">
	$(document).ready(function(){
		setInterval(function() {
			$("#notifications").load("'.$this->convertGetArraytoURL(array('q' => $action)).'", {
					"action": "'.$el->action.'", 
					"divert": "true", 
					"_token": "'.$el->token.'"
				});
		}, "'.$el->interval.'");
	});
</script>
');?>

<div id='notifications'>
	
	<?php $this->render('default.minorLinks.notificationContent'); ?>
    
</div>
