<tr id="<?php echo 'file_'.$el->name ; ?>" class=''>
    <td>
        <?php echo $el->name.'.yml' ; ?>
    </td>
    <td>
        <?php $this->displayImage('loading.gif', 'Loading'); ?>
    </td>
    <td class="centre">
        Loading
    </td>
</tr>


<?php
$action = '/modules/System Admin/systemSettingsFileManageCheck.php';
$tObj = new Gibbon\Form\token($action, null, $this);
$el->token = $tObj->generateToken($action);
$el->action = $tObj->generateAction($action);
$this->addScript('
<script type="text/javascript">
	$(document).ready(function(){
		$("#file_'.$el->name.'").load("'.$this->convertGetArraytoURL(array('q' => $action)).'", {
			"action": "'.$el->action.'", 
			"divert": "true", 
			"_token": "'.$el->token.'",
			"fileName": "'.$el->name.'"
		});
	});
</script>
');