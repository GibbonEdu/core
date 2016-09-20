<?php 
$el = new stdClass();
$el->interval = $this->session->get("gibbonRoleIDCurrentCategory")=="Staff" ? 10000 : 120000 ;
$action = '/modules/Notifications/index_notification_ajax.php';
$tObj = new Gibbon\Form\token($action, null, $this);
$el->token = $tObj->generateToken($action);
$el->action = $tObj->generateAction($action);

?>
<script type="text/javascript">
	$(document).ready(function(){
		setInterval(function() {
			$("#notifications").load("<?php echo $this->convertGetArraytoURL(array('q' => $action)); ?>", {
					"action": "<?php echo $el->action; ?>", 
					"divert": "true", 
					"_token": "<?php echo $el->token; ?>"
				});
		}, "<?php echo $el->interval; ?>");
	});
</script>

<div id='notifications'>
	
	<?php $this->render('default.minorLinks.notificationContent'); ?>
    
</div>
