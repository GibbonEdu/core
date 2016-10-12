<?php
//Get and set step
$step = isset($_GET["step"]) ? $_GET["step"] : 0 ;

//Deal with $guid setup
if ($step == 0) {
	$charList = "abcdefghijkmnopqrstuvwxyz023456789";
	$guid = "" ;
	for ($i=0; $i<36; $i++) {
		if ($i==9 OR $i==14 OR $i==19 OR $i==24) {
			$guid.="-" ;
		}
		else {
			$guid .= substr($charList, rand(1,strlen($charList)),1);
		}
	}
	
}
else 
{
	$guid = isset($_GET["guid"]) ? $_GET["guid"] : '' ;
}
		
//Deal with non-existent stringReplacement session					
$this->session->set("stringReplacement", array()) ;
$params = new stdClass();
$params->step = $step ;
$_GET['guid'] = $params->guid = $guid ;
define('GIBBON_UID', $guid);
//$this->config->set('guid', $guid);
$this->render('default.header');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<?php $this->render('install.head', $params); ?>
	<?php $this->render('install.body', $params); ?>
</html>
