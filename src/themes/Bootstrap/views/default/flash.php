<?php
if ( empty($params)) 
{
	$params = new stdClass();
	$params->target = 'flash';
}
$this->getReturn();
echo $this->session->get($params->target);
$this->session->clear($params->target);
?>