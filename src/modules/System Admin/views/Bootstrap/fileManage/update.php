<?php 
if (substr($el->status, 0, 6) === 'Update') {
	$action = '/modules/System Admin/systemSettingsFileManageUpload.php';
	$tObj = new Gibbon\Form\token($action, null, $this);
	$el->token = $tObj->generateToken($action);
	$el->action = $tObj->generateAction($action);
	
	$this->getLink('upload', array('href'=>'#', 'onclick'=>"$('#file_".$el->name."').load('".$this->convertGetArraytoURL(array('q' => $action, 'fileName'=>$el->name, 'divert'=>true, 'version' => $el->version))."');"));

}
elseif ($el->status == 'Unknown') echo '?';
else echo 'Ok';
