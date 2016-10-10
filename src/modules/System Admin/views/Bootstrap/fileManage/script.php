<div id="<?php echo 'file_'.$el->name ; ?>" class="row">
    <div class="col-lg-3 col-md-3">
         <?php echo $el->name.'.yml' ; ?>
    </div>      
    <div class="col-lg-7 col-md-7">
        <?php $this->displayImage('loading.gif', 'Loading'); ?>
    </div>      
    <div class="col-lg-2 col-md-2 centre border">
       Loading
    </div>
</div>
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